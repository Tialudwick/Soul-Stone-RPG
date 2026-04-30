<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- REPAIR LOGIC: Ensures moves/types exist for old saves ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        if (!isset($m['type'])) {
            foreach ($allMonsters as $ref) { if ($ref['name'] === $m['name']) { $m['type'] = $ref['type']; break; } }
        }
        if (!isset($m['moves']) || empty($m['moves'])) { $m['moves'] = $moves[$m['type'] ?? 'earth']; }
    }
    saveGame($game);
}

// --- HANDLE ACTIONS ---
if ($game['currentBattle']) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    // 1. Attack Logic
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

    // 2. Potion Logic (3 Types)
    if (str_starts_with($action, "use_pot_")) {
        $type = str_replace("use_pot_", "", $action);
        $healAmt = ["basic" => 30, "greater" => 80, "ancient" => 200][$type];
        if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
            $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $healAmt);
            $game['inventory'][$type."_potion"]--;
            $game['message'] = "Healed {$pm['name']} for $healAmt HP!";
        }
    }

    // 3. Catch Logic (3 Types)
    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if (($game['inventory'][$type] ?? 0) > 0) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], ($type === 'ancient' ? 50 : ($type === 'greater' ? 20 : 0)))) {
                $em['moves'] = $moves[$em['type']];
                $game['player']['roster'][] = $em;
                $game['message'] = "Caught {$em['name']}!";
                $game['currentBattle'] = null;
            } else { $game['message'] = "It broke free!"; }
        }
    }

    // 4. Run Logic
    if ($action === "run") { $game['currentBattle'] = null; $game['message'] = "Got away safely!"; }
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
        body { font-family: sans-serif; background: #f0f2f5; text-align: center; }
        .battle-ui { background: white; max-width: 600px; margin: 20px auto; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; }
        .btn { padding: 12px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; }
        .atk-btn { background: #e67e22; }
        .pot-btn { background: #2ecc71; }
        .stone-btn { background: #9b59b6; }
        .run-btn { background: #e74c3c; width: 100%; margin-top: 10px; }
        .label { display: block; margin-top: 15px; font-weight: bold; color: #7f8c8d; text-transform: uppercase; font-size: 0.8em; }
    </style>
</head>
<body>

<h1>Soul Stone RPG</h1>
<p><strong><?php echo $game['message']; ?></strong></p>

<?php if($game['currentBattle']): 
    $pm = $game['player']['roster'][$game['player']['active']];
    $em = $game['currentBattle'];
?>
    <div class="battle-ui">
        <h3><?php echo $pm['name']; ?> (HP: <?php echo $pm['hp']; ?>) VS <?php echo $em['name']; ?> (HP: <?php echo $em['hp']; ?>)</h3>
        
        <form method="post">
            <span class="label">Choose an Attack</span>
            <div class="btn-grid">
                <?php foreach($pm['moves'] as $i => $move): ?>
                    <button name="action" value="attack_<?php echo $i; ?>" class="btn atk-btn">
                        <?php echo $move['name']; ?><br><small>Pwr: <?php echo $move['power']; ?></small>
                    </button>
                <?php endforeach; ?>
            </div>

            <span class="label">Potions</span>
            <div class="btn-grid">
                <button name="action" value="use_pot_basic" class="btn pot-btn">Basic (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                <button name="action" value="use_pot_greater" class="btn pot-btn">Greater (<?php echo $game['inventory']['greater_potion'] ?? 0; ?>)</button>
                <button name="action" value="use_pot_ancient" class="btn pot-btn" style="grid-column: span 2;">Ancient (<?php echo $game['inventory']['ancient_potion'] ?? 0; ?>)</button>
            </div>

            <span class="label">Soul Stones</span>
            <div class="btn-grid">
                <button name="action" value="catch_basic" class="btn stone-btn">Basic (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                <button name="action" value="catch_greater" class="btn stone-btn">Greater (<?php echo $game['inventory']['greater'] ?? 0; ?>)</button>
                <button name="action" value="catch_ancient" class="btn stone-btn" style="grid-column: span 2;">Ancient (<?php echo $game['inventory']['ancient'] ?? 0; ?>)</button>
            </div>

            <button name="action" value="run" class="btn run-btn">🏃 RUN AWAY</button>
        </form>
    </div>
<?php else: ?>
    <form method="post"><button name="action" value="start_battle" style="padding: 20px 40px; font-size: 1.2em;">🌲 Search the Grass</button></form>
<?php endif; ?>

</body>
</html>