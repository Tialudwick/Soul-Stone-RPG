<?php

session_start();

include "functions.php";
include "monsters.php";


// ============================================================
// LOAD CURRENT BATTLE CODE
// ============================================================

$battleCode = $_SESSION['battle_code'] ?? null;

if (!$battleCode) {

    header('Location: start.php');
    exit;
}


// ============================================================
// LOAD SAVED GAME
// ============================================================

$game = loadBattleSave($battleCode);

if ($game === null) {

    unset($_SESSION['battle_code']);

    header('Location: start.php');
    exit;
}


$action = $_POST['action'] ?? null;

// --- DATA REPAIR & SYNC ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        if (!isset($m['type'])) {
            foreach ($allMonsters as $ref) { 
                if ($ref['name'] === $m['name']) { $m['type'] = $ref['type']; break; } 
            }
        }
        if (!isset($m['moves']) || empty($m['moves'])) { 
            $m['moves'] = $moves[$m['type'] ?? 'earth']; 
        }
    }
}

// ============================================================
// BATTLE ACTION SYSTEM
// ============================================================

$action = $_POST['action'] ?? '';

if ($game['currentBattle']) {

    $activeIndex = $game['player']['active'];
    $pm = &$game['player']['roster'][$activeIndex];
    $em = &$game['currentBattle'];

    // --------------------------------------------------------
    // Helper: Enemy attacks player
    // --------------------------------------------------------
    function enemyTurn(&$pm, &$em) {

        if ($em['hp'] <= 0 || $pm['hp'] <= 0) {
            return;
        }

        // Pick a random enemy move
        $moveIndex = rand(0, count($em['moves']) - 1);
        $move = $em['moves'][$moveIndex];

        // Calculate damage
        $baseDamage = rand(
            max(1, $em['attack'] - 2),
            $em['attack'] + 2
        );

        $multiplier = getTypeMultiplier(
            $move['type'],
            $pm['type']
        );

        $damage = max(
            1,
            floor($baseDamage * $move['power'] * $multiplier)
        );

        $pm['hp'] -= $damage;

        if ($pm['hp'] < 0) {
            $pm['hp'] = 0;
        }

        return [
            'damage' => $damage,
            'move' => $move['name']
        ];
    }


    // --------------------------------------------------------
    // ATTACK
    // --------------------------------------------------------
    if (str_starts_with($action, 'attack_')) {

        if ($pm['hp'] <= 0) {

            $game['message'] =
                "Your {$pm['name']} has fainted! Choose another monster.";

        } else {

            $idx = (int)str_replace('attack_', '', $action);

            if (!isset($pm['moves'][$idx])) {

                $game['message'] = "Invalid move.";

            } else {

                $move = $pm['moves'][$idx];

                // Player damage
                $baseDamage = rand(
                    max(1, $pm['attack'] - 2),
                    $pm['attack'] + 2
                );

                $multiplier = getTypeMultiplier(
                    $move['type'],
                    $em['type']
                );

                $damage = max(
                    1,
                    floor(
                        $baseDamage *
                        $move['power'] *
                        $multiplier
                    )
                );

                $em['hp'] -= $damage;

                if ($em['hp'] < 0) {
                    $em['hp'] = 0;
                }


                // ------------------------------------------------
                // ENEMY DEFEATED
                // ------------------------------------------------
                if ($em['hp'] <= 0) {

                    $xpMessage = gainXP($pm, 80);

                    $goldReward = rand(50, 100);

                    $game['player']['gold'] += $goldReward;

                    $game['message'] =
                        "{$pm['name']} used {$move['name']}! "
                        . "Dealt {$damage} damage! "
                        . "Wild {$em['name']} fainted! "
                        . "+{$goldReward} gold. "
                        . ($xpMessage ?: '');

                    $game['currentBattle'] = null;

                } else {

                    // Enemy counter attack
                    $enemyResult = enemyTurn($pm, $em);

                    $game['message'] =
                        "{$pm['name']} used {$move['name']} "
                        . "and dealt {$damage} damage! ";

                    if ($enemyResult) {

                        $game['message'] .=
                            "Wild {$em['name']} used "
                            . "{$enemyResult['move']} "
                            . "and dealt "
                            . "{$enemyResult['damage']} damage!";
                    }


                    // Player fainted
                    if ($pm['hp'] <= 0) {

                        $game['message'] .=
                            " {$pm['name']} fainted! Choose another monster.";
                    }
                }
            }
        }
    }


    // --------------------------------------------------------
    // POTION
    // --------------------------------------------------------
    elseif (str_starts_with($action, 'use_pot_')) {

        $type = str_replace('use_pot_', '', $action);

        $healing = [
            'basic' => 30,
            'greater' => 80,
            'ancient' => 200
        ];

        $inventoryKey = $type . '_potion';

        if (!isset($healing[$type])) {

            $game['message'] = "Invalid potion.";

        } elseif (($game['inventory'][$inventoryKey] ?? 0) <= 0) {

            $game['message'] =
                "You don't have any {$type} potions.";

        } elseif ($pm['hp'] >= $pm['max_hp']) {

            $game['message'] =
                "{$pm['name']} already has full HP.";

        } elseif ($pm['hp'] <= 0) {

            $game['message'] =
                "{$pm['name']} has fainted. Switch monsters first.";

        } else {

            $oldHP = $pm['hp'];

            $pm['hp'] = min(
                $pm['max_hp'],
                $pm['hp'] + $healing[$type]
            );

            $actualHeal = $pm['hp'] - $oldHP;

            $game['inventory'][$inventoryKey]--;

            $game['message'] =
                "Used a " . ucfirst($type) .
                " Potion! {$pm['name']} recovered "
                . "{$actualHeal} HP.";

            // Enemy gets a turn
            $enemyResult = enemyTurn($pm, $em);

            if ($enemyResult) {

                $game['message'] .=
                    " Wild {$em['name']} used "
                    . "{$enemyResult['move']} and dealt "
                    . "{$enemyResult['damage']} damage!";
            }
        }
    }


    // --------------------------------------------------------
    // SOUL STONE / CATCH
    // --------------------------------------------------------
    elseif (str_starts_with($action, 'catch_')) {

        $stoneType = str_replace('catch_', '', $action);

        $stonePower = [
            'basic' => 0.30,
            'greater' => 0.60,
            'ancient' => 1.00
        ];

        if (!isset($stonePower[$stoneType])) {

            $game['message'] = "Invalid Soul Stone.";

        } elseif (($game['inventory'][$stoneType] ?? 0) <= 0) {

            $game['message'] =
                "You don't have any {$stoneType} Soul Stones.";

        } else {

            $game['inventory'][$stoneType]--;

            $hpPercent =
                $em['hp'] / $em['max_hp'];

            /*
             * Lower enemy HP = higher catch chance
             */
            $catchChance =
                (1 - $hpPercent) * 0.70
                + $stonePower[$stoneType];

            // Never exceed 95%
            $catchChance =
                min(0.95, $catchChance);

            $roll = mt_rand(1, 100) / 100;

            if ($roll <= $catchChance) {

                // Successful capture
                if (count($game['player']['roster']) < 8) {

                    $newMonster = $em;

                    $newMonster['hp'] =
                        $newMonster['max_hp'];

                    $newMonster['xp'] = 0;

                    $game['player']['roster'][] =
                        $newMonster;

                    $game['message'] =
                        "Gotcha! {$em['name']} was captured!";

                    $game['currentBattle'] = null;

                } else {

                    $game['message'] =
                        "The Soul Stone worked, but your roster "
                        . "is full!";
                }

            } else {

                // Capture failed
                $game['message'] =
                    "{$em['name']} broke free!";

                // Enemy gets attack
                $enemyResult =
                    enemyTurn($pm, $em);

                if ($enemyResult) {

                    $game['message'] .=
                        " {$em['name']} used "
                        . "{$enemyResult['move']} and dealt "
                        . "{$enemyResult['damage']} damage!";
                }

                if ($pm['hp'] <= 0) {

                    $game['message'] .=
                        " {$pm['name']} fainted!";
                }
            }
        }
    }


    // --------------------------------------------------------
    // SWITCH MONSTER
    // --------------------------------------------------------
    elseif ($action === 'switch') {

        $newIndex =
            isset($_POST['monster_index'])
            ? (int)$_POST['monster_index']
            : -1;

        if (!isset($game['player']['roster'][$newIndex])) {

            $game['message'] =
                "That monster does not exist.";

        } elseif (
            $newIndex === $activeIndex
        ) {

            $game['message'] =
                "{$pm['name']} is already active.";

        } elseif (
            $game['player']['roster'][$newIndex]['hp'] <= 0
        ) {

            $game['message'] =
                "That monster has fainted.";

        } else {

            $game['player']['active'] =
                $newIndex;

            $newMonster =
                &$game['player']['roster'][$newIndex];

            $game['message'] =
                "Go, {$newMonster['name']}!";

            // Enemy gets a free attack
            $enemyResult =
                enemyTurn($newMonster, $em);

            if ($enemyResult) {

                $game['message'] .=
                    " Wild {$em['name']} used "
                    . "{$enemyResult['move']} and dealt "
                    . "{$enemyResult['damage']} damage!";
            }
        }
    }


    // --------------------------------------------------------
    // RUN
    // --------------------------------------------------------
    elseif ($action === 'run') {

        $game['currentBattle'] = null;

        $game['message'] =
            "You escaped safely!";
    }
}


