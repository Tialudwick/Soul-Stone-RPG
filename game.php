```php
<?php
// ============================================================
// SOUL STONE RPG
// GAME.PHP
// ============================================================

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/monsters.php';


// ============================================================
// LOAD / INITIALIZE GAME
// ============================================================

$battleCode = $_SESSION['battle_code'] ?? null;

if ($battleCode) {

    $game = loadBattleSave($battleCode);

    if ($game === null) {
        unset($_SESSION['battle_code']);
        $battleCode = null;
    }
}

if (!$battleCode) {

    $game = createNewGame();

    /*
     * If game.php is reached directly without going through
     * newgame.php, send the player back to the start screen.
     */
    if (
        empty($game['player']['roster']) ||
        empty($game['starter_chosen'])
    ) {
        header("Location: index.php");
        exit;
    }
}


// ============================================================
// BASIC DATA SAFETY
// ============================================================

if (!isset($game['player'])) {
    $game['player'] = [];
}

if (!isset($game['player']['roster'])) {
    $game['player']['roster'] = [];
}

if (!isset($game['player']['active'])) {
    $game['player']['active'] = 0;
}

if (!isset($game['player']['gold'])) {
    $game['player']['gold'] = 500;
}

if (!isset($game['player']['discovered'])) {
    $game['player']['discovered'] = [];
}

if (!isset($game['inventory'])) {
    $game['inventory'] = [];
}


// ============================================================
// INVENTORY NORMALIZATION
// ============================================================

$inventoryDefaults = [

    'basic_potion'   => 0,
    'greater_potion' => 0,
    'ancient_potion' => 0,

    'basic'          => 0,
    'greater'        => 0,
    'ancient'        => 0
];

foreach ($inventoryDefaults as $item => $amount) {

    if (!isset($game['inventory'][$item])) {
        $game['inventory'][$item] = $amount;
    }
}


// ============================================================
// CURRENT BATTLE
// ============================================================

$currentBattle = $game['currentBattle'] ?? null;


// ============================================================
// HANDLE PLAYER ACTIONS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    // ========================================================
    // START A NEW WILD BATTLE
    // ========================================================

    if ($action === 'start_battle') {

        if (!hasUsableMonster($game)) {

            $game['message'] =
                "You have no usable monsters. Visit the roster and heal your team.";

        } else {

            $wild = spawnMonster($allMonsters);

            $game['currentBattle'] = [
                'enemy' => $wild
            ];

            $game['message'] =
                "A wild "
                . ucfirst($wild['name'])
                . " appeared!";
        }
    }


    // ========================================================
    // RUN AWAY
    // ========================================================

    elseif ($action === 'run') {

        if ($game['currentBattle'] !== null) {

            $game['currentBattle'] = null;

            $game['message'] =
                "You escaped safely.";
        }
    }


    // ========================================================
    // SWITCH ACTIVE MONSTER
    // ========================================================

    elseif (isset($_POST['switch_to'])) {

        $newIndex = (int) $_POST['switch_to'];

        if (
            switchActiveMonster(
                $game,
                $newIndex
            )
        ) {

            $activeMonster =
                $game['player']['roster'][$newIndex];

            $game['message'] =
                $activeMonster['name']
                . " is now your active monster.";

        } else {

            $game['message'] =
                "That monster cannot battle right now.";
        }
    }


    // ========================================================
    // USE BASIC POTION
    // ========================================================

    elseif ($action === 'use_pot_basic') {

        if (
            usePotion(
                $game,
                'basic_potion'
            )
        ) {

            $game['message'] =
                "Your monster recovered 30 HP.";

        } else {

            $game['message'] =
                "You cannot use a Basic Potion right now.";
        }
    }


    // ========================================================
    // USE GREATER POTION
    // ========================================================

    elseif ($action === 'use_pot_greater') {

        if (
            usePotion(
                $game,
                'greater_potion'
            )
        ) {

            $game['message'] =
                "Your monster recovered 100 HP.";

        } else {

            $game['message'] =
                "You cannot use a Greater Potion right now.";
        }
    }


    // ========================================================
    // USE ANCIENT POTION
    // ========================================================

    elseif ($action === 'use_pot_ancient') {

        if (
            usePotion(
                $game,
                'ancient_potion'
            )
        ) {

            $game['message'] =
                "Your monster was completely healed.";

        } else {

            $game['message'] =
                "You cannot use an Ancient Potion right now.";
        }
    }


    // ========================================================
    // CAPTURE BASIC
    // ========================================================

    elseif ($action === 'catch_basic') {

        if ($currentBattle === null) {

            $game['message'] =
                "There is no wild monster to capture.";

        } elseif (
            ($game['inventory']['basic'] ?? 0) <= 0
        ) {

            $game['message'] =
                "You do not have a Basic Soul Stone.";

        } elseif (
            count($game['player']['roster']) >= 8
        ) {

            $game['message'] =
                "Your roster is full.";

        } else {

            $enemy =
                $game['currentBattle']['enemy'];

            $caught =
                attemptCatch(
                    $enemy['hp'],
                    $enemy['max_hp'],
                    10
                );

            $game['inventory']['basic']--;

            if ($caught) {

                $captured = $enemy;

                $captured['hp'] =
                    $captured['max_hp'];

                $captured['id'] =
                    generateMonsterId();

                $captured['xp'] = 0;

                $game['player']['roster'][] =
                    $captured;

                recordCapture(
                    $game,
                    $captured['name']
                );

                $game['currentBattle'] = null;

                $game['message'] =
                    "Success! "
                    . ucfirst($captured['name'])
                    . " was captured!";
            } else {

                $game['message'] =
                    "The Basic Soul Stone failed to capture "
                    . ucfirst($enemy['name'])
                    . ".";
            }
        }
    }


    // ========================================================
    // CAPTURE GREATER
    // ========================================================

    elseif ($action === 'catch_greater') {

        if ($currentBattle === null) {

            $game['message'] =
                "There is no wild monster to capture.";

        } elseif (
            ($game['inventory']['greater'] ?? 0) <= 0
        ) {

            $game['message'] =
                "You do not have a Greater Soul Stone.";

        } elseif (
            count($game['player']['roster']) >= 8
        ) {

            $game['message'] =
                "Your roster is full.";

        } else {

            $enemy =
                $game['currentBattle']['enemy'];

            $caught =
                attemptCatch(
                    $enemy['hp'],
                    $enemy['max_hp'],
                    25
                );

            $game['inventory']['greater']--;

            if ($caught) {

                $captured = $enemy;

                $captured['hp'] =
                    $captured['max_hp'];

                $captured['id'] =
                    generateMonsterId();

                $captured['xp'] = 0;

                $game['player']['roster'][] =
                    $captured;

                recordCapture(
                    $game,
                    $captured['name']
                );

                $game['currentBattle'] = null;

                $game['message'] =
                    "Amazing! "
                    . ucfirst($captured['name'])
                    . " was captured!";
            } else {

                $game['message'] =
                    "The Greater Soul Stone failed to capture "
                    . ucfirst($enemy['name'])
                    . ".";
            }
        }
    }


    // ========================================================
    // CAPTURE ANCIENT
    // ========================================================

    elseif ($action === 'catch_ancient') {

        if ($currentBattle === null) {

            $game['message'] =
                "There is no wild monster to capture.";

        } elseif (
            ($game['inventory']['ancient'] ?? 0) <= 0
        ) {

            $game['message'] =
                "You do not have an Ancient Soul Stone.";

        } elseif (
            count($game['player']['roster']) >= 8
        ) {

            $game['message'] =
                "Your roster is full.";

        } else {

            $enemy =
                $game['currentBattle']['enemy'];

            $caught =
                attemptCatch(
                    $enemy['hp'],
                    $enemy['max_hp'],
                    45
                );

            $game['inventory']['ancient']--;

            if ($caught) {

                $captured = $enemy;

                $captured['hp'] =
                    $captured['max_hp'];

                $captured['id'] =
                    generateMonsterId();

                $captured['xp'] = 0;

                $game['player']['roster'][] =
                    $captured;

                recordCapture(
                    $game,
                    $captured['name']
                );

                $game['currentBattle'] = null;

                $game['message'] =
                    "Legendary capture! "
                    . ucfirst($captured['name'])
                    . " joined your roster!";
            } else {

                $game['message'] =
                    "The Ancient Soul Stone failed to capture "
                    . ucfirst($enemy['name'])
                    . ".";
            }
        }
    }


    // ========================================================
    // ATTACK
    // ========================================================

    elseif (
        str_starts_with(
            $action,
            'attack_'
        )
    ) {

        if ($game['currentBattle'] === null) {

            $game['message'] =
                "There is no enemy to attack.";

        } else {

            $moveIndex =
                (int) str_replace(
                    'attack_',
                    '',
                    $action
                );

            $activeIndex =
                $game['player']['active'];

            if (
                !isset(
                    $game['player']['roster'][$activeIndex]
                )
            ) {

                $game['message'] =
                    "You do not have an active monster.";

            } else {

                $pm =
                    &$game['player']['roster']
                    [$activeIndex];

                $enemy =
                    &$game['currentBattle']['enemy'];


                // ------------------------------------------------
                // Make sure moves exist
                // ------------------------------------------------

                if (
                    empty($pm['moves'])
                ) {

                    $pm['moves'] =
                        $moves[$pm['type']]
                        ?? [];
                }


                // ------------------------------------------------
                // Validate move
                // ------------------------------------------------

                if (
                    !isset(
                        $pm['moves'][$moveIndex]
                    )
                ) {

                    $game['message'] =
                        "Invalid move.";

                } elseif ($pm['hp'] <= 0) {

                    $game['message'] =
                        $pm['name']
                        . " has fainted.";

                } else {

                    $move =
                        $pm['moves'][$moveIndex];


                    // ------------------------------------------------
                    // Calculate player damage
                    // ------------------------------------------------

                    $multiplier =
                        getTypeMultiplier(
                            $move['type'],
                            $enemy['type']
                        );

                    $baseDamage =
                        $pm['attack']
                        * $move['power'];

                    $damage =
                        max(
                            1,
                            (int) round(
                                $baseDamage
                                * $multiplier
                            )
                        );

                    $enemy['hp'] =
                        max(
                            0,
                            $enemy['hp'] - $damage
                        );


                    $effectText = '';

                    if ($multiplier >= 2) {

                        $effectText =
                            " Super effective!";

                    } elseif ($multiplier <= 0.5) {

                        $effectText =
                            " Not very effective...";
                    }


                    // ------------------------------------------------
                    // Enemy defeated
                    // ------------------------------------------------

                    if ($enemy['hp'] <= 0) {

                        $gold =
                            getBattleRewards(
                                $game
                            );

                        $xpReward =
                            25;

                        if (
                            ($enemy['rarity'] ?? '')
                            === 'greater'
                        ) {

                            $xpReward = 50;

                        } elseif (
                            ($enemy['rarity'] ?? '')
                            === 'ancient'
                        ) {

                            $xpReward = 100;
                        }


                        $levelMessage =
                            gainXP(
                                $pm,
                                $xpReward
                            );


                        $game['currentBattle'] =
                            null;


                        if ($levelMessage) {

                            $game['message'] =
                                $pm['name']
                                . " dealt "
                                . $damage
                                . " damage!"
                                . $effectText
                                . " "
                                . $levelMessage
                                . " +"
                                . $gold
                                . " gold.";

                        } else {

                            $game['message'] =
                                $pm['name']
                                . " dealt "
                                . $damage
                                . " damage!"
                                . $effectText
                                . " Wild "
                                . $enemy['name']
                                . " was defeated!"
                                . " +"
                                . $xpReward
                                . " XP and +"
                                . $gold
                                . " gold.";
                        }

                    } else {

                        // ------------------------------------------------
                        // Enemy attacks back
                        // ------------------------------------------------

                        $enemyMoves =
                            $enemy['moves']
                            ?? (
                                $moves[$enemy['type']]
                                ?? []
                            );

                        if (!empty($enemyMoves)) {

                            $enemyMove =
                                $enemyMoves[
                                    array_rand(
                                        $enemyMoves
                                    )
                                ];

                            $enemyMultiplier =
                                getTypeMultiplier(
                                    $enemyMove['type'],
                                    $pm['type']
                                );

                            $enemyBaseDamage =
                                $enemy['attack']
                                * $enemyMove['power'];

                            $enemyDamage =
                                max(
                                    1,
                                    (int) round(
                                        $enemyBaseDamage
                                        * $enemyMultiplier
                                    )
                                );

                            $pm['hp'] =
                                max(
                                    0,
                                    $pm['hp']
                                    - $enemyDamage
                                );


                            // ------------------------------------------------
                            // Player monster fainted
                            // ------------------------------------------------

                            if ($pm['hp'] <= 0) {

                                $nextMonster =
                                    getFirstUsableMonster(
                                        $game
                                    );

                                if (
                                    $nextMonster !== null
                                ) {

                                    $game['player']['active'] =
                                        $nextMonster;

                                    $newActive =
                                        $game['player']['roster']
                                        [$nextMonster];

                                    $game['message'] =
                                        $pm['name']
                                        . " dealt "
                                        . $damage
                                        . " damage!"
                                        . $effectText
                                        . " "
                                        . $pm['name']
                                        . " fainted!"
                                        . " "
                                        . $newActive['name']
                                        . " entered the battle.";

                                } else {

                                    $game['currentBattle'] =
                                        null;

                                    $game['message'] =
                                        "All of your monsters have fainted. "
                                        . "You need to heal your team.";
                                }

                            } else {

                                $game['message'] =
                                    $pm['name']
                                    . " dealt "
                                    . $damage
                                    . " damage!"
                                    . $effectText
                                    . " Wild "
                                    . $enemy['name']
                                    . " used "
                                    . $enemyMove['name']
                                    . " and dealt "
                                    . $enemyDamage
                                    . " damage!";
                            }
                        }
                    }
                }
            }
        }
    }


    // ========================================================
    // UNKNOWN ACTION
    // ========================================================

    elseif ($action !== '') {

        $game['message'] =
            "Nothing happened.";
    }


    // ========================================================
    // SAVE GAME
    // ========================================================

    if ($battleCode) {

        saveBattleGame(
            $battleCode,
            $game
        );
    }
}


// ============================================================
// DISPLAY DATA
// ============================================================

$battleCode =
    $game['battle_code']
    ?? $battleCode
    ?? '';


// ------------------------------------------------------------
// Active player monster
// ------------------------------------------------------------

$activeIndex =
    $game['player']['active']
    ?? 0;

$pm =
    $game['player']['roster'][$activeIndex]
    ?? null;


// ------------------------------------------------------------
// Make sure active monster has moves
// ------------------------------------------------------------

if ($pm !== null) {

    if (
        empty($pm['moves'])
    ) {

        $pm['moves'] =
            $moves[$pm['type']]
            ?? [];

        $game['player']['roster']
        [$activeIndex]['moves'] =
            $pm['moves'];
    }
}


// ------------------------------------------------------------
// Enemy
// ------------------------------------------------------------

$em =
    $game['currentBattle']['enemy']
    ?? null;


// ------------------------------------------------------------
// Player HP
// ------------------------------------------------------------

$pmHP = 0;

if ($pm !== null && $pm['max_hp'] > 0) {

    $pmHP =
        (
            $pm['hp']
            / $pm['max_hp']
        ) * 100;
}


// ------------------------------------------------------------
// Enemy HP
// ------------------------------------------------------------

$emHP = 0;

if ($em !== null && $em['max_hp'] > 0) {

    $emHP =
        (
            $em['hp']
            / $em['max_hp']
        ) * 100;
}


// ------------------------------------------------------------
// XP
// ------------------------------------------------------------

$pmXP =
    $pm !== null
    ? getXPStats(
        $pm['xp'] ?? 0
    )
    : [
        'level' => 1,
        'current' => 0,
        'needed' => 100,
        'percent' => 0
    ];


// ============================================================
// HTML
// ============================================================
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Soul Stone RPG
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- ========================================================
     NAVIGATION
     ======================================================== -->

<nav class="top-nav">

    <div class="logo">

        <a href="index.php">
            <strong>SOUL STONE RPG</strong>
        </a>

    </div>

</nav>


<!-- ========================================================
     MAIN GAME
     ======================================================== -->

<main class="main-container">


    <!-- ====================================================
         BATTLE COLUMN
         ==================================================== -->

    <div class="battle-column">


        <?php if ($em !== null && $pm !== null): ?>


            <!-- ==============================================
                 BATTLEFIELD
                 ============================================== -->

            <div class="battlefield">


                <!-- PLAYER MONSTER -->

                <div class="monster-card player-card">

                    <div
                        class="type-badge <?php echo htmlspecialchars($pm['type']); ?>"
                    >

                        <?php echo strtoupper(
                            htmlspecialchars($pm['type'])
                        ); ?>

                    </div>


                    <div class="card-header">

                        <span>
                            <?php echo htmlspecialchars($pm['name']); ?>
                        </span>

                        <span>
                            Lv.<?php echo $pmXP['level']; ?>
                        </span>

                    </div>


                    <div class="image-well">

                        <img
                            src="images/monsters/<?php echo htmlspecialchars($pm['image']); ?>"
                            alt="<?php echo htmlspecialchars($pm['name']); ?>"
                        >

                    </div>


                    <div class="hp-bar">

                        <div
                            class="hp-fill"
                            style="width:<?php echo max(0, min(100, $pmHP)); ?>%;"
                        ></div>

                    </div>


                    <div class="card-stats">

                        HP:
                        <?php echo $pm['hp']; ?>
                        /
                        <?php echo $pm['max_hp']; ?>

                        |

                        ATK:
                        <?php echo $pm['attack']; ?>

                    </div>


                    <!-- XP -->

                    <div style="margin-top: 3px;">

                        <div
                            class="hp-bar"
                            style="height: 4px; background: #34495e;"
                        >

                            <div
                                class="hp-fill"
                                style="
                                    width:<?php echo max(0, min(100, $pmXP['percent'])); ?>%;
                                    background:#3498db;
                                "
                            ></div>

                        </div>


                        <div
                            style="
                                font-size:0.6em;
                                display:flex;
                                justify-content:space-between;
                                color:#3d2b1f;
                                font-weight:bold;
                                margin-top:2px;
                            "
                        >

                            <span>

                                XP:
                                <?php echo $pmXP['current']; ?>
                                /
                                <?php echo $pmXP['needed']; ?>

                            </span>


                            <span>

                                <?php echo floor($pmXP['percent']); ?>%

                            </span>

                        </div>

                    </div>

                </div>


                <!-- ENEMY MONSTER -->

                <div class="monster-card enemy-card">

                    <div
                        class="type-badge <?php echo htmlspecialchars($em['type']); ?>"
                    >

                        <?php echo strtoupper(
                            htmlspecialchars($em['type'])
                        ); ?>

                    </div>


                    <div class="card-header">

                        <span>

                            Wild
                            <?php echo htmlspecialchars($em['name']); ?>

                        </span>


                        <span>

                            Lv.<?php echo getLevel($em['xp'] ?? 0); ?>

                        </span>

                    </div>


                    <div class="image-well">

                        <img
                            src="images/monsters/<?php echo htmlspecialchars($em['image']); ?>"
                            alt="<?php echo htmlspecialchars($em['name']); ?>"
                        >

                    </div>


                    <div class="hp-bar">

                        <div
                            class="hp-fill hp-enemy"
                            style="width:<?php echo max(0, min(100, $emHP)); ?>%;"
                        ></div>

                    </div>


                    <div class="card-stats">

                        WILD BEAST

                    </div>

                </div>


            </div>


            <!-- ==============================================
                 BATTLE MESSAGE
                 ============================================== -->

            <div
                style="
                    color:white;
                    text-align:center;
                    font-size:0.85em;
                    font-weight:bold;
                    min-height:18px;
                    margin:10px 0;
                "
            >

                <?php echo htmlspecialchars(
                    $game['message'] ?? ''
                ); ?>

            </div>


            <!-- ==============================================
                 MOVES
                 ============================================== -->

            <form method="post">

                <div class="moves-grid">

                    <?php foreach (
                        ($pm['moves'] ?? [])
                        as $i => $move
                    ): ?>

                        <button
                            type="submit"
                            name="action"
                            value="attack_<?php echo $i; ?>"
                            class="btn btn-move"

                            <?php echo
                                $pm['hp'] <= 0
                                ? 'disabled'
                                : '';
                            ?>
                        >

                            <?php echo strtoupper(
                                htmlspecialchars($move['name'])
                            ); ?>

                            <br>

                            <small style="font-size:0.8em;">

                                PWR:
                                <?php echo $move['power']; ?>

                            </small>

                        </button>

                    <?php endforeach; ?>

                </div>


                <button
                    type="submit"
                    name="action"
                    value="run"
                    class="btn btn-run"
                >

                    RUN AWAY

                </button>

            </form>


        <?php else: ?>


            <!-- ==============================================
                 EXPLORE SCREEN
                 ============================================== -->

            <div class="explore-screen">

                <h3
                    style="
                        color:white;
                        margin-bottom:15px;
                    "
                >

                    <?php echo htmlspecialchars(
                        $game['message']
                        ?: "Explore the world of Soul Stones!"
                    ); ?>

                </h3>


                <form method="post">

                    <button
                        type="submit"
                        name="action"
                        value="start_battle"
                        class="btn"
                        style="
                            background:#27ae60;
                            padding:15px 40px;
                            font-size:1.3em;
                        "
                    >

                        EXPLORE GRASS

                    </button>

                </form>

            </div>


        <?php endif; ?>


    </div>


    <!-- ====================================================
         UI COLUMN
         ==================================================== -->

    <div class="ui-column">


        <form
            method="post"
            style="
                display:flex;
                flex-direction:column;
                height:100%;
                justify-content:space-between;
            "
        >


            <div>


                <!-- ==========================================
                     POTIONS
                     ========================================== -->

                <div class="section-title">
                    POTIONS
                </div>


                <div class="btn-grid">

                    <button
                        type="submit"
                        name="action"
                        value="use_pot_basic"
                        class="btn btn-pot"
                    >

                        Basic
                        (
                        <?php echo
                            $game['inventory']['basic_potion'];
                        ?>
                        )

                    </button>


                    <button
                        type="submit"
                        name="action"
                        value="use_pot_greater"
                        class="btn btn-pot"
                    >

                        Great
                        (
                        <?php echo
                            $game['inventory']['greater_potion'];
                        ?>
                        )

                    </button>


                    <button
                        type="submit"
                        name="action"
                        value="use_pot_ancient"
                        class="btn btn-pot"
                    >

                        Ancient
                        (
                        <?php echo
                            $game['inventory']['ancient_potion'];
                        ?>
                        )

                    </button>

                </div>


                <!-- ==========================================
                     SOUL STONES
                     ========================================== -->

                <div class="section-title">
                    SOUL STONES
                </div>


                <div class="btn-grid">


                    <button
                        type="submit"
                        name="action"
                        value="catch_basic"
                        class="btn btn-stone"
                    >

                        Basic
                        (
                        <?php echo
                            $game['inventory']['basic'];
                        ?>
                        )

                    </button>


                    <button
                        type="submit"
                        name="action"
                        value="catch_greater"
                        class="btn btn-stone"
                    >

                        Great
                        (
                        <?php echo
                            $game['inventory']['greater'];
                        ?>
                        )

                    </button>


                    <button
                        type="submit"
                        name="action"
                        value="catch_ancient"
                        class="btn btn-stone"
                    >

                        Ancient
                        (
                        <?php echo
                            $game['inventory']['ancient'];
                        ?>
                        )

                    </button>

                </div>


                <!-- ==========================================
                     MONSTER ROSTER
                     ========================================== -->

                <div class="section-title">
                    MONSTER ROSTER
                </div>


                <div class="roster-grid">


                    <?php for (
                        $i = 0;
                        $i < 8;
                        $i++
                    ): ?>


                        <?php if (
                            isset(
                                $game['player']['roster'][$i]
                            )
                        ): ?>


                            <?php

                            $m =
                                $game['player']['roster'][$i];

                            $hpP =
                                ($m['max_hp'] > 0)
                                ? (
                                    $m['hp']
                                    / $m['max_hp']
                                ) * 100
                                : 0;

                            $stats =
                                getXPStats(
                                    $m['xp'] ?? 0
                                );

                            ?>


                            <button
                                type="submit"
                                name="switch_to"
                                value="<?php echo $i; ?>"
                                class="
                                    roster-card
                                    <?php echo
                                        $i == $game['player']['active']
                                        ? 'active-slot'
                                        : '';
                                    ?>
                                "
                            >


                                <img
                                    src="images/monsters/<?php echo htmlspecialchars($m['image']); ?>"
                                    class="<?php echo
                                        $m['hp'] <= 0
                                        ? 'fainted-img'
                                        : '';
                                    ?>"
                                    alt="<?php echo htmlspecialchars($m['name']); ?>"
                                >


                                <div class="roster-info">


                                    <div class="roster-name">

                                        <?php echo htmlspecialchars(
                                            $m['name']
                                        ); ?>

                                    </div>


                                    <div class="roster-level">

                                        Lv.<?php echo $stats['level']; ?>


                                        <span
                                            class="<?php echo htmlspecialchars($m['type']); ?>"
                                        >

                                            <?php echo strtoupper(
                                                htmlspecialchars($m['type'])
                                            ); ?>

                                        </span>

                                    </div>


                                    <div class="hp-bar">

                                        <div
                                            class="hp-fill"
                                            style="
                                                width:<?php echo
                                                    max(
                                                        0,
                                                        min(
                                                            100,
                                                            $hpP
                                                        )
                                                    );
                                                ?>%;
                                            "
                                        ></div>

                                    </div>


                                </div>


                            </button>


                        <?php else: ?>


                            <div class="roster-card empty-slot">

                                EMPTY

                            </div>


                        <?php endif; ?>


                    <?php endfor; ?>


                </div>


            </div>


            <!-- ==============================================
                 BATTLE CODE
                 ============================================== -->

            <div class="battle-code-box">

                <span class="battle-code-label">
                    BATTLE CODE
                </span>


                <strong>

                    <?php echo htmlspecialchars(
                        $battleCode
                    ); ?>

                </strong>


                <small>

                    Save this code to continue
                    your game later.

                </small>

            </div>


            <!-- ==============================================
                 GOLD
                 ============================================== -->

            <div class="gold-box">

                GOLD AMOUNT:

                <?php echo number_format(
                    $game['player']['gold']
                ); ?>

            </div>


        </form>


    </div>


</main>


</body>

</html>
```
