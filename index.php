<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- DATA REPAIR ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        if (!isset($m['xp'])) { $m['xp'] = 0; } 
        // Logic to ensure every monster has a type
        if (!isset($m['type'])) {
            foreach ($allMonsters as $ref) { if ($ref['name'] === $m['name']) { $m['type'] = $ref['type']; break; } }
        }
        if (!isset($m['moves']) || empty($m['moves'])) { $m['moves'] = $moves[$m['type'] ?? 'earth']; }
    }
}

// --- HANDLE ACTIONS ---
if ($game['currentBattle']) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    if (str_starts_with($action, "attack_")) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * getTypeMultiplier($move['type'], $em['type']));
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            gainXP($pm, 80);
            $game['player']['gold'] += rand(50, 100);
            $game['message'] = "Victory!";
            $game['currentBattle'] = null;
        } else {
            $eMove = $em['moves'][rand(0,3)];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
        }
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
        body { font-family: sans-serif; background: #2c3e50; margin: 0; padding: 0; color: #333; }
        .top-nav { background: white; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #ddd; }
        .main-container { display: flex; justify-content: center; gap: 20px; max-width: 1100px; margin: 30px auto; }
        
        .battle-column { flex: 1.2; background: #bdc3c7; padding: 25px; border-radius: 5px; border: 4px solid #3498db; }
        .stage { display: flex; justify-content: space-between; background: #ecf0f1; padding: 30px; border-radius: 5px; margin-bottom: 20px; border: 2px solid #7f8c8d; }
        .monster-box { width: 160px; text-align: center; position: relative; }
        .monster-box img { width: 140px; height: 140px; background: #fff; border: 1px solid #333; }

        /* Type Tags Styling */
        .type-tag { font-size: 0.6em; background: #333; color: #fff; padding: 2px 6px; border-radius: 3px; text-transform: uppercase; display: inline-block; margin-bottom: 4px; }
        .type-fire { background: #e67e22; } .type-water { background: #3498db; } .type-earth { background: #27ae60; }

        .ui-column { flex: 1; background: #bdc3c7; padding: 25px; border-radius: 5px; }
        .section-box { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .roster-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }

        .hp-bar { width: 100%; height: 8px; background: #7f8c8d; margin-top: 5px; }
        .hp-fill { height: 100%; background: #2ecc71; }
        .xp-bar { width: 100%; height: 4px; background: #dfe6e9; margin-top: 2px; }
        .xp-fill { height: 100%; background: #3498db; }

        .btn { border: none; padding: 12px 5px; cursor: pointer; font-weight: bold; color: white; border-radius: 2px; }
        .btn-atk { background: #f39c12; border-bottom: 3px solid #d35400; }
        .btn-pot { background: #9b59b6; } .btn-pot.greater { background: #3498db; } .btn-pot.ancient { background: #2ecc71; }
        .btn-stone { background: #9b59b6; } .btn-stone.greater { background: #3498db; } .btn-stone.ancient { background: #2ecc71; }
        
        .roster-slot { background: #fff; border: 1px solid #7f8c8d; padding: 5px; min-height: 105px; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .active-slot { border: 3px solid #3498db; }
        .gold-display { background: #ddd; padding: 10px; text-align: center; font-weight: bold; margin-top: 10px; }
    </style>
</head>
<body>

<div class="top-nav">
    <div class="logo">SOUL STONE LOGO</div>
    <div class="nav-links">BESTIARY / SHOP / HOME</div>
</div>

<div class="main-container">
    <div class="battle-column">
        <?php if($game['currentBattle']): 
            $pm = $game['player']['roster'][$game['player']['active']];
            $em = $game['currentBattle'];
            $pmXP = getXPStats($pm['xp']);
        ?>
            <div class="stage">
                <div class="monster-box">
                    <div class="type-tag type-<?php echo $pm['type']; ?>"><?php echo $pm['type']; ?></div><br>
                    <small>Lvl <?php echo $pmXP['level']; ?></small>
                    <img src="images/monsters/<?php echo $pm['image']; ?>">
                    <strong><?php echo $pm['name']; ?></strong>
                    <div class="hp-bar"><div class="hp-fill" style="width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div></div>
                    <div class="xp-bar"><div class="xp-fill" style="width:<?php echo $pmXP['percent']; ?>%"></div></div>
                </div>
                <div style="align-self:center; font-weight:bold;">VS</div>
                <div class="monster-box">
                    <div class="type-tag type-<?php echo $em['type']; ?>"><?php echo $em['type']; ?></div><br>
                    <small><?php echo ucfirst($em['rarity']); ?></small>
                    <img src="images/monsters/<?php echo $em['image']; ?>">
                    <strong><?php echo $em['name']; ?></strong>
                    <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div></div>
                </div>
            </div>
            <form method="post">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-atk"><?php echo strtoupper($move['name']); ?></button>
                    <?php endforeach; ?>
                </div>
                <button name="action" value="run" class="btn" style="background:#e74c3c; width:100%; margin-top:10px; padding:15px;">RUN</button>
            </form>
        <?php else: ?>
            <div style="height:350px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                <p><strong><?php echo $game['message']; ?></strong></p>
                <form method="post"><button name="action" value="start_battle" class="btn btn-atk" style="padding:20px 40px;">EXPLORE GRASS</button></form>
            </div>
        <?php endif; ?>
    </div>

    <div class="ui-column">
        <form method="post">
            <div class="section-box">
                <div style="text-align:center; font-weight:bold; margin-bottom:5px;">POTIONS</div>
                <div class="grid-3">
                    <button name="action" value="use_pot_basic" class="btn btn-pot">Basic (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                    <button name="action" value="use_pot_greater" class="btn btn-pot greater">Greater (<?php echo $game['inventory']['greater_potion'] ?? 0; ?>)</button>
                    <button name="action" value="use_pot_ancient" class="btn btn-pot ancient">Ancient (<?php echo $game['inventory']['ancient_potion'] ?? 0; ?>)</button>
                </div>
            </div>

            <div class="section-box">
                <div style="text-align:center; font-weight:bold; margin-bottom:5px;">SOUL STONES</div>
                <div class="grid-3">
                    <button name="action" value="catch_basic" class="btn btn-stone">Basic (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                    <button name="action" value="catch_greater" class="btn btn-stone greater">Greater (<?php echo $game['inventory']['greater'] ?? 0; ?>)</button>
                    <button name="action" value="catch_ancient" class="btn btn-stone ancient">Ancient (<?php echo $game['inventory']['ancient'] ?? 0; ?>)</button>
                </div>
            </div>

            <div class="section-box">
                <div style="text-align:center; font-weight:bold; margin-bottom:5px;">MONSTER ROSTER</div>
                <div class="roster-grid">
                    <?php for($i=0; $i<8; $i++): ?>
                        <?php if(isset($game['player']['roster'][$i])): 
                            $m = $game['player']['roster'][$i];
                            $mXP = getXPStats($m['xp']);
                        ?>
                            <button name="switch_to" value="<?php echo $i; ?>" class="roster-slot <?php echo ($i == $game['player']['active']) ? 'active-slot' : ''; ?>">
                                <div class="type-tag type-<?php echo $m['type']; ?>" style="font-size:0.5em;"><?php echo $m['type']; ?></div>
                                <img src="images/monsters/<?php echo $m['image']; ?>" style="width:30px; height:30px; object-fit:contain;">
                                <div style="font-size:0.6em; font-weight:bold;"><?php echo $m['name']; ?></div>
                                <div class="hp-bar" style="height:3px;"><div class="hp-fill" style="width:<?php echo ($m['hp']/$m['max_hp'])*100; ?>%"></div></div>
                                <div class="xp-bar"><div class="xp-fill" style="width:<?php echo $mXP['percent']; ?>%"></div></div>
                            </button>
                        <?php else: ?>
                            <div class="roster-slot" style="color:#999; font-size:0.7em;">Empty</div>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="gold-display">GOLD AMOUNT: <?php echo $game['player']['gold']; ?></div>
        </form>
    </div>
</div>
</body>
</html>