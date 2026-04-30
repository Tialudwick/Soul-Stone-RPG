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
    $s['moves'] = $moves[$s['type']]; // Assign 4 moves based on type
    $game['player']['roster'][] = $s;
    $game['inventory']['potions'] = 10;
    recordCapture($game, $s['name']);
    saveGame($game);
}

// --- SWITCH MONSTER ---
if (isset($_POST['switch_to'])) { 
    $game['player']['active'] = (int)$_POST['switch_to']; 
}

// --- HEAL TEAM ---
if (isset($_POST['heal_team'])) {
    foreach($game['player']['roster'] as &$m) $m['hp'] = $m['max_hp'];
    unset($game['showHeal']);
    $game['message'] = "Your team is fully healed!";
}

// --- START BATTLE ---
if ($action === "start_battle") {
    $game['currentBattle'] = spawnMonster($allMonsters);
    $game['message'] = "A wild " . $game['currentBattle']['name'] . " appeared!";
}

// --- BATTLE LOGIC ---
if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    // SAFETY: If monster has no moves (from old saves), assign them now
    if (!isset($pm['moves'])) { $pm['moves'] = $moves[$pm['type']]; }
    if (!isset($em['moves'])) { $em['moves'] = $moves[$em['type']]; }

    // RUN AWAY
    if ($action === "run") {
        $game['currentBattle'] = null;
        $game['message'] = "You escaped safely!";
    }

    // MULTI-MOVE ATTACK SYSTEM
    if (str_starts_with($action, "attack_") && $pm['hp'] > 0) {
        $moveIndex = (int)str_replace("attack_", "", $action);
        $selectedMove = $pm['moves'][$moveIndex];

        // Type Effectiveness Logic
        $multiplier = getTypeMultiplier($selectedMove['type'], $em['type']);
        $dmg = floor(rand($pm['attack'] - 2, $pm['attack'] + 2) * $selectedMove['power'] * $multiplier);
        $em['hp'] -= $dmg;

        $effText = ($multiplier > 1) ? " It's super effective!" : ($multiplier < 1 ? " It's not very effective..." : "");
        
        if ($em['hp'] <= 0) {
            $gold = getBattleRewards($game);
            $xpReward = ($em['rarity'] === 'ancient') ? 300 : ($em['rarity'] === 'greater' ? 150 : 70);
            $lvlMsg = gainXP($pm, $xpReward);
            $game['message'] = "Victory! Gained $gold Gold and $xpReward XP. " . ($lvlMsg ?: "");
            $game['currentBattle'] = null;
            $game['showHeal'] = true;
        } else {
            // Enemy Turn
            $eMove = $em['moves'][rand(0, 3)];
            $eMult = getTypeMultiplier($eMove['type'], $pm['type']);
            $eDmg = floor(rand($em['attack'] - 2, $em['attack'] + 2) * $eMove['power'] * $eMult);
            $pm['hp'] -= $eDmg;
            $game['message'] = "{$pm['name']} used {$selectedMove['name']} for $dmg dmg.$effText Enemy used {$eMove['name']} for $eDmg dmg.";
        }
    }

    // CATCH LOGIC
    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if (($game['inventory'][$type] ?? 0) > 0) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], $soulStones[$type]['bonus'])) {
                $em['id'] = generateMonsterId();
                $em['xp'] = 0;
                $em['moves'] = $moves[$em['type']];
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
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; text-align: center; margin: 0; }
        .navbar { background: #2c3e50; color: white; padding: 15px; display: flex; justify-content: space-around; }
        .navbar a { color: #3498db; text-decoration: none; font-weight: bold; }
        .battle-stage { display: flex; justify-content: center; gap: 50px; margin: 30px auto; max-width: 900px; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .m-card { width: 250px; }
        .m-card img { width: 150px; height: 150px; object-fit: contain; }
        .hp-bar { width: 100%; height: 15px; background: #eee; border-radius: 10px; margin: 10px 0; border: 1px solid #ddd; overflow: hidden; }
        .hp-fill { height: 100%; transition: width 0.5s ease; }
        .xp-bar { width: 100%; height: 6px; background: #dfe6e9; border-radius: 5px; overflow: hidden; }
        .xp-fill { height: 100%; background: #3498db; }
        .badge { font-size: 0.7em; padding: 4px 10px; border-radius: 5px; color: white; text-transform: uppercase; font-weight: bold; }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #27ae60; }
        .move-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; }
        .btn { padding: 15px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; transition: all 0.2s; }
        .btn-move { background: #f8f9fa; border: 2px solid #e9ecef; color: #2c3e50; }
        .btn-move:hover { border-color: #3498db; background: #edf7fd; }
        .btn-main { background: #27ae60; color: white; width: 100%; font-size: 1.2em; padding: 20px; }
    </style>
</head>
<body>

<div class="navbar">
    <span>💰 Gold: <?php echo $game['player']['gold']; ?></span>
    <nav><a href="store.php">Shop</a> | <a href="bestiary.php">Bestiary</a> | <a href="main.php">Menu</a></nav>
</div>

<h1>Soul Stone RPG</h1>
<p><strong><?php echo $game['message']; ?></strong></p>

<?php if(!empty($game['player']['roster'])): ?>
    <div class="battle-stage">
        <?php 
            $pm = $game['player']['roster'][$game['player']['active']]; 
            $xp = getXPStats($pm['xp']); 
        ?>
        <div class="m-card">
            <span class="badge <?php echo $pm['type']; ?>"><?php echo $pm['type']; ?></span>
            <h3>Lvl <?php echo $xp['level']; ?></h3>
            <img src="images/monsters/<?php echo $pm['image']; ?>">
            <div class="hp-bar"><div class="hp-fill" style="background:#2ecc71; width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div></div>
            <strong><?php echo $pm['name']; ?></strong>
            <div class="xp-bar"><div class="xp-fill" style="width:<?php echo $xp['percent']; ?>%"></div></div>
        </div>

        <div style="align-self: center; font-size: 2.5em; color: #dfe6e9;">VS</div>

        <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
            <div class="m-card">
                <span class="badge <?php echo $em['type']; ?>"><?php echo $em['type']; ?></span>
                <h3 style="color:#e74c3c;"><?php echo ucfirst($em['rarity']); ?></h3>
                <img src="images/monsters/<?php echo $em['image']; ?>">
                <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div></div>
                <strong><?php echo $em['name']; ?></strong>
            </div>
        <?php else: ?>
            <div class="m-card" style="border: 3px dashed #eee; display: flex; align-items: center; justify-content: center; height: 250px; border-radius: 20px;">
                <p style="color: #bdc3c7;">Tall Grass...</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="max-width: 500px; margin: auto; padding: 0 20px;">
        <?php if($game['currentBattle']): ?>
            <form method="post">
                <div class="move-grid">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-move">
                            <?php echo $move['name']; ?><br>
                            <small>Pwr: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button name="action" value="run" class="btn" style="flex:1; background:#95a5a6; color:white;">🏃 Run</button>
                    <button name="action" value="catch_basic" class="btn" style="flex:2; background:#9b59b6; color:white;">✨ Throw Soul Stone (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                </div>
            </form>
        <?php else: ?>
            <form method="post">
                <?php if(isset($game['showHeal'])): ?>
                    <button name="heal_team" class="btn btn-main" style="background:#3498db;">💖 Heal Team</button>
                <?php else: ?>
                    <button name="action" value="start_battle" class="btn btn-main">🌲 Walk into Grass</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <h3 style="margin-top: 40px;">Your Pack</h3>
    <div style="display: flex; justify-content: center; gap: 10px; padding: 20px; flex-wrap: wrap;">
        <?php foreach($game['player']['roster'] as $index => $mon): ?>
            <form method="post">
                <button name="switch_to" value="<?php echo $index; ?>" 
                        class="btn" style="background: white; border: 2px solid <?php echo ($index == $game['player']['active']) ? '#3498db' : '#eee'; ?>; min-width: 100px;">
                    <strong><?php echo $mon['name']; ?></strong><br>
                    <small>HP: <?php echo max(0, $mon['hp']); ?></small>
                </button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

</body>
</html>