<?php

// ============================================================
// GAME SAVE / LOAD SYSTEM
// ============================================================

function loadGame($file = "save.json") {

    if (!file_exists($file) || filesize($file) === 0) {

        return [
            "player" => [
                "roster" => [],
                "active" => 0,
                "gold" => 0,
                "discovered" => []
            ],

            "inventory" => [
                "potions" => 0,
                "super_potions" => 0,
                "max_potions" => 0,
                "basic" => 0,
                "greater" => 0,
                "ancient" => 0
            ],

            "currentBattle" => null,

            "message" => "Welcome to Soul Stone RPG!"
        ];
    }

    $data = json_decode(
        file_get_contents($file),
        true
    );

    // Protect against corrupted JSON
    if (!is_array($data)) {

        return [
            "player" => [
                "roster" => [],
                "active" => 0,
                "gold" => 0,
                "discovered" => []
            ],

            "inventory" => [
                "potions" => 0,
                "super_potions" => 0,
                "max_potions" => 0,
                "basic" => 0,
                "greater" => 0,
                "ancient" => 0
            ],

            "currentBattle" => null,

            "message" => "Welcome to Soul Stone RPG!"
        ];
    }

    return $data;
}


// ------------------------------------------------------------
// Existing saveGame function
// ------------------------------------------------------------

function saveGame($game, $file = "save.json") {

    return file_put_contents(
        $file,
        json_encode($game, JSON_PRETTY_PRINT)
    );
}


// ============================================================
// BATTLE CODE SAVE SYSTEM
// ============================================================

/*
 * Creates a unique Battle Code.
 *
 * Example:
 *
 * SS-A7K2-91XM
 *
 * Characters that look similar, such as 0/O and 1/I,
 * are intentionally excluded.
 */

function generateBattleCode(): string {

    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    $code = '';

    for ($i = 0; $i < 8; $i++) {

        $code .= $characters[
            random_int(0, strlen($characters) - 1)
        ];
    }

    return 'SS-'
        . substr($code, 0, 4)
        . '-'
        . substr($code, 4, 4);
}


// ------------------------------------------------------------
// Clean and normalize Battle Code
// ------------------------------------------------------------

function normalizeBattleCode($code): string {

    $code = strtoupper(trim($code));

    // Remove spaces
    $code = str_replace(' ', '', $code);

    return $code;
}


// ------------------------------------------------------------
// Get save file path for Battle Code
// ------------------------------------------------------------

function getBattleSavePath(string $code): string {

    $code = normalizeBattleCode($code);

    /*
     * Only allow the characters used by our Battle Codes.
     * This prevents path traversal.
     */

    $safeCode = preg_replace(
        '/[^A-Z0-9-]/',
        '',
        $code
    );

    return __DIR__
        . '/saves/'
        . $safeCode
        . '.json';
}


// ------------------------------------------------------------
// Check whether a Battle Code exists
// ------------------------------------------------------------

function battleCodeExists(string $code): bool {

    return file_exists(
        getBattleSavePath($code)
    );
}


// ------------------------------------------------------------
// Create a new Battle Code save
// ------------------------------------------------------------

function createBattleSave(array $game): string {

    $saveDirectory = __DIR__ . '/saves';

    // Create saves directory if it doesn't exist
    if (!is_dir($saveDirectory)) {

        mkdir(
            $saveDirectory,
            0755,
            true
        );
    }

    do {

        $code = generateBattleCode();

        $path = getBattleSavePath($code);

    } while (file_exists($path));


    // Store Battle Code inside the game data
    $game['battle_code'] = $code;


    file_put_contents(
        $path,
        json_encode(
            $game,
            JSON_PRETTY_PRINT
        )
    );


    return $code;
}


// ------------------------------------------------------------
// Load Battle Code save
// ------------------------------------------------------------

function loadBattleSave(string $code): ?array {

    $code = normalizeBattleCode($code);

    $path = getBattleSavePath($code);

    if (!file_exists($path)) {

        return null;
    }


    $data = json_decode(
        file_get_contents($path),
        true
    );


    if (!is_array($data)) {

        return null;
    }


    // Make sure the loaded game knows its code
    $data['battle_code'] = $code;


    return $data;
}


// ------------------------------------------------------------
// Save existing Battle Code game
// ------------------------------------------------------------

function saveBattleGame(
    string $code,
    array $game
): bool {

    $code = normalizeBattleCode($code);

    $path = getBattleSavePath($code);


    /*
     * Don't accidentally create a save for an invalid code.
     */

    if (!file_exists($path)) {

        return false;
    }


    $game['battle_code'] = $code;


    $result = file_put_contents(
        $path,
        json_encode(
            $game,
            JSON_PRETTY_PRINT
        )
    );


    return $result !== false;
}


// ============================================================
// MONSTER IDs
// ============================================================

function generateMonsterId() {

    return uniqid();
}


// ============================================================
// CAPTURE TRACKING
// ============================================================

