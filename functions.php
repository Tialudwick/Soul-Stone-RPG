<?php

// ============================================================
// SOUL STONE RPG
// FUNCTIONS / SAVE SYSTEM
// ============================================================


// ============================================================
// DEFAULT GAME STRUCTURE
// ============================================================

function getDefaultGame(): array
{
    return [

        'player' => [

            'roster' => [],

            'active' => 0,

            'gold' => 0,

            'discovered' => []
        ],

        'inventory' => [

            'potions' => 0,

            'super_potions' => 0,

            'max_potions' => 0,

            'basic_potion' => 0,

            'greater_potion' => 0,

            'ancient_potion' => 0,

            'basic' => 0,

            'greater' => 0,

            'ancient' => 0
        ],

        'currentBattle' => null,

        'message' => 'Welcome to Soul Stone RPG!',

        'battle_code' => null,

        'game_started' => false,

        'starter_chosen' => false
    ];
}


// ============================================================
// GAME SAVE / LOAD SYSTEM
// ============================================================

function loadGame($file = "save.json")
{
    if (!file_exists($file) || filesize($file) === 0) {

        return getDefaultGame();
    }

    $data = json_decode(
        file_get_contents($file),
        true
    );

    if (!is_array($data)) {

        return getDefaultGame();
    }

    return $data;
}


// ============================================================
// OLD SAVE FUNCTION
// ============================================================

function saveGame($game, $file = "save.json")
{
    return file_put_contents(
        $file,
        json_encode(
            $game,
            JSON_PRETTY_PRINT
        )
    );
}


// ============================================================
// BATTLE CODE SYSTEM
// ============================================================

function generateBattleCode(): string
{
    $characters =
        'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    $code = '';

    for ($i = 0; $i < 8; $i++) {

        $code .= $characters[
            random_int(
                0,
                strlen($characters) - 1
            )
        ];
    }

    return 'SS-'
        . substr($code, 0, 4)
        . '-'
        . substr($code, 4, 4);
}


// ============================================================
// NORMALIZE BATTLE CODE
// ============================================================

function normalizeBattleCode($code): string
{
    $code = strtoupper(trim($code));

    $code = str_replace(
        ' ',
        '',
        $code
    );

    return $code;
}


// ============================================================
// BATTLE SAVE PATH
// ============================================================

function getBattleSavePath(string $code): string
{
    $code =
        normalizeBattleCode($code);

    $safeCode =
        preg_replace(
            '/[^A-Z0-9-]/',
            '',
            $code
        );

    return __DIR__
        . '/saves/'
        . $safeCode
        . '.json';
}


// ============================================================
// CHECK BATTLE CODE
// ============================================================

function battleCodeExists(string $code): bool
{
    return file_exists(
        getBattleSavePath($code)
    );
}


// ============================================================
// CREATE NEW BATTLE SAVE
// ============================================================

function createBattleSave(array $game): string
{
    $saveDirectory =
        __DIR__ . '/saves';


    if (!is_dir($saveDirectory)) {

        mkdir(
            $saveDirectory,
            0755,
            true
        );
    }


    do {

        $code =
            generateBattleCode();

        $path =
            getBattleSavePath($code);

    } while (file_exists($path));


    $game['battle_code'] =
        $code;


    $result =
        file_put_contents(
            $path,
            json_encode(
                $game,
                JSON_PRETTY_PRINT
            )
        );


    if ($result === false) {

        throw new RuntimeException(
            'Unable to create Battle Code save.'
        );
    }


    return $code;
}


// ============================================================
// LOAD BATTLE SAVE
// ============================================================

function loadBattleSave(string $code): ?array
{
    $code =
        normalizeBattleCode($code);

    $path =
        getBattleSavePath($code);


    if (!file_exists($path)) {

        return null;
    }


    $data =
        json_decode(
            file_get_contents($path),
            true
        );


    if (!is_array($data)) {

        return null;
    }


    $data['battle_code'] =
        $code;


    return $data;
}


// ============================================================
// SAVE EXISTING BATTLE GAME
// ============================================================

