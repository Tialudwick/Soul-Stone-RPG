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

// --- STARTER SELECTION ---
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

// --- SWITCH MONSTER ---
if (isset($_POST['switch_to'])) { 
    $game['player']['active'] = $_POST['switch_to']; 
}

// --- HEAL TEAM (POST-BATTLE BONUS) ---
if (isset($_POST['heal_team'])) {
    foreach($game['player']['roster'] as &$m) $m['hp'] = $m['max_hp'];
    unset($game['showHeal']);
    $game['message'] = "Your team has been fully restored!";
}

// --- START BATTLE ---
if ($action === "start_battle") {
    $game['currentBattle'] = spawnMonster($allMonsters);
    $game['message'] = "A wild monster appears!";
}

// --- BATTLE ACTIONS ---
if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    // ATTACK LOGIC
    if ($action === "attack" && $pm['hp'] > 0) {
        $dmg = rand(5, $pm['attack']);
        $em['hp'] -= $dmg;
        if ($em['hp'] <= 0) {
            $gold = getBattleRewards($game);
            
            // INCREASED XP RATIO: 150 XP per victory for faster leveling
            $lvlMsg = gainXP($pm, 150); 
            
            $game['message'] = "Victory! Gained $gold Gold. " . ($lvlMsg ? $lvlMsg : "");
            $game['currentBattle'] = null;
            $game['showHeal'] = true;
        } else {
            $eDmg = rand(5, $em['attack']);
            $pm['hp'] -= $eDmg;
            $game['message'] = "You hit for $dmg! Enemy hits back for $eDmg.";
        }
    }

    // --- BLACKOUT LOGIC (Total Team Faint) ---
    $allFainted = true;
    foreach ($game['player']['roster'] as $m) {
        if ($m['hp'] > 0) {
            $allFainted = false;
            break;
        }
    }

    if ($allFainted) {
        foreach ($game['player']['roster'] as &$m) {
            $m['hp'] = $m['max_hp'];
        }
        $game['currentBattle'] = null; 
        $game['message'] = "All monsters fainted! You rushed to the nearest healing station. Your team is restored.";
        saveGame($game);
    }

    // --- ENHANCED HEALING LOGIC ---
    if (str_starts_with($action, "heal_")) {
        $potionType = str_replace("heal_", "", $action);
        
        $tiers = [
            'basic' => ['key' => 'potions', 'amt' => 30],
            'super' => ['key' => 'super_potions', 'amt' => 100],
            'max'   => ['key' => 'max_potions', 'amt' => 999]
        ];

        $invKey = $tiers[$potionType]['key'];
        $healAmt = $tiers[$potionType]['amt'];

        if (($game['inventory'][$invKey] ?? 0) > 0) {
            if ($pm['hp'] < $pm['max_hp']) {
                $game['inventory'][$invKey]--;
                $pm['hp'] = min($pm['hp'] + $healAmt, $pm['max_hp']);
                $game['message'] = "Used $potionType Potion on {$pm['name']}!";
            } else {
                $game['message'] = "{$pm['name']} is already healthy!";
            }
        } else {
            $game['message'] = "You don't have any $potionType potions!";
        }
    }
    
    // CATCH LOGIC
    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if ($game['inventory'][$type] > 0 && count($game['player']['roster']) < 8) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], $soulStones[$type]['bonus'])) {
                $em['id'] = generateMonsterId();
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; text-align: center; color: #333; }
        .stats-bar { background: #2c3e50; color: white; padding: 15px; display: flex; justify-content: space-around; position: sticky; top: 0; z-index: 100; }
        .stats-bar a { color: #3498db; text-decoration: none; font-weight: bold; }
        .battle-container { display: flex; justify-content: center; gap: 40px; margin: 30px auto; max-width: 800px; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .monster-card { width: 200px; }
        .monster-card img { width: 120px; height: 120px; object-fit: contain; }
        .hp-outer { width: 100%; height: 15px; background: #eee; border-radius: 10px; border: 1px solid #ccc; overflow: hidden; margin: 10px 0; }
        .hp-inner { height: 100%; transition: width 0.4s ease; }
        .hp-green { background: #2ecc71; }
        .hp-red { background: #e74c3c; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 5px; transition: transform 0.1s; }
        .btn:active { transform: scale(0.95); }
        .btn-attack { background: #e67e22; color: white; }
        .btn-heal { background: #3498db; color: white; }
        .btn-catch { background: #9b59b6; color: white; }
        .btn-explore { background: #27ae60; color: white; padding: 15px 40px; font-size: 1.2em; }
        .pack-grid { display: flex; justify-content: center; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .pack-btn { padding: 10px; border: 1px solid #ddd; background: white; border-radius: 8px; cursor: pointer; }
        .pack-btn.active { border: 2px solid #3498db; box-shadow: 0 0 10px rgba(52, 152, 219, 0.3); }
        .fainted { opacity: 0.5; background: #dfe6e9; cursor: not-allowed; }
    </style>
</head>
<body>

    <div class="stats-bar">
        <span>💰 Gold: <strong><?php echo $game['player']['gold']; ?></strong></span>
        <span>🧪 Potions: <strong><?php echo $game['inventory']['potions']; ?></strong></span>
        <nav>
            <a href="store.php">Shop</a> | 
            <a href="bestiary.php">Bestiary</a> | 
            <a href="main.php">Menu</a>
        </nav>
    </div>

    <h1>Soul Stone RPG</h1>
    <p><em><?php echo $game['message'] ?? 'Find a monster in the wild!'; ?></em></p>

    <?php if(empty($game['player']['roster'])): ?>
        <div style="background: white; display: inline-block; padding: 30px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
            <h2>Pick Your Starter</h2>
            <form method="post">
                <?php for($i=0; $i<3; $i++): $m = $allMonsters[$i]; ?>
                    <button name="pick_id" value="<?php echo $i; ?>" class="btn" style="background:#eee;">
                        <img src="images/monsters/<?php echo $m['image']; ?>" width="80"><br>
                        <?php echo $m['name']; ?>
                    </button>
                <?php endfor; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if(!empty($game['player']['roster'])): ?>
        <div class="battle-container">
            <?php $pm = $game['player']['roster'][$game['player']['active']]; ?>
            <div class="monster-card">
                <h3>You (Lvl <?php echo getLevel($pm['xp']); ?>)</h3>
                <img src="images/monsters/<?php echo $pm['image']; ?>">
                <div class="hp-outer">
                    <div class="hp-inner hp-green" style="width: <?php echo max(0, ($pm['hp']/$pm['max_hp'])*100); ?>%"></div>
                </div>
                <strong><?php echo $pm['name']; ?></strong><br>
                HP: <?php echo max(0, $pm['hp']); ?> / <?php echo $pm['max_hp']; ?>
            </div>

            <div style="align-self: center; font-size: 2em; font-weight: bold; color: #bdc3c7;">VS</div>

            <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
                <div class="monster-card">
                    <h3 style="color: #c0392b;">Wild</h3>
                    <img src="images/monsters/<?php echo $em['image']; ?>">
                    <div class="hp-outer">
                        <div class="hp-inner hp-red" style="width: <?php echo max(0, ($em['hp']/$em['max_hp'])*100); ?>%"></div>
                    </div>
                    <strong><?php echo $em['name']; ?></strong><br>
                    HP: <?php echo max(0, $em['hp']); ?> / <?php echo $em['max_hp']; ?>
                </div>
            <?php else: ?>
                <div class="monster-card" style="display:flex; align-items:center; justify-content:center; border: 2px dashed #ccc; border-radius: 10px;">
                    <p style="color: #999;">Peaceful...</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="margin: 20px;">
        <?php if($game['currentBattle']): ?>
            <form method="post">
                <button name="action" value="attack" class="btn btn-attack">⚔️ Attack</button>
                
                <br><br>
                <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 20px;">
                    <button name="action" value="heal_basic" class="btn btn-heal">
                        🧪 Potion (<?php echo $game['inventory']['potions'] ?? 0; ?>)
                    </button>
                    <button name="action" value="heal_super" class="btn" style="background:#2980b9; color:white;">
                        💎 Super (<?php echo $game['inventory']['super_potions'] ?? 0; ?>)
                    </button>
                    <button name="action" value="heal_max" class="btn" style="background:#8e44ad; color:white;">
                        🌟 Max (<?php echo $game['inventory']['max_potions'] ?? 0; ?>)
                    </button>
                </div>
                
                <?php foreach($soulStones as $type => $data): ?>
                    <button name="action" value="catch_<?php echo $type; ?>" class="btn btn-catch">
                        ✨ <?php echo $data['name']; ?> (<?php echo $game['inventory'][$type] ?? 0; ?>)
                    </button>
                <?php endforeach; ?>
            </form>
        <?php elseif(!empty($game['player']['roster'])): ?>
            <form method="post">
                <?php if(isset($game['showHeal'])): ?>
                    <button name="heal_team" class="btn" style="background:#2ecc71; color:white;">💖 Full Team Heal</button>
                <?php else: ?>
                    <button name="action" value="start_battle" class="btn btn-explore">🌲 Explore Tall Grass</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>

    <?php if(!empty($game['player']['roster'])): ?>
        <hr style="width: 80%; margin: 40px auto; border: 0; border-top: 1px solid #ccc;">
        <h3>Your Pack</h3>
        <div class="pack-grid">
            <?php foreach($game['player']['roster'] as $i => $m): 
                $is_active = ($i == $game['player']['active']);
                $is_fainted = ($m['hp'] <= 0);
            ?>
                <form method="post">
                    <button name="switch_to" value="<?php echo $i; ?>" 
                            class="pack-btn <?php echo $is_active ? 'active' : ''; ?> <?php echo $is_fainted ? 'fainted' : ''; ?>"
                            <?php echo ($is_active || $is_fainted) ? 'disabled' : ''; ?>>
                        <strong><?php echo $m['name']; ?> (Lvl <?php echo getLevel($m['xp']); ?>)</strong><br>
                        <small>HP: <?php echo max(0, $m['hp']); ?>/<?php echo $m['max_hp']; ?></small>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>