<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- DATA REPAIR & SYNC ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        // Ensure type and moves exist for display
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

    // FAINTED LOGIC: Prevent attacking if HP is 0
    if ($pm['hp'] <= 0) {
        $game['message'] = "{$pm['name']} is fainted! Use a potion or switch monsters.";
    } elseif (str_starts_with($action, "attack_")) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        
        // Damage Calculation
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * getTypeMultiplier($move['type'], $em['type']));
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            $em['hp'] = 0; 
            // AUTO-HEAL: Leveling up now fully restores HP
            $lvlMsg = gainXP($pm, 80); 
            $game['player']['gold'] += rand(50, 100);
            $game['message'] = "Victory! " . ($lvlMsg ?: "");
            $game['currentBattle'] = null;
        } else {
            // Enemy Counter-Attack
            $eMove = $em['moves'][rand(0,3)];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
            if ($pm['hp'] < 0) { $pm['hp'] = 0; } 
        }
    }
}

// Item Usage
if (str_starts_with($action, "use_pot_")) {
    $type = str_replace("use_pot_", "", $action);
    $healAmt = ["basic" => 30, "greater" => 80, "ancient" => 200][$type];
    if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
        $pm = &$game['player']['roster'][$game['player']['active']];
        $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $healAmt);
        $game['inventory'][$type."_potion"]--;
    }
}

if ($action === "start_battle") { $game['currentBattle'] = spawnMonster($allMonsters); }
if (isset($_POST['switch_to'])) { $game['player']['active'] = (int)$_POST['switch_to']; }
if ($action === "run") { $game['currentBattle'] = null; }