function saveBattleGame(
    string $code,
    array $game
): bool
{
    $code =
        normalizeBattleCode($code);

    $path =
        getBattleSavePath($code);


    if (!file_exists($path)) {

        return false;
    }


    $game['battle_code'] =
        $code;


    $result =
        file_put_contents(
            $path,
            json_encode(
                $game,
                JSON_PRETTY_PRINT
            )
        );


    return $result !== false;
}


// ============================================================
// MONSTER IDS
// ============================================================

function generateMonsterId(): string
{
    return uniqid(
        'monster_',
        true
    );
}


// ============================================================
// CAPTURE TRACKING
// ============================================================

function recordCapture(
    &$game,
    $monsterName
) {
    if (
        !isset(
            $game['player']['discovered']
        )
    ) {

        $game['player']['discovered'] =
            [];
    }


    if (
        !in_array(
            $monsterName,
            $game['player']['discovered'],
            true
        )
    ) {

        $game['player']['discovered'][] =
            $monsterName;
    }
}


// ============================================================
// TYPE EFFECTIVENESS
// ============================================================

function getTypeMultiplier(
    $attackerType,
    $defenderType
) {
    $chart = [

        "fire" => [

            "earth" => 2.0,

            "water" => 0.5,

            "fire" => 1.0
        ],

        "water" => [

            "fire" => 2.0,

            "earth" => 0.5,

            "water" => 1.0
        ],

        "earth" => [

            "water" => 2.0,

            "fire" => 0.5,

            "earth" => 1.0
        ]
    ];


    return
        $chart[$attackerType][$defenderType]
        ?? 1.0;
}


// ============================================================
// SPAWN WILD MONSTER
// ============================================================

function spawnMonster($allMonsters)
{
    global $moves;


    $roll =
        rand(
            1,
            100
        );


    if ($roll <= 5) {

        $target = 'ancient';

    } elseif ($roll <= 30) {

        $target = 'greater';

    } else {

        $target = 'basic';
    }


    $pool =
        array_filter(
            $allMonsters,
            function ($monster) use ($target) {

                return
                    ($monster['rarity'] ?? '')
                    === $target;
            }
        );


    if (empty($pool)) {

        $pool =
            $allMonsters;
    }


    $wild =
        $pool[
            array_rand($pool)
        ];


    $wild['hp'] =
        $wild['max_hp'];


    if (
        isset(
            $moves[$wild['type']]
        )
    ) {

        $wild['moves'] =
            $moves[$wild['type']];

    } else {

        $wild['moves'] =
            [];
    }


    if (!isset($wild['id'])) {

        $wild['id'] =
            generateMonsterId();
    }


    if (!isset($wild['xp'])) {

        $wild['xp'] = 0;
    }


    return $wild;
}


// ============================================================
// BATTLE REWARDS
// ============================================================

function getBattleRewards(&$game)
{
    $amount =
        rand(
            15,
            45
        );


    if (
        !isset(
            $game['player']['gold']
        )
    ) {

        $game['player']['gold'] =
            0;
    }


    $game['player']['gold'] +=
        $amount;


    return $amount;
}


// ============================================================
// XP SYSTEM
// ============================================================

function getXPForLevel($level)
{
    if ($level <= 1) {

        return 0;
    }


    return (
        $level *
        ($level - 1) /
        2
    ) * 100;
}


// ============================================================
// GET LEVEL
// ============================================================

function getLevel($xp)
{
    $level = 1;


    while (
        $xp >=
        getXPForLevel(
            $level + 1
        )
    ) {

        $level++;
    }


    return $level;
}


// ============================================================
// XP PROGRESS
// ============================================================

function getXPStats($xp)
{
    $level =
        getLevel($xp);


    $currentLvlTotal =
        getXPForLevel($level);


    $nextLvlTotal =
        getXPForLevel(
            $level + 1
        );


    $xpInCurrentLevel =
        $xp -
        $currentLvlTotal;


    $xpNeededForNext =
        $nextLvlTotal -
        $currentLvlTotal;


    $percent =
        ($xpNeededForNext > 0)
        ? (
            $xpInCurrentLevel /
            $xpNeededForNext
        ) * 100
        : 0;


    return [

        'level' =>
            $level,

        'current' =>
            $xpInCurrentLevel,

        'needed' =>
            $xpNeededForNext,

        'next_lvl' =>
            $nextLvlTotal,

        'percent' =>
            max(
                0,
                min(
                    100,
                    $percent
                )
            )
    ];
}


