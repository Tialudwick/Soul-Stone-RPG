<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

$soulStones = [
    "basic"   => ["name" => "Basic Stone", "bonus" => 0],
    "greater" => ["name" => "Greater Stone", "bonus" => 20],
    "ancient" => ["name" => "Ancient Stone", "bonus" => 50]
];

// --- STARTER SELECTION ---
if (empty($game['player']['roster']) && isset($_POST['pick_id'])) {
    $s = $allMonsters[$_POST['pick_id']];
    $s['id'] = generateMonsterId();
    $s['hp'] = $s['max_hp'];
    $s['xp'] = 0;
    $s['moves'] = $moves[$s['type']]; // Assign moves based on type
    $game['player']['roster'][] = $s;
    $game['inventory']['potions'] = 10;
    recordCapture($game, $s['name']);
    saveGame($game);
}

// --- SWITCH MONSTER ---
if (isset($_POST['switch_to'])) { 
    $game['player']['active'] = $_POST['switch_to']; 
}

// --- HEAL TEAM ---
if (isset($_POST['heal_team'])) {
    foreach($game['player']['roster'] as &$m) $m['hp'] = $m['max_hp'];
    unset($game['showHeal']);
    $game['message'] = "Your team has been fully restored!";
}

// --- START BATTLE ---
if ($action === "start_battle") {
    $game['currentBattle'] = spawnMonster($allMonsters);
    $game['message'] = "A wild " . $game['currentBattle']['name'] . " appeared!";
}

// --- BATTLE ACTIONS ---
if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    // RUN LOGIC
    if ($action === "run") {
        $game['currentBattle'] = null;
        $game['message'] = "You escaped safely!";
    }

    // ATTACK LOGIC (Moves & Type Weakness)
    if (str_starts_with($action, "attack_") && $pm['hp'] > 0) {
        $moveIndex = (int)str_replace("attack_", "", $action);
        $selectedMove = $pm['moves'][$moveIndex];

        // Player Attack
        $multiplier = getTypeMultiplier($selectedMove['type'], $em['type']);
        $dmg = floor(rand($pm['attack'] - 2, $pm['attack'] + 2) * $selectedMove['power'] * $multiplier);
        $em['hp'] -= $dmg;

        $effText = ($multiplier > 1) ? " It's super effective!" : ($multiplier < 1 ? " It's not very effective..." : "");
        $msg = "{$pm['name']} used {$selectedMove['name']} for $dmg dmg.$effText";

        if ($em['hp'] <= 0) {
            $gold = getBattleRewards($game);
            $xpReward = ($em['rarity'] === 'ancient') ? 300 : ($em['rarity'] === 'greater' ? 150 : 70);
            $lvlMsg = gainXP($pm, $xpReward);
            $game['message'] = "Victory! Gained $gold Gold and $xpReward XP. " . ($lvlMsg ?: "");
            $game['currentBattle'] = null;
            $game['showHeal'] = true;
        } else {
            // Enemy Counter Attack
            $eMove = $em['moves'][rand(0, 3)];
            $eMult = getTypeMultiplier($eMove['type'], $pm['type']);
            $eDmg = floor(rand($em['attack'] - 2, $em['attack'] + 2) * $eMove['power'] * $eMult);
            $pm['hp'] -= $eDmg;
            $game['message'] = $msg . " Enemy used {$eMove['name']} for $eDmg dmg.";
        }
    }

    // CATCH LOGIC
    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if (($game['inventory'][$type] ?? 0) > 0 && count($game['player']['roster']) < 8) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], $soulStones[$type]['bonus'])) {
                $em['id'] = generateMonsterId();
                $em['xp'] = 0;
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
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; text-align: center; margin: 0; }
        .stats-bar { background: #2c3e50; color: white; padding: 15px; display: flex; justify-content: space-around; }
        .stats-bar a { color: #3498db; text-decoration: none; font-weight: bold; }
        .battle-container { display: flex; justify-content: center; gap: 40px; margin: 20px auto; max-width: 900px; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .monster-card { width: 240px; padding: 10px; }
        .monster-card img { width: 140px; height: 140px; object-fit: contain; }
        .hp-outer { width: 100%; height: 12px; background: #eee; border-radius: 10px; overflow: hidden; margin: 10px 0; border: 1px solid #ccc; }
        .hp-inner { height: 100%; transition: width 0.4s; }
        .xp-outer { width: 100%; height: 6px; background: #dfe6e9; border-radius: 5px; overflow: hidden; }
        .xp-inner { height: 100%; background: #3498db; }
        .type-badge { font-size: 0.75em; padding: 3px 8px; border-radius: 5px; color: white; text-transform: uppercase; }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #2ecc71; }
        .move-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px; }
        .btn { padding: 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; transition: 0.2s; }
        .btn:active { transform: scale(0.95); }
        .btn-move { background: #ecf0f1; border-bottom: 3px solid #bdc3c7; }
        .btn-move:hover { background: #dfe6e9; }
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
            <span class="type-badge <?php echo $pm['type']; ?>"><?php echo $pm['type']; ?></span>
            <h3>Lvl <?php echo $xpStats['level']; ?></h3>
            <img src="images/monsters/<?php echo $pm['image']; ?>">
            <div class="hp-outer">
                <div class="hp-inner" style="background:#2ecc71; width: <?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div>
            </div>
            <strong><?php echo $pm['name']; ?></strong><br>
            <div class="xp-outer"><div class="xp-inner" style="width: <?php echo $xpStats['percent']; ?>%"></div></div>
        </div>

        <div style="align-self: center; font-size: 2em; color: #bdc3c7;">VS</div>

        <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
            <div class="monster-card">
                <span class="type-badge <?php echo $em['type']; ?>"><?php echo $em['type']; ?></span>
                <h3 style="color:#c0392b;"><?php echo ucfirst($em['rarity']); ?></h3>
                <img src="images/monsters/<?php echo $em['image']; ?>">
                <div class="hp-outer">
                    <div class="hp-inner" style="background:#e74c3c; width: <?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div>
                </div>
                <strong><?php echo $em['name']; ?></strong>
            </div>
        <?php else: ?>
            <div class="monster-card" style="border: 2px dashed #ccc; display:flex; align-items:center; justify-content:center;">
                <p>Search the grass...</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="max-width: 500px; margin: auto;">
        <?php if($game['currentBattle']): ?>
            <form method="post">
                <div class="move-grid">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-move">
                            <?php echo $move['name']; ?><br>
                            <small style="color:#7f8c8d;">Pwr: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 15px;">
                    <button name="action" value="run" class="btn" style="background:#95a5a6; color:white;">🏃 Run Away</button>
                </div>
                <div style="margin-top: 15px;">
                    <?php foreach($soulStones as $type => $data): ?>
                        <button name="action" value="catch_<?php echo $type; ?>" class="btn" style="background:#9b59b6; color:white;">
                            ✨ <?php echo $data['name']; ?> (<?php echo $game['inventory'][$type] ?? 0; ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php else: ?>
            <form method="post">
                <?php if(isset($game['showHeal'])): ?>
                    <button name="heal_team" class="btn" style="background:#2ecc71; color:white; width:100%;">💖 Full Team Heal</button>
                <?php else: ?>
                    <button name="action" value="start_battle" class="btn" style="background:#27ae60; color:white; width:100%; font-size: 1.2em;">🌲 Explore Tall Grass</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

</body>
</html>