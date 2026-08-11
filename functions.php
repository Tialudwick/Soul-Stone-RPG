<?php

// functions & save logic


// default game structure

function getDefaultGame(): array
{
    return [

        'player' => [

            'name' => '',

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


        'game_started' => false,

        'starter_chosen' => false
    ];
}


// game save & load logic

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


// old save function

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

// =========================================================
// MULTIPLE PLAYER SAVE SYSTEM
// =========================================================

/**
 * Get a unique filename for a player's save.
 *
 * Example:
 *   saves/player_John.json
 *   saves/player_John_1.json
 *   saves/player_John_2.json
 */
function getUniqueSaveFileName(string $playerName): string
{
    $saveDirectory = __DIR__ . '/saves';

    // Create the saves directory if it does not exist
    if (!is_dir($saveDirectory)) {
        mkdir($saveDirectory, 0777, true);
    }

    // Clean the player name so it is safe to use as a filename
    $safeName = preg_replace(
        '/[^a-zA-Z0-9_-]/',
        '_',
        trim($playerName)
    );

    // Prevent an empty filename
    if ($safeName === '') {
        $safeName = 'Player';
    }

    $baseFileName = 'player_' . $safeName;
    $fileName = $baseFileName . '.json';

    $counter = 1;

    // Make sure we never overwrite an existing save
    while (file_exists($saveDirectory . '/' . $fileName)) {
        $fileName = $baseFileName . '_' . $counter . '.json';
        $counter++;
    }

    return $saveDirectory . '/' . $fileName;
}


/**
 * Save a player's game to a separate save file.
 *
 * If $file is supplied, that exact save file is updated.
 * If no file is supplied, a new unique save file is created.
 */
function savePlayerGame(array $game, ?string $file = null): string|false
{
    $saveDirectory = __DIR__ . '/saves';

    // Create saves directory if necessary
    if (!is_dir($saveDirectory)) {
        if (!mkdir($saveDirectory, 0777, true)) {
            return false;
        }
    }

    // Create a new unique save file if one wasn't provided
    if ($file === null || $file === '') {
        $playerName =
            $game['player']['name']
            ?? 'Player';

        $file = getUniqueSaveFileName($playerName);
    }

    // Make sure the game knows which save file it belongs to
    $game['_save_file'] = basename($file);

    $json = json_encode(
        $game,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        return false;
    }

    $result = file_put_contents(
        $file,
        $json,
        LOCK_EX
    );

    if ($result === false) {
        return false;
    }

    return $file;
}


/**
 * Load a player's saved game.
 */
function loadPlayerGame(string $file): array
{
    // If only a filename was supplied, look inside /saves
    if (
        !str_contains($file, DIRECTORY_SEPARATOR) &&
        !str_contains($file, '/')
    ) {
        $file = __DIR__ . '/saves/' . basename($file);
    }

    // Prevent attempts to load a directory
    if (!is_file($file)) {
        return getDefaultGame();
    }

    $contents = file_get_contents($file);

    if ($contents === false || trim($contents) === '') {
        return getDefaultGame();
    }

    $game = json_decode($contents, true);

    if (!is_array($game)) {
        return getDefaultGame();
    }

    // Remember which save file was loaded
    $game['_save_file'] = basename($file);

    return $game;
}


/**
 * Get all saved games.
 */
function getSavedGames(): array
{
    $saveDirectory = __DIR__ . '/saves';

    // No saves directory yet
    if (!is_dir($saveDirectory)) {
        return [];
    }

    $files = glob($saveDirectory . '/player_*.json');

    if ($files === false) {
        return [];
    }

    $savedGames = [];

    foreach ($files as $file) {

        if (!is_file($file)) {
            continue;
        }

        $contents = file_get_contents($file);

        if ($contents === false || trim($contents) === '') {
            continue;
        }

        $game = json_decode($contents, true);

        if (!is_array($game)) {
            continue;
        }

        $savedGames[] = [
            'file' => basename($file),

            'name' =>
                $game['player']['name']
                ?? 'Unnamed Player',

            'gold' =>
                $game['player']['gold']
                ?? 0,

            'roster_count' =>
                isset($game['player']['roster'])
                && is_array($game['player']['roster'])
                    ? count($game['player']['roster'])
                    : 0,

            'starter_chosen' =>
                $game['starter_chosen']
                ?? false,

            'game_started' =>
                $game['game_started']
                ?? false,

            'modified' =>
                filemtime($file)
        ];
    }

    // Newest saves first
    usort(
        $savedGames,
        function ($a, $b) {
            return $b['modified'] <=> $a['modified'];
        }
    );

    return $savedGames;
}


// monster ID's

function generateMonsterId(): string
{
    return uniqid(
        'monster_',
        true
    );
}


// capture tracking logic

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


// type effectiveness multiplier

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


// wild monster spawn logic

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


// nattle rewards

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


// XP system

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


// get level logic

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


// xp progress

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


// give XP

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


// shop

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


// remove monster logic

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


// capture

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


// potion logic

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

// switch monster

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


// has usable monster logic

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


// first usable monster

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


// started monster system

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


// get starter

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


// prep starter

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


// create completely new game

function createNewGame(): array
{
    return [

        'player' => [

            'name' => '',

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


        'game_started' => false,

        'starter_chosen' => false
    ];
}


// start game with starter

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