// ============================================================
// GIVE XP
// ============================================================

function gainXP(
    &$monster,
    $amount
) {
    if (
        !isset(
            $monster['xp']
        )
    ) {

        $monster['xp'] = 0;
    }


    if (
        !isset(
            $monster['attack']
        )
    ) {

        $monster['attack'] = 1;
    }


    if (
        !isset(
            $monster['max_hp']
        )
    ) {

        $monster['max_hp'] = 1;
    }


    $oldLevel =
        getLevel(
            $monster['xp']
        );


    $monster['xp'] +=
        $amount;


    $newLevel =
        getLevel(
            $monster['xp']
        );


    if (
        $newLevel >
        $oldLevel
    ) {

        $levelsGained =
            $newLevel -
            $oldLevel;


        $monster['max_hp'] +=
            10 *
            $levelsGained;


        $monster['hp'] =
            $monster['max_hp'];


        $monster['attack'] +=
            5 *
            $levelsGained;


        return
            "Level Up! "
            . $monster['name']
            . " is now Level "
            . $newLevel
            . "!";
    }


    return false;
}


// ============================================================
// SHOP
// ============================================================

function buyItem(
    &$game,
    $itemType,
    $cost
) {
    if (
        ($game['player']['gold'] ?? 0)
        >= $cost
    ) {

        $game['player']['gold'] -=
            $cost;


        if (
            !isset(
                $game['inventory'][$itemType]
            )
        ) {

            $game['inventory'][$itemType] =
                0;
        }


        $game['inventory'][$itemType]++;


        return true;
    }


    return false;
}


// ============================================================
// REMOVE MONSTER
// ============================================================

function discardFromRoster(
    &$game,
    $monsterId
) {
    foreach (
        $game['player']['roster']
        as $index => $monster
    ) {

        if (
            isset($monster['id']) &&
            $monster['id'] === $monsterId
        ) {

            array_splice(
                $game['player']['roster'],
                $index,
                1
            );


            if (
                empty(
                    $game['player']['roster']
                )
            ) {

                $game['player']['active'] =
                    0;

            } elseif (
                $game['player']['active']
                >=
                count(
                    $game['player']['roster']
                )
            ) {

                $game['player']['active'] =
                    0;
            }


            return true;
        }
    }


    return false;
}


// ============================================================
// CAPTURE
// ============================================================

function attemptCatch(
    $h,
    $m,
    $b
) {
    if ($m <= 0) {

        return false;
    }


    $chance =
        (
            1 -
            ($h / $m)
        ) * 100 +
        $b;


    $chance =
        max(
            1,
            min(
                95,
                $chance
            )
        );


    return
        rand(
            1,
            100
        ) <= $chance;
}


// ============================================================
// POTION LOGIC
// ============================================================

function usePotion(&$game, $type = 'basic_potion')
{
    // Make sure the player has an active monster
    if (
        !isset($game['player']['roster']) ||
        !isset($game['player']['active']) ||
        !isset($game['player']['roster'][$game['player']['active']])
    ) {
        return false;
    }

    // Reference the active monster
    $pm = &$game['player']['roster'][$game['player']['active']];

    // Healing amounts for each potion
    $heals = [
        'basic_potion'   => 30,
        'greater_potion' => 100,
        'ancient_potion' => 999,

        // Legacy names — kept so older saves still work
        'potions'        => 30,
        'super_potions'  => 100,
        'max_potions'    => 999
    ];

    // Check that the potion type exists
    if (!isset($heals[$type])) {
        return false;
    }

    // Make sure inventory exists
    if (!isset($game['inventory'])) {
        $game['inventory'] = [];
    }

    // Make sure this potion exists in inventory
    if (!isset($game['inventory'][$type])) {
        $game['inventory'][$type] = 0;
    }

    // Check whether the player has any
    if ($game['inventory'][$type] <= 0) {
        return false;
    }

    // Make sure monster has HP values
    $currentHP = $pm['hp'] ?? 0;
    $maxHP = $pm['max_hp'] ?? 1;

    // Monster is already at full HP
    if ($currentHP >= $maxHP) {
        return false;
    }

    // Remove one potion
    $game['inventory'][$type]--;

    // Heal monster
    $pm['hp'] = min(
        $currentHP + $heals[$type],
        $maxHP
    );

    return true;
}

