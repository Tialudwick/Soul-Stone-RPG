<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;
$soulStones = [
    "basic" => ["name" => "Basic Stone", "bonus" => 0],
    "greater" => ["name" => "Greater Stone", "bonus" => 20],
    "ancient" => ["name" => "Ancient Stone", "bonus" => 50]
];

if (empty($game['player']['roster']) && isset($_POST['pick_id'])) {
    $s = $allMonsters[$_POST['pick_id']];
    $s['id'] = generateMonsterId();
    $s['hp'] = $s['max_hp'];
    $s['xp'] = 0;
    $game['player']['roster'][] = $s;
    $game['inventory']['potions'] = 10;
    recordCapture($game, $s['name']);
    saveGame($game);
}

if (isset($_POST['switch_to'])) { 
    $game['player']['active'] = $_POST['switch_to']; 
}

if (isset($_POST['heal_team'])) {
    foreach($game['player']['roster'] as &$m) $m['hp'] = $m['max_hp'];
    unset($game['showHeal']);
    $game['message'] = "Your team has been fully restored!";
}

if ($action === "start_battle") {
    $game['currentBattle'] = spawnMonster($allMonsters);
    $game['message'] = "A wild monster appears!";
}

// --- BATTLE ACTIONS ---
if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    // RUN LOGIC
    if ($action === "run") {
        $game['currentBattle'] = null;
        $game['message'] = "You ran away safely!";
    }

    // ATTACK LOGIC
    if ($action === "attack" && $pm['hp'] > 0) {
        $dmg = rand(5, $pm['attack']);
        $em['hp'] -= $dmg;
        if ($em['hp'] <= 0) {
            $gold = getBattleRewards($game);
            // Higher rarity enemies give more XP
            $xpReward = ($em['rarity'] === 'ancient') ? 250 : (($em['rarity'] === 'greater') ? 125 : 60);
            $lvlMsg = gainXP($pm, $xpReward); 
            
            $game['message'] = "Victory! Gained $gold Gold and $xpReward XP. " . ($lvlMsg ? $lvlMsg : "");
            $game['currentBattle'] = null;
            $game['showHeal'] = true;
        } else {
            $eDmg = rand(5, $em['attack']);
            $pm['hp'] -= $eDmg;
            $game['message'] = "You hit for $dmg! Enemy hits back for $eDmg.";
        }
    }

    // BLACKOUT
    $allFainted = true;
    foreach ($game['player']['roster'] as $m) {
        if ($m['hp'] > 0) { $allFainted = false; break; }
    }
    if ($allFainted) {
        foreach ($game['player']['roster'] as &$m) { $m['hp'] = $m['max_hp']; }
        $game['currentBattle'] = null; 
        $game['message'] = "All monsters fainted! You rushed to safety. Your team is restored.";
    }

    // CATCH LOGIC
    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if (($game['inventory'][$type] ?? 0) > 0) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], $soulStones[$type]['bonus'])) {
                $em['id'] = generateMonsterId();
                $em['xp'] = 0; // New catches start at Lvl 1
                $game['player']['roster'][] = $em;
                recordCapture($game, $em['name']);
                $game['message'] = "Gotcha! {$em['name']} was caught!";
                $game['currentBattle'] = null;
            } else {
                $game['message'] = "The monster broke free!";
            }
        }
    }
}
saveGame($game);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; text-align: center; color: #333; margin: 0; }
        .stats-bar { background: #2c3e50; color: white; padding: 15px; display: flex; justify-content: space-around; position: sticky; top: 0; z-index: 100; }
        .stats-bar a { color: #3498db; text-decoration: none; font-weight: bold; }
        .battle-container { display: flex; justify-content: center; gap: 40px; margin: 30px auto; max-width: 800px; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .monster-card { width: 220px; padding: 10px; border: 1px solid #eee; border-radius: 10px; }
        .monster-card img { width: 120px; height: 120px; object-fit: contain; }
        .hp-outer { width: 100%; height: 12px; background: #eee; border-radius: 10px; overflow: hidden; margin: 8px 0; border: 1px solid #ccc; }
        .hp-inner { height: 100%; transition: width 0.4s ease; }
        .hp-green { background: #2ecc71; }
        .hp-red { background: #e74c3c; }
        /* XP BAR CSS */
        .xp-outer { width: 100%; height: 6px; background: #dfe6e9; border-radius: 5px; margin-top: 4px; overflow: hidden; }
        .xp-inner { height: 100%; background: #3498db; transition: width 0.5s; }
        .rarity-badge { font-size: 0.7em; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; background: #ecf0f1; color: #7f8c8d; font-weight: bold; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 5px; }
        .btn-attack { background: #e67e22; color: white; }
        .btn-run { background: #95a5a6; color: white; }
        .btn-catch { background: #9b59b6; color: white; }
        .pack-btn { padding: 10px; border: 1px solid #ddd; background: white; border-radius: 8px; cursor: pointer; min-width: 120px; }
        .pack-btn.active { border: 2px solid #3498db; box-shadow: 0 0 10px rgba(52, 152, 219, 0.3); }
    </style>
</head>
<body>

    <div class="stats-bar">
        <span>💰 Gold: <strong><?php echo $game['player']['gold']; ?></strong></span>
        <nav><a href="store.php">Shop</a> | <a href="bestiary.php">Bestiary</a> | <a href="main.php">Menu</a></nav>
    </div>

    <h1>Soul Stone RPG</h1>
    <p><em><?php echo $game['message']; ?></em></p>

    <?php if(!empty($game['player']['roster'])): ?>
        <div class="battle-container">
            <?php 
                $pm = $game['player']['roster'][$game['player']['active']]; 
                $xpStats = getXPStats($pm['xp']);
            ?>
            <div class="monster-card">
                <h3>Lvl <?php echo $xpStats['level']; ?></h3>
                <img src="images/monsters/<?php echo $pm['image']; ?>">
                <div class="hp-outer">
                    <div class="hp-inner hp-green" style="width: <?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div>
                </div>
                <strong><?php echo $pm['name']; ?></strong><br>
                <small>HP: <?php echo max(0, $pm['hp']); ?> / <?php echo $pm['max_hp']; ?></small>
                
                <div class="xp-outer" title="XP to next: <?php echo ($xpStats['needed'] - $xpStats['current']); ?>">
                    <div class="xp-inner" style="width: <?php echo $xpStats['percent']; ?>%"></div>
                </div>
            </div>

            <div style="align-self: center; font-size: 2em; font-weight: bold; color: #bdc3c7;">VS</div>

            <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
                <div class="monster-card">
                    <h3>Wild <span class="rarity-badge"><?php echo $em['rarity']; ?></span></h3>
                    <img src="images/monsters/<?php echo $em['image']; ?>">
                    <div class="hp-outer">
                        <div class="hp-inner hp-red" style="width: <?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div>
                    </div>
                    <strong><?php echo $em['name']; ?></strong><br>
                    <small>HP: <?php echo $em['hp']; ?> / <?php echo $em['max_hp']; ?></small>
                </div>
            <?php else: ?>
                <div class="monster-card" style="display:flex; align-items:center; justify-content:center; border: 2px dashed #ccc;">
                    <p style="color: #999;">Peaceful...</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 40px;">
            <?php if($game['currentBattle']): ?>
                <form method="post">
                    <button name="action" value="attack" class="btn btn-attack">⚔️ Attack</button>
                    <button name="action" value="run" class="btn btn-run">🏃 Run</button>
                    <br><br>
                    <?php foreach($soulStones as $type => $data): ?>
                        <button name="action" value="catch_<?php echo $type; ?>" class="btn btn-catch">
                            ✨ <?php echo $data['name']; ?> (<?php echo $game['inventory'][$type] ?? 0; ?>)
                        </button>
                    <?php endforeach; ?>
                </form>
            <?php else: ?>
                <form method="post">
                    <?php if(isset($game['showHeal'])): ?>
                        <button name="heal_team" class="btn" style="background:#2ecc71; color:white;">💖 Restore Team</button>
                    <?php else: ?>
                        <button name="action" value="start_battle" class="btn" style="background:#27ae60; color:white; padding: 15px 40px;">🌲 Explore Grass</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>

        <h3>Your Pack</h3>
        <div style="display:flex; justify-content:center; gap:10px; flex-wrap:wrap;">
            <?php foreach($game['player']['roster'] as $i => $m): 
                $active = ($i == $game['player']['active']);
                $fainted = ($m['hp'] <= 0);
            ?>
                <form method="post">
                    <button name="switch_to" value="<?php echo $i; ?>" 
                            class="pack-btn <?php echo $active ? 'active' : ''; ?>"
                            <?php echo ($active || $fainted) ? 'disabled' : ''; ?>>
                        <strong><?php echo $m['name']; ?></strong><br>
                        <small>Lvl <?php echo getLevel($m['xp']); ?></small>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>