// ============================================================
// START NEW BATTLE
// ============================================================

if ($action === 'start_battle') {

    $game['currentBattle'] =
        spawnMonster($allMonsters);

    $game['message'] =
        "A wild {$game['currentBattle']['name']} appeared!";
}


// ============================================================
// SAVE GAME
// ============================================================

saveGame($game);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soul Stone RPG</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="top-nav">
    <div class="logo">
    <a href="start.php">
        <strong>SOUL STONE RPG</strong>
    </a>
</div>
    <div class="nav-links">
        <a href="bestiary.php">BESTIARY</a>
        <a href="shop.php">SHOP</a>
        <a href="index.php">HOME</a>
    </div>
</div>

<div class="main-container">
    <div class="battle-column">
        <?php if($game['currentBattle']): 
            $pm = $game['player']['roster'][$game['player']['active']];
            $em = $game['currentBattle'];
            $pmXP = getXPStats($pm['xp'] ?? 0); 
            $pmHP = ($pm['hp'] / $pm['max_hp']) * 100;
            $emHP = ($em['hp'] / $em['max_hp']) * 100;
        ?>
            <div class="stage">
                <!-- Player Card -->
                <div class="monster-card">
                    <div class="type-badge <?php echo $pm['type']; ?>"><?php echo strtoupper($pm['type']); ?></div>
                    <div class="card-header">
                        <span><?php echo $pm['name']; ?></span> 
                        <span>Lv.<?php echo $pmXP['level']; ?></span>
                    </div>
                    <div class="image-well">
                        <img src="images/monsters/<?php echo $pm['image']; ?>" class="<?php echo $pm['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                    </div>
                    <div class="hp-bar">
                        <div class="hp-fill" style="width:<?php echo $pmHP; ?>%"></div>
                    </div>
                    
                    <div style="margin-top: 3px;">
                        <div class="hp-bar" style="height: 4px; background: #34495e;">
                            <div class="hp-fill" style="width:<?php echo $pmXP['percent']; ?>%; background: #3498db;"></div>
                        </div>
                        <div style="font-size: 0.6em; display: flex; justify-content: space-between; color: #3d2b1f; font-weight: bold; margin-top: 2px;">
                            <span>XP: <?php echo $pm['xp']; ?> / <?php echo $pmXP['next_lvl']; ?></span>
                            <span><?php echo floor($pmXP['percent']); ?>%</span>
                        </div>
                    </div>
                    <div class="card-stats">
                        HP: <?php echo $pm['hp']."/".$pm['max_hp']; ?> | ATK: <?php echo $pm['attack']; ?>
                    </div>
                </div>

                <!-- Enemy Card -->
                <div class="monster-card">
                    <div class="type-badge <?php echo $em['type']; ?>"><?php echo strtoupper($em['type']); ?></div>
                    <div class="card-header">
                        <span>Wild <?php echo $em['name']; ?></span>
                    </div>
                    <div class="image-well">
                        <img src="images/monsters/<?php echo $em['image']; ?>">
                    </div>
                    <div class="hp-bar">
                        <div class="hp-fill hp-enemy" style="width:<?php echo $emHP; ?>%"></div>
                    </div>
                    <div class="card-stats">WILD BEAST</div>
                </div>
            </div>

            <form method="post">
                <div class="moves-grid">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-move" <?php echo $pm['hp'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo strtoupper($move['name']); ?><br><small style="font-size: 0.8em;">PWR: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button name="action" value="run" class="btn btn-run">RUN AWAY</button>
            </form>
            <div style="color:white; text-align:center; font-size: 0.85em; font-weight:bold; min-height: 18px;">
                <?php echo $game['message']; ?>
            </div>

        <?php else: ?>
            <div class="explore-screen">
                <h3 style="color:white; margin-bottom: 15px;"><?php echo $game['message'] ?: "Explore the world of Soul Stones!"; ?></h3>
                <form method="post">
                    <button name="action" value="start_battle" class="btn" style="background:#27ae60; padding:15px 40px; font-size:1.3em;">EXPLORE GRASS</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="ui-column">
        <form method="post" style="display: flex; flex-direction: column; height: 100%; justify-content: space-between;">
            <div>
                <div class="section-title">POTIONS</div>
                <div class="btn-grid">
                    <button name="action" value="use_pot_basic" class="btn btn-pot">Basic (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                    <button name="action" value="use_pot_greater" class="btn btn-pot">Great (<?php echo $game['inventory']['greater_potion'] ?? 0; ?>)</button>
                    <button name="action" value="use_pot_ancient" class="btn btn-pot">Ancient (<?php echo $game['inventory']['ancient_potion'] ?? 0; ?>)</button>
                </div>

                <div class="section-title">SOUL STONES</div>
                <div class="btn-grid">
                    <button name="action" value="catch_basic" class="btn btn-stone">
                        Basic (<?php echo $game['inventory']['basic'] ?? 0; ?>)
                    </button>
                    <button name="action" value="catch_greater" class="btn btn-stone">
                        Great (<?php echo $game['inventory']['greater'] ?? 0; ?>)
                    </button>
                    <button name="action" value="catch_ancient" class="btn btn-stone">
                        Ancient (<?php echo $game['inventory']['ancient'] ?? 0; ?>)
                    </button>
                </div>

                <div class="section-title">MONSTER ROSTER</div>
                <div class="roster-grid">
                    <?php for($i=0; $i<8; $i++): ?>
                        <?php if(isset($game['player']['roster'][$i])): 
                            $m = $game['player']['roster'][$i];
                            $hpP = ($m['hp'] / $m['max_hp']) * 100;
                            $stats = getXPStats($m['xp'] ?? 0);
                        ?>
                            <button name="switch_to" value="<?php echo $i; ?>" class="roster-card <?php echo $i == $game['player']['active'] ? 'active-slot' : ''; ?>">
                                <img src="images/monsters/<?php echo $m['image']; ?>" class="<?php echo $m['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                                <div class="roster-info">
                                    <div class="roster-name"><?php echo $m['name']; ?></div>
                                    <div style="font-size: 0.65em; color: #7f8c8d;">
                                        Lv.<?php echo $stats['level']; ?> 
                                        <span class="<?php echo $m['type']; ?>" style="padding: 1px 3px; border-radius: 2px; color: white;"><?php echo $m['type']; ?></span>
                                    </div>
                                    <div class="hp-bar" style="height:4px; margin-top:2px;">
                                        <div class="hp-fill" style="width:<?php echo $hpP; ?>%"></div>
                                    </div>
                                </div>
                            </button>
                        <?php else: ?>
                            <div class="roster-card" style="justify-content:center; color:#bdc3c7; border-style:dashed; font-size: 0.75em;">Empty</div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="battle-code-box">

                <span class="battle-code-label">BATTLE CODE</span>
                    <strong><?php echo htmlspecialchars($battleCode); ?></strong>
                    <small>Save this code to continue your game later.</small></div>
            <div class="gold-box">
                GOLD AMOUNT: <?php echo number_format($game['player']['gold']); ?>
            </div>
        </form>
    </div>
</div>

</body>
</html>