// ============================================================
// SWITCH MONSTER
// ============================================================

function switchActiveMonster(
    &$game,
    $newIndex
) {
    if (
        !isset(
            $game['player']['roster']
            [$newIndex]
        )
    ) {

        return false;
    }


    if (
        $game['player']['roster']
        [$newIndex]['hp'] <= 0
    ) {

        return false;
    }


    $game['player']['active'] =
        $newIndex;


    return true;
}


// ============================================================
// HAS USABLE MONSTER
// ============================================================

function hasUsableMonster($game): bool
{
    foreach (
        $game['player']['roster']
        as $monster
    ) {

        if (
            ($monster['hp'] ?? 0) > 0
        ) {

            return true;
        }
    }


    return false;
}


// ============================================================
// FIRST USABLE MONSTER
// ============================================================

function getFirstUsableMonster($game): ?int
{
    foreach (
        $game['player']['roster']
        as $index => $monster
    ) {

        if (
            ($monster['hp'] ?? 0) > 0
        ) {

            return $index;
        }
    }


    return null;
}


// ============================================================
// STARTER MONSTER SYSTEM
// ============================================================

function getStarterMonsters(
    $allMonsters
): array {
    $starterNames = [

        'emberling',

        'tidepup',

        'gravhorn'
    ];


    $starters = [];


    foreach (
        $allMonsters
        as $monster
    ) {

        $monsterName =
            strtolower(
                $monster['name'] ?? ''
            );


        if (
            in_array(
                $monsterName,
                $starterNames,
                true
            )
        ) {

            $starters[$monsterName] =
                $monster;
        }
    }


    return $starters;
}


// ============================================================
// GET STARTER
// ============================================================

function getStarterMonster(
    $allMonsters,
    string $name
): ?array {
    $name =
        strtolower(
            trim($name)
        );


    foreach (
        $allMonsters
        as $monster
    ) {

        if (
            strtolower(
                $monster['name'] ?? ''
            ) === $name
        ) {

            return $monster;
        }
    }


    return null;
}


// ============================================================
// PREPARE STARTER
// ============================================================

function prepareStarterMonster(
    array $monster
): array {
    global $moves;


    $monster['xp'] =
        0;


    $monster['hp'] =
        $monster['max_hp'];


    $monster['id'] =
        generateMonsterId();


    if (
        isset(
            $moves[$monster['type']]
        )
    ) {

        $monster['moves'] =
            $moves[$monster['type']];

    } else {

        $monster['moves'] =
            [];
    }


    return $monster;
}


// ============================================================
// CREATE COMPLETELY NEW GAME
// ============================================================

function createNewGame(): array
{
    return [

        'player' => [

            'roster' => [],

            'active' => 0,

            'gold' => 500,

            'discovered' => []
        ],

        'inventory' => [

            'potions' => 0,

            'super_potions' => 0,

            'max_potions' => 0,

            'basic_potion' => 0,

            'greater_potion' => 0,

            'ancient_potion' => 0,

            'basic' => 0,

            'greater' => 0,

            'ancient' => 0
        ],

        'currentBattle' => null,

        'message' =>
            'Welcome to Soul Stone RPG!',

        'battle_code' => null,

        'game_started' => false,

        'starter_chosen' => false
    ];
}


// ============================================================
// START GAME WITH STARTER
// ============================================================

function startGameWithStarter(
    array &$game,
    array $starter
): void {
    $starter =
        prepareStarterMonster(
            $starter
        );


    $game['player']['roster'] = [

        $starter
    ];


    $game['player']['active'] =
        0;


    $game['player']['gold'] =
        500;


    $game['player']['discovered'] = [

        $starter['name']
    ];


    $game['currentBattle'] =
        null;


    $game['game_started'] =
        true;


    $game['starter_chosen'] =
        true;


    $game['message'] =
        "Your journey has begun! "
        . $starter['name']
        . " has joined your team.";
}

?>