saveGame($game);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a1a1d; margin: 0; color: #333; }
        
        /* NAVIGATION */
        .top-nav { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3498db; }
        .nav-links a { text-decoration: none; color: #2c3e50; font-weight: bold; margin-left: 20px; transition: 0.3s; }
        .nav-links a:hover { color: #3498db; }

        .main-container { display: flex; justify-content: center; gap: 20px; max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .battle-column { flex: 1.5; background: #2c3e50; padding: 25px; border-radius: 12px; border: 4px solid #34495e; box-shadow: inset 0 0 50px rgba(0,0,0,0.5); }
        .ui-column { flex: 0.8; background: #bdc3c7; padding: 20px; border-radius: 8px; border: 4px solid #3498db; height: fit-content; }

        /* THE BATTLE STAGE */
        .stage { display: flex; justify-content: space-around; align-items: center; padding: 40px 10px; margin-bottom: 20px; perspective: 1000px; }

        /* CARD DESIGN (Inspired by Reference) */
        .monster-card {
            width: 220px;
            background: #f4e4bc; /* Parchment Background */
            border: 8px solid #3d2b1f; /* Dark Wood Border */
            border-radius: 10px;
            padding: 12px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.6);
            position: relative;
            color: #3d2b1f;
        }
        
        .card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #3d2b1f; padding-bottom: 5px; margin-bottom: 10px; font-weight: bold; text-transform: uppercase; font-size: 0.9em; }
        
        .image-well { background: #fff; border: 3px solid #3d2b1f; height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px; box-shadow: inset 0 0 10px rgba(0,0,0,0.2); }
        .image-well img { width: 85%; height: auto; object-fit: contain; transition: 0.3s; }
        .fainted-img { filter: grayscale(100%) sepia(50%) opacity(0.4); }

        .card-footer { background: rgba(0,0,0,0.05); padding: 8px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1); }

        /* TYPE STICKER */
        .type-sticker { position: absolute; top: -15px; right: -15px; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.65em; border: 3px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.3); z-index: 10; text-transform: uppercase; }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #27ae60; }

        .vs-emblem { font-size: 3.5em; font-weight: 900; color: #f1c40f; text-shadow: 2px 2px 0 #000; font-style: italic; }

        /* HP BARS */
        .hp-bar { width: 100%; height: 10px; background: #95a5a6; border-radius: 5px; overflow: hidden; margin: 5px 0; border: 1px solid #3d2b1f; }
        .hp-fill { height: 100%; background: #2ecc71; transition: width 0.4s ease; }

        /* BUTTONS */
        .btn { border: none; padding: 12px; cursor: pointer; font-weight: bold; color: white; border-radius: 4px; transition: 0.2s; }
        .btn-atk { background: #f39c12; border-bottom: 4px solid #d35400; }
        .btn-atk:active { border-bottom: 0; transform: translateY(4px); }
        .btn:disabled { background: #7f8c8d; cursor: not-allowed; border-bottom: none; }
        
        /* SIDEBAR COMPONENTS */
        .roster-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .roster-slot { background: white; border: 2px solid #ddd; padding: 8px; border-radius: 6px; cursor: pointer; text-align: center; }
        .active-slot { border-color: #3498db; background: #ebf5fb; box-shadow: 0 0 10px rgba(52, 152, 219, 0.5); }
    </style>
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
            $pmXP = getXPStats($pm['xp']); 
            $pmHP = ($pm['hp'] / $pm['max_hp']) * 100;
            $emHP = ($em['hp'] / $em['max_hp']) * 100;
        ?>
            <div class="stage">
                <div class="monster-card">
                    <div class="type-sticker <?php echo $pm['type']; ?>"><?php echo $pm['type']; ?></div>
                    <div class="card-header">
                        <span><?php echo $pm['name']; ?></span>
                        <span>Lv.<?php echo $pmXP['level']; ?></span>
                    </div>
                    <div class="image-well">
                        <img src="images/monsters/<?php echo $pm['image']; ?>" class="<?php echo $pm['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                    </div>
                    <div class="card-footer">
                        <div class="hp-bar"><div class="hp-fill" style="width:<?php echo $pmHP; ?>%"></div></div>
                        <div style="display:flex; justify-content:space-between; font-size:0.75em; font-weight:bold;">
                            <span>HP: <?php echo $pm['hp']; ?>/<?php echo $pm['max_hp']; ?></span>
                            <span>ATK: <?php echo $pm['attack']; ?></span>
                        </div>
                    </div>
                </div>

                <div class="vs-emblem">VS</div>

                <div class="monster-card" style="border-color: #c0392b;">
                    <div class="type-sticker <?php echo $em['type']; ?>"><?php echo $em['type']; ?></div>
                    <div class="card-header">
                        <span>Wild <?php echo $em['name']; ?></span>
                    </div>
                    <div class="image-well">
                        <img src="images/monsters/<?php echo $em['image']; ?>">
                    </div>
                    <div class="card-footer">
                        <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo $emHP; ?>%"></div></div>
                        <div style="text-align:center; font-size:0.75em; font-weight:bold;">
                            <span>HP: <?php echo $em['hp']; ?>/<?php echo $em['max_hp']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="post" style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-atk" <?php echo $pm['hp'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo strtoupper($move['name']); ?><br>
                            <small style="opacity:0.8">POWER: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button name="action" value="run" class="btn" style="background:#e74c3c; width:100%; margin-top:10px; border-bottom: 4px solid #b03a2e;">RUN AWAY</button>
            </form>
            <div style="color: #fff; text-align: center; margin-top: 15px; font-weight: bold; text-shadow: 1px 1px 2px #000;">
                <?php echo $game['message']; ?>
            </div>

        <?php else: ?>
            <div style="height:450px; display:flex; flex-direction:column; align-items:center; justify-content:center; background: rgba(255,255,255,0.05); border-radius:12px; border: 2px dashed #7f8c8d;">
                <h2 style="color: #fff; margin-bottom: 20px;"><?php echo $game['message'] ?: "The wilderness awaits..."; ?></h2>
                <form method="post">
                    <button name="action" value="start_battle" class="btn btn-atk" style="padding:20px 60px; font-size:1.4em;">EXPLORE TALL GRASS</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div class="ui-column">
        <form method="post">
            <div class="section-box" style="background:#ecf0f1; padding:15px; border-radius:8px; margin-bottom:15px; border: 1px solid #95a5a6;">
                <div style="font-weight:bold; margin-bottom:10px; border-bottom:1px solid #ccc; color: #2c3e50;">INVENTORY</div>
                <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:8px;">
                    <button name="action" value="use_pot_basic" class="btn" style="background:#8e44ad; font-size:0.75em;">Pot (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                    <button name="action" value="catch_basic" class="btn" style="background:#27ae60; font-size:0.75em;">Stone (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                </div>
            </div>

            <div class="section-box" style="background:#ecf0f1; padding:15px; border-radius:8px; border: 1px solid #95a5a6;">
                <div style="font-weight:bold; margin-bottom:10px; border-bottom:1px solid #ccc; color: #2c3e50;">MY SQUAD</div>
                <div class="roster-grid">
                    <?php for($i=0; $i<8; $i++): ?>
                        <?php if(isset($game['player']['roster'][$i])): 
                            $m = $game['player']['roster'][$i];
                            $hpP = ($m['hp'] / $m['max_hp']) * 100;
                        ?>
                            <button name="switch_to" value="<?php echo $i; ?>" class="roster-slot <?php echo $i == $game['player']['active'] ? 'active-slot' : ''; ?>">
                                <img src="images/monsters/<?php echo $m['image']; ?>" style="width:30px; height:30px;" class="<?php echo $m['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                                <div class="hp-bar" style="height:4px; margin-top:2px;"><div class="hp-fill" style="width:<?php echo $hpP; ?>%"></div></div>
                            </button>
                        <?php else: ?>
                            <div class="roster-slot" style="color:#bdc3c7; height:46px; line-height:46px;">-</div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>

            <div style="background:#f1c40f; color:#3d2b1f; padding:12px; border-radius:8px; margin-top:15px; text-align:center; font-weight:900; border: 2px solid #d4ac0d; box-shadow: 0 4px 0 #b7950b;">
                💰 <?php echo number_format($game['player']['gold']); ?> GOLD
            </div>
        </form>
    </div>
</div>
</body>
</html>