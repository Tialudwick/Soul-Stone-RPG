<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- REPAIR LOGIC: Ensures moves/types exist ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        if (!isset($m['type'])) {
            foreach ($allMonsters as $ref) { if ($ref['name'] === $m['name']) { $m['type'] = $ref['type']; break; } }
        }
        if (!isset($m['moves']) || empty($m['moves'])) { $m['moves'] = $moves[$m['type'] ?? 'earth']; }
    }
}

// --- HANDLE ACTIONS (Logic remains the same as previous) ---
if ($game['currentBattle']) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    if (str_starts_with($action, "attack_")) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        $mult = getTypeMultiplier($move['type'], $em['type']);
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * $mult);
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            gainXP($pm, ($em['rarity'] === 'ancient' ? 300 : 80));
            $game['player']['gold'] += rand(20, 50);
            $game['message'] = "Victory! You defeated {$em['name']}!";
            $game['currentBattle'] = null;
        } else {
            $eMove = $em['moves'][rand(0,3)];
            $eDmg = floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power'] * getTypeMultiplier($eMove['type'], $pm['type']));
            $pm['hp'] -= $eDmg;
            $game['message'] = "{$pm['name']} used {$move['name']} ($dmg). Enemy hit back for $eDmg.";
        }
    }

    if (str_starts_with($action, "use_pot_")) {
        $type = str_replace("use_pot_", "", $action);
        $healAmt = ["basic" => 30, "greater" => 80, "ancient" => 200][$type];
        if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
            $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $healAmt);
            $game['inventory'][$type."_potion"]--;
        }
    }

    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if (($game['inventory'][$type] ?? 0) > 0) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], ($type === 'ancient' ? 50 : ($type === 'greater' ? 20 : 0)))) {
                $em['moves'] = $moves[$em['type']];
                $game['player']['roster'][] = $em;
                $game['currentBattle'] = null;
            }
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
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; text-align: center; margin: 0; padding-bottom: 50px; }
        .nav { background: #2c3e50; color: white; padding: 15px; display: flex; justify-content: space-around; }
        
        /* Section 1: Battle Visuals */
        .battle-stage { display: flex; justify-content: center; align-items: center; gap: 60px; margin: 20px auto; max-width: 900px; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .m-card { width: 220px; }
        .m-card img { width: 180px; height: 180px; object-fit: contain; margin-bottom: 10px; }
        .hp-bar { width: 100%; height: 12px; background: #eee; border-radius: 10px; overflow: hidden; border: 1px solid #ddd; }
        .hp-fill { height: 100%; transition: width 0.4s; }

        /* Section 2: Control Panel */
        .controls { background: #fff; max-width: 700px; margin: 20px auto; padding: 25px; border-radius: 20px; border: 1px solid #e1e8ed; }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 15px 0; }
        .btn { padding: 15px; border: none; border-radius: 10px; cursor: pointer; font-weight: bold; color: white; transition: 0.2s; }
        .btn:active { transform: scale(0.98); }
        .atk-btn { background: #e67e22; border-bottom: 4px solid #d35400; }
        .pot-btn { background: #2ecc71; border-bottom: 4px solid #27ae60; }
        .stone-btn { background: #9b59b6; border-bottom: 4px solid #8e44ad; }
        .run-btn { background: #e74c3c; width: 100%; margin-top: 15px; border-bottom: 4px solid #c0392b; }
        .label { display: block; margin: 20px 0 10px; font-weight: bold; color: #95a5a6; text-transform: uppercase; font-size: 0.75em; letter-spacing: 1px; }

        /* Section 3: Roster */
        .roster-wrap { margin-top: 40px; }
        .roster-grid { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; padding: 20px; }
        .pack-btn { background: white; border: 2px solid #eee; padding: 10px; border-radius: 12px; min-width: 110px; }
        .active-mon { border-color: #3498db; background: #ebf5fb; }
    </style>
</head>
<body>

<div class="nav">
    <span>💰 Gold: <?php echo $game['player']['gold']; ?></span>
    <nav><a href="store.php" style="color:#3498db; text-decoration:none;">Visit Shop</a></nav>
</div>

<p style="margin-top:20px;"><strong><?php echo $game['message']; ?></strong></p>

<?php if(!empty($game['player']['roster'])): 
    $pm = $game['player']['roster'][$game['player']['active']];
?>
    <div class="battle-stage">
        <div class="m-card">
            <img src="images/monsters/<?php echo $pm['image']; ?>">
            <div class="hp-bar"><div class="hp-fill" style="background:#2ecc71; width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div></div>
            <strong><?php echo $pm['name']; ?></strong><br>
            <small>HP: <?php echo $pm['hp']; ?> / <?php echo $pm['max_hp']; ?></small>
        </div>

        <div style="font-size: 2em; color: #dfe6e9; font-weight: bold;">VS</div>

        <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
            <div class="m-card">
                <img src="images/monsters/<?php echo $em['image']; ?>">
                <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div></div>
                <strong>Wild <?php echo $em['name']; ?></strong><br>
                <small>HP: <?php echo $em['hp']; ?> / <?php echo $em['max_hp']; ?></small>
            </div>
        <?php else: ?>
            <div class="m-card" style="border: 3px dashed #eee; height: 200px; display: flex; align-items: center; justify-content: center; border-radius: 20px;">
                <span style="color: #ccc;">No Wild Monster</span>
            </div>
        <?php endif; ?>
    </div>

    <?php if($game['currentBattle']): ?>
        <div class="controls">
            <form method="post">
                <span class="label">Choose an Attack</span>
                <div class="btn-grid">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn atk-btn">
                            <?php echo $move['name']; ?><br><small>Power: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>

                <span class="label">Use Items</span>
                <div class="btn-grid">
                    <button name="action" value="use_pot_basic" class="btn pot-btn">Potion (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                    <button name="action" value="catch_basic" class="btn stone-btn">Stone (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                </div>

                <button name="action" value="run" class="btn run-btn">🏃 RUN AWAY</button>
            </form>
        </div>
    <?php else: ?>
        <form method="post">
            <button name="action" value="start_battle" style="padding: 25px 60px; font-size: 1.3em; background: #27ae60; color: white; border: none; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 10px rgba(39, 174, 96, 0.3);">
                🌲 Explore Tall Grass
            </button>
        </form>
    <?php endif; ?>

    <div class="roster-wrap">
        <h3>Your Pack</h3>
        <div class="roster-grid">
            <?php foreach($game['player']['roster'] as $idx => $m): ?>
                <form method="post">
                    <button name="switch_to" value="<?php echo $idx; ?>" class="pack-btn <?php echo ($idx == $game['player']['active']) ? 'active-mon' : ''; ?>">
                        <img src="images/monsters/<?php echo $m['image']; ?>" style="width: 40px; height: 40px; object-fit: contain;"><br>
                        <strong><?php echo $m['name']; ?></strong><br>
                        <small>HP: <?php echo max(0, $m['hp']); ?></small>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

</body>
</html>