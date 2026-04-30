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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #2c3e50; margin: 0; color: #333; }
        /* NAVIGATION FIX */
        .top-nav { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3498db; }
        .nav-links a { text-decoration: none; color: #2c3e50; font-weight: bold; margin-left: 20px; transition: 0.3s; }
        .nav-links a:hover { color: #3498db; }

        .main-container { display: flex; justify-content: center; gap: 20px; max-width: 1100px; margin: 30px auto; padding: 0 20px; }
        .battle-column, .ui-column { background: #bdc3c7; padding: 25px; border-radius: 8px; border: 4px solid #3498db; }
        .battle-column { flex: 1.2; } .ui-column { flex: 1; }

        /* TYPE LABELS */
        .type-badge { font-size: 0.7em; padding: 3px 8px; border-radius: 4px; color: white; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; display: inline-block; }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #27ae60; }

        .stage { display: flex; justify-content: space-between; background: #ecf0f1; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 2px solid #7f8c8d; }
        .monster-box img { width: 120px; height: 120px; object-fit: contain; background: white; border-radius: 4px; border: 1px solid #ccc; }
        
        .hp-bar { width: 100%; height: 12px; background: #95a5a6; border-radius: 6px; overflow: hidden; margin: 5px 0; }
        .hp-fill { height: 100%; background: #2ecc71; transition: width 0.4s ease; }
        .fainted-img { filter: grayscale(100%) opacity(0.5); }

        .btn { border: none; padding: 12px; cursor: pointer; font-weight: bold; color: white; border-radius: 4px; transition: 0.2s; }
        .btn-atk { background: #f39c12; border-bottom: 4px solid #d35400; }
        .btn-atk:active { border-bottom: 0; transform: translateY(4px); }
        .btn:disabled { background: #7f8c8d; cursor: not-allowed; border-bottom: none; }
        
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
                <div class="monster-box">
                    <span class="type-badge <?php echo $pm['type']; ?>"><?php echo $pm['type']; ?></span><br>
                    <img src="images/monsters/<?php echo $pm['image']; ?>" class="<?php echo $pm['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                    <div><strong><?php echo $pm['name']; ?></strong> (Lvl <?php echo $pmXP['level']; ?>)</div>
                    <div class="hp-bar"><div class="hp-fill" style="width:<?php echo $pmHP; ?>%"></div></div>
                    <small><?php echo "{$pm['hp']}/{$pm['max_hp']} HP"; ?></small>
                </div>

                <div style="align-self:center; font-size: 2em; font-weight: bold; color: #7f8c8d;">VS</div>

                <div class="monster-box">
                    <span class="type-badge <?php echo $em['type']; ?>"><?php echo $em['type']; ?></span><br>
                    <img src="images/monsters/<?php echo $em['image']; ?>">
                    <div><strong>Wild <?php echo $em['name']; ?></strong></div>
                    <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo $emHP; ?>%"></div></div>
                    <small><?php echo "{$em['hp']}/{$em['max_hp']} HP"; ?></small>
                </div>
            </div>

            <form method="post">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-atk" <?php echo $pm['hp'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo strtoupper($move['name']); ?><br><small>Pwr: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button name="action" value="run" class="btn" style="background:#e74c3c; width:100%; margin-top:10px;">RUN AWAY</button>
            </form>
        <?php else: ?>
            <div style="height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#ecf0f1; border-radius:8px;">
                <h3><?php echo $game['message']; ?></h3>
                <form method="post"><button name="action" value="start_battle" class="btn btn-atk" style="padding:15px 50px; font-size:1.2em;">EXPLORE GRASS</button></form>
            </div>
        <?php endif; ?>
    </div>

    <div class="ui-column">
        <form method="post">
            <div class="section-box" style="background:#ecf0f1; padding:15px; border-radius:8px; margin-bottom:15px;">
                <div style="font-weight:bold; margin-bottom:10px; border-bottom:1px solid #ccc;">ITEMS & CAPTURE</div>
                <div class="grid-3" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:5px;">
                    <button name="action" value="use_pot_basic" class="btn" style="background:#9b59b6; font-size:0.8em;">Pot (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                    <button name="action" value="catch_basic" class="btn" style="background:#2ecc71; font-size:0.8em;">Stone (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                </div>
            </div>

            <div class="section-box" style="background:#ecf0f1; padding:15px; border-radius:8px;">
                <div style="font-weight:bold; margin-bottom:10px; border-bottom:1px solid #ccc;">MONSTER ROSTER</div>
                <div class="roster-grid">
                    <?php for($i=0; $i<8; $i++): ?>
                        <?php if(isset($game['player']['roster'][$i])): 
                            $m = $game['player']['roster'][$i];
                            $hpP = ($m['hp'] / $m['max_hp']) * 100;
                        ?>
                            <button name="switch_to" value="<?php echo $i; ?>" class="roster-slot <?php echo $i == $game['player']['active'] ? 'active-slot' : ''; ?>">
                                <img src="images/monsters/<?php echo $m['image']; ?>" style="width:30px; height:30px;" class="<?php echo $m['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                                <div class="hp-bar" style="height:4px;"><div class="hp-fill" style="width:<?php echo $hpP; ?>%"></div></div>
                            </button>
                        <?php else: ?>
                            <div class="roster-slot" style="color:#bdc3c7;">---</div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <div style="background:#2c3e50; color:white; padding:10px; border-radius:8px; margin-top:15px; text-align:center; font-weight:bold;">
                GOLD: <?php echo $game['player']['gold']; ?>
            </div>
        </form>
    </div>
</div>
</body>
</html>