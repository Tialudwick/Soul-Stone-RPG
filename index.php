<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
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

// --- HANDLE ACTIONS ---
if ($game['currentBattle']) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    if ($pm['hp'] <= 0) {
        $game['message'] = "Your monster has fainted! Switch to another or use a potion.";
    } elseif (str_starts_with($action, "attack_")) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * getTypeMultiplier($move['type'], $em['type']));
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            $em['hp'] = 0; 
            $lvlMsg = gainXP($pm, 80); 
            $game['player']['gold'] += rand(50, 100);
            $game['message'] = "Victory! " . ($lvlMsg ?: "");
            $game['currentBattle'] = null;
        } else {
            $eMove = $em['moves'][rand(0,3)];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
            if ($pm['hp'] < 0) { $pm['hp'] = 0; } 
        }
    }
}

// --- ITEM & CATCH LOGIC ---
if (str_starts_with($action, "use_pot_")) {
    $type = str_replace("use_pot_", "", $action);
    $healAmt = ["basic" => 30, "greater" => 80, "ancient" => 200][$type];
    if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
        $pm = &$game['player']['roster'][$game['player']['active']];
        $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $healAmt);
        $game['inventory'][$type."_potion"]--;
        $game['message'] = "Used " . ucfirst($type) . " Potion!";
    }
}

if (str_starts_with($action, "catch_")) {
    $stoneType = str_replace("catch_", "", $action);
    
    if (($game['inventory'][$stoneType] ?? 0) > 0) {
        $game['inventory'][$stoneType]--;
        
        $em = &$game['currentBattle'];
        $hpPercent = $em['hp'] / $em['max_hp'];
        $stonePower = ["basic" => 0.3, "greater" => 0.6, "ancient" => 1.0][$stoneType];
        
        $catchChance = (1 - $hpPercent) + $stonePower;
        $roll = rand(0, 100) / 100;

        if ($roll < $catchChance) {
            if (count($game['player']['roster']) < 8) {
                $newMonster = $em;
                $newMonster['hp'] = $newMonster['max_hp'];
                $game['player']['roster'][] = $newMonster;
                $game['message'] = "Gotcha! {$em['name']} was caught!";
                $game['currentBattle'] = null;
            } else {
                $game['message'] = "Roster full! Could not keep {$em['name']}.";
                $game['currentBattle'] = null;
            }
        } else {
            $game['message'] = "Oh no! The {$em['name']} broke free!";
            $eMove = $em['moves'][rand(0,3)];
            $pm = &$game['player']['roster'][$game['player']['active']];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
            if ($pm['hp'] < 0) { $pm['hp'] = 0; }
        }
    } else {
        $game['message'] = "You don't have any $stoneType stones!";
    }
}

if ($action === "start_battle") { $game['currentBattle'] = spawnMonster($allMonsters); }
if (isset($_POST['switch_to'])) { $game['player']['active'] = (int)$_POST['switch_to']; }
if ($action === "run") { $game['currentBattle'] = null; }

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
    <div class="logo"><strong>SOUL STONE RPG</strong></div>
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

            <div class="gold-box">
                GOLD AMOUNT: <?php echo number_format($game['player']['gold']); ?>
            </div>
        </form>
    </div>
</div>

</body>
</html>