function recordCapture(&$game, $monsterName) {

    if (
        !isset(
            $game['player']['discovered']
        )
    ) {

        $game['player']['discovered'] = [];
    }


    if (
        !in_array(
            $monsterName,
            $game['player']['discovered']
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


    return $chart[$attackerType][$defenderType]
        ?? 1.0;
}


// ============================================================
// SPAWN WILD MONSTER
// ============================================================

function spawnMonster($allMonsters) {

    global $moves;


    // Determine rarity
    $roll = rand(1, 100);


    $target =
        ($roll <= 5)
        ? "ancient"
        : (
            ($roll <= 30)
            ? "greater"
            : "basic"
        );


    // Filter monster pool
    $pool = array_filter(
        $allMonsters,
        function ($m) use ($target) {

            return $m['rarity'] === $target;
        }
    );


    // Safety check
    if (empty($pool)) {

        $pool = $allMonsters;
    }


    // Pick random monster
    $wild = $pool[
        array_rand($pool)
    ];


    // Set current HP
    $wild['hp'] = $wild['max_hp'];


    // Attach moveset
    if (
        isset(
            $moves[$wild['type']]
        )
    ) {

        $wild['moves'] =
            $moves[$wild['type']];

    } else {

        $wild['moves'] = [];
    }


    // Give wild monster a unique ID
    if (!isset($wild['id'])) {

        $wild['id'] =
            generateMonsterId();
    }


    // Make sure XP exists
    if (!isset($wild['xp'])) {

        $wild['xp'] = 0;
    }


    return $wild;
}


// ============================================================
// BATTLE REWARDS
// ============================================================

function getBattleRewards(&$game) {

    $amount = rand(15, 45);


    if (
        !isset(
            $game['player']['gold']
        )
    ) {

        $game['player']['gold'] = 0;
    }


    $game['player']['gold'] += $amount;


    return $amount;
}


// ============================================================
// XP / LEVEL SYSTEM
// ============================================================


// Total XP needed to reach a level
function getXPForLevel($level) {

    if ($level <= 1) {

        return 0;
    }


    return (
        $level *
        ($level - 1) /
        2
    ) * 100;
}


// Determine level from XP
function getLevel($xp) {

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


// XP progress bar data
function getXPStats($xp) {

    $lvl = getLevel($xp);


    $currentLvlTotal =
        getXPForLevel($lvl);


    $nextLvlTotal =
        getXPForLevel(
            $lvl + 1
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

        'level' => $lvl,

        'current' =>
            $xpInCurrentLevel,

        'needed' =>
            $xpNeededForNext,

        'percent' =>
            $percent
    ];
}


// ============================================================
// GIVE XP
// ============================================================

function gainXP(&$monster, $amount) {

    // Make sure XP exists
    if (
        !isset(
            $monster['xp']
        )
    ) {

        $monster['xp'] = 0;
    }


    // Make sure attack exists
    if (
        !isset(
            $monster['attack']
        )
    ) {

        $monster['attack'] = 1;
    }


    // Make sure max HP exists
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


    $monster['xp'] += $amount;


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


        // Increase HP
        $monster['max_hp'] +=
            10 *
            $levelsGained;


        // Fully heal after level up
        $monster['hp'] =
            $monster['max_hp'];


        // Increase attack
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
// SHOP / BUY ITEMS
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
// REMOVE MONSTER FROM ROSTER
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


            // Make sure active index remains valid
            if (
                empty(
                    $game['player']['roster']
                )
            ) {

                $game['player']['active'] = 0;

            } elseif (
                $game['player']['active']
                >=
                count(
                    $game['player']['roster']
                )
            ) {

                $game['player']['active'] = 0;
            }


            return true;
        }
    }


    return false;
}


// ============================================================
// CAPTURE / SOUL STONE LOGIC
// ============================================================

function attemptCatch(
    $h,
    $m,
    $b
) {

    /*
     * Prevent division by zero.
     */

    if ($m <= 0) {

        return false;
    }


    $chance =
        (1 - ($h / $m)) *
        100 +
        $b;


    // Keep chance between 1% and 95%
    $chance =
        max(
            1,
            min(
                95,
                $chance
            )
        );


    return rand(
        1,
        100
    ) <= $chance;
}


// ============================================================
// POTION LOGIC
// ============================================================

function usePotion(
    &$game,
    $type = 'potions'
) {

    // Make sure active monster exists
    if (
        !isset(
            $game['player']['roster']
            [$game['player']['active']]
        )
    ) {

        return false;
    }


    $pm =
        &$game['player']['roster']
        [$game['player']['active']];


    // Healing amounts
    $heals = [

        'potions' =>
            30,

        'super_potions' =>
            100,

        'max_potions' =>
            999
    ];


    // Invalid potion type
    if (
        !isset(
            $heals[$type]
        )
    ) {

        return false;
    }


    // No potion available
    if (
        ($game['inventory'][$type] ?? 0)
        <= 0
    ) {

        return false;
    }


    // Already full HP
    if (
        $pm['hp'] >=
        $pm['max_hp']
    ) {

        return false;
    }


    // Use potion
    $game['inventory'][$type]--;


    $pm['hp'] =
        min(
            $pm['hp'] +
            $heals[$type],

            $pm['max_hp']
        );


    return true;
}


// ============================================================
// SWITCH ACTIVE MONSTER
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


    // Cannot switch to fainted monster
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
// CHECK WHETHER PLAYER HAS USABLE MONSTERS
// ============================================================

function hasUsableMonster($game): bool {

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
// FIND FIRST USABLE MONSTER
// ============================================================

function getFirstUsableMonster($game): ?int {

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

?>