<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- DATA REPAIR: Fixes missing Type/Moves/XP in old saves ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        // Ensure every monster has a type and moves
        if (!isset($m['type'])) {
            foreach ($allMonsters as $ref) { 
                if ($ref['name'] === $m['name']) { $m['type'] = $ref['type']; break; } 
            }
        }
        if (!isset($m['moves']) || empty($m['moves'])) { $m['moves'] = $moves[$m['type'] ?? 'earth']; }
        if (!isset($m['xp'])) { $m['xp'] = 0; }
        if (!isset($m['rarity'])) { $m['rarity'] = 'basic'; }
    }
    saveGame($game);
}

// --- HANDLE ACTIONS ---
if ($game['currentBattle']) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    if (str_starts_with($action, "attack_") && $pm['hp'] > 0) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        $mult = getTypeMultiplier($move['type'], $em['type']);
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * $mult);
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            $xpGain = ($em['rarity'] === 'ancient' ? 350 : ($em['rarity'] === 'greater' ? 150 : 70));
            $lvlMsg = gainXP($pm, $xpGain);
            $game['player']['gold'] += rand(20, 50);
            $game['message'] = "Victory! Gained $xpGain XP. " . ($lvlMsg ?? "");
            $game['currentBattle'] = null;
        } else {
            $eMove = $em['moves'][rand(0,3)];
            $eDmg = floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power'] * getTypeMultiplier($eMove['type'], $pm['type']));
            $pm['hp'] -= $eDmg;
            $game['message'] = "{$pm['name']} used {$move['name']} for $dmg. Enemy countered for $eDmg.";
        }
    }
    // Items and Run logic (Standard)
    if (str_starts_with($action, "use_pot_")) {
        $type = str_replace("use_pot_", "", $action);
        $heal = ["basic" => 40, "greater" => 100, "ancient" => 300][$type];
        if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
            $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $heal);
            $game['inventory'][$type."_potion"]--;
        }
    }
    if ($action === "run") { $game['currentBattle'] = null; }
}

if ($action === "start_battle") { $game['currentBattle'] = spawnMonster($allMonsters); }
if (isset($_POST['switch_to'])) { $game['player']['active'] = (int)$_POST['switch_to']; }

saveGame($game);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; text-align: center; margin: 0; padding-bottom: 80px; }
        .nav { background: #2c3e50; color: white; padding: 15px; display: flex; justify-content: space-around; }
        .battle-stage { display: flex; justify-content: center; align-items: center; gap: 40px; margin: 20px auto; max-width: 900px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .m-card { width: 220px; position: relative; }
        .m-card img { width: 180px; height: 180px; object-fit: contain; }
        .hp-bar { width: 100%; height: 14px; background: #eee; border-radius: 10px; overflow: hidden; border: 1px solid #ddd; margin: 10px 0; }
        .hp-fill { height: 100%; transition: width 0.4s; }
        .xp-bar { width: 100%; height: 6px; background: #dfe6e9; border-radius: 3px; overflow: hidden; }
        .xp-fill { height: 100%; background: #3498db; }
        .badge { font-size: 0.65em; padding: 3px 8px; border-radius: 4px; color: white; text-transform: uppercase; font-weight: bold; position: absolute; top: -10px; left: 0; }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #27ae60; }
        .controls { background: #fff; max-width: 650px; margin: 20px auto; padding: 25px; border-radius: 20px; border: 1px solid #e1e8ed; }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 10px 0; }
        .btn { padding: 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: white; }
        .atk-btn { background: #e67e22; } .pot-btn { background: #2ecc71; } .stone-btn { background: #9b59b6; }
        .pack-item { background: white; border: 2px solid #eee; padding: 15px; border-radius: 15px; width: 140px; transition: 0.2s; cursor: pointer; }
        .pack-item.active { border-color: #3498db; background: #f0f9ff; }
        .rarity-tag { font-size: 0.7em; color: #95a5a6; display: block; margin-top: 5px; }
    </style>
</head>
<body>

<div class="nav">
    <span>💰 Gold: <?php echo $game['player']['gold']; ?></span>
    <nav><a href="store.php" style="color:#3498db; text-decoration:none;">Visit Shop</a></nav>
</div>

<p style="padding: 10px;"><strong><?php echo $game['message']; ?></strong></p>

<?php if(!empty($game['player']['roster'])): 
    $pm = $game['player']['roster'][$game['player']['active']];
    $pmXP = getXPStats($pm['xp']);
?>
    <div class="battle-stage">
        <div class="m-card">
            <span class="badge <?php echo $pm['type']; ?>"><?php echo $pm['type']; ?></span>
            <img src="images/monsters/<?php echo $pm['image']; ?>">
            <div class="hp-bar"><div class="hp-fill" style="background:#2ecc71; width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div></div>
            <strong><?php echo $pm['name']; ?></strong> (Lvl <?php echo $pmXP['level']; ?>)
            <div class="xp-bar"><div class="xp-fill" style="width:<?php echo $pmXP['percent']; ?>%"></div></div>
        </div>

        <div style="font-size: 2em; color: #dfe6e9;">VS</div>

        <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
            <div class="m-card">
                <span class="badge <?php echo $em['type']; ?>"><?php echo $em['type']; ?></span>
                <img src="images/monsters/<?php echo $em['image']; ?>">
                <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div></div>
                <strong>Wild <?php echo $em['name']; ?></strong><br>
                <small><?php echo ucfirst($em['rarity']); ?></small>
            </div>
        <?php else: ?>
            <div class="m-card" style="border: 2px dashed #eee; height: 200px; display: flex; align-items: center; justify-content: center; border-radius: 20px; color: #ccc;">Searching...</div>
        <?php endif; ?>
    </div>

    <?php if($game['currentBattle']): ?>
        <div class="controls">
            <form method="post">
                <div class="btn-grid">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn atk-btn">
                            <?php echo $move['name']; ?><br><small>Pwr: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="btn-grid">
                    <button name="action" value="use_pot_basic" class="btn pot-btn">🧪 Potion (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                    <button name="action" value="catch_basic" class="btn stone-btn">✨ Stone (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                </div>
                <button name="action" value="run" class="btn" style="background:#e74c3c; width:100%; margin-top:10px;">🏃 RUN AWAY</button>
            </form>
        </div>
    <?php else: ?>
        <form method="post"><button name="action" value="start_battle" class="btn" style="background:#27ae60; padding:20px 60px; font-size:1.2em; border-radius:50px;">🌲 Explore Grass</button></form>
    <?php endif; ?>

    <h3 style="margin-top: 40px;">Your Pack</h3>
    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; padding: 20px;">
        <?php foreach($game['player']['roster'] as $idx => $m): 
            $mXP = getXPStats($m['xp']);
        ?>
            <form method="post">
                <button name="switch_to" value="<?php echo $idx; ?>" class="pack-item <?php echo ($idx == $game['player']['active']) ? 'active' : ''; ?>">
                    <span class="badge <?php echo $m['type']; ?>" style="position:relative; top:0;"><?php echo $m['type']; ?></span><br>
                    <strong><?php echo $m['name']; ?></strong>
                    <div class="hp-bar" style="height:8px;"><div class="hp-fill" style="background:#2ecc71; width:<?php echo ($m['hp']/$m['max_hp'])*100; ?>%"></div></div>
                    <div class="xp-bar"><div class="xp-fill" style="width:<?php echo $mXP['percent']; ?>%"></div></div>
                    <span class="rarity-tag"><?php echo ucfirst($m['rarity']); ?> | Lvl <?php echo $mXP['level']; ?></span>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>