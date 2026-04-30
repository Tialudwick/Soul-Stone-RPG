<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- REPAIR LOGIC: Ensuring data integrity for all monsters ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
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

    // Attack Action
    if (str_starts_with($action, "attack_")) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * getTypeMultiplier($move['type'], $em['type']));
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            gainXP($pm, ($em['rarity'] === 'ancient' ? 300 : 80));
            $game['message'] = "Victory! Defeated {$em['name']}.";
            $game['currentBattle'] = null;
        } else {
            $eMove = $em['moves'][rand(0,3)];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
            $game['message'] = "{$pm['name']} hit for $dmg! Enemy countered.";
        }
    }

    // Potion/Stone Actions
    if (str_starts_with($action, "use_pot_")) {
        $type = str_replace("use_pot_", "", $action);
        $heal = ["basic" => 40, "greater" => 100, "ancient" => 300][$type];
        if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
            $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $heal);
            $game['inventory'][$type."_potion"]--;
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
        body { font-family: sans-serif; background: #e0e0e0; margin: 0; padding: 20px; }
        .main-container { display: flex; justify-content: center; gap: 20px; max-width: 1200px; margin: auto; }
        
        /* Left Column: Battle Stage */
        .battle-column { flex: 1; background: #cccccc; padding: 20px; border-radius: 10px; min-height: 600px; }
        .stage-visuals { display: flex; justify-content: space-around; background: #b0b0b0; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .monster-box { width: 180px; text-align: center; }
        .monster-box img { width: 150px; height: 150px; background: #fff; border: 2px solid #333; }
        
        /* Right Column: Inventory & Roster */
        .ui-column { flex: 1; background: #cccccc; padding: 20px; border-radius: 10px; }
        
        .section-label { font-weight: bold; text-align: center; margin: 15px 0 5px; text-transform: uppercase; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .roster-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }

        .btn { border: none; padding: 15px 5px; cursor: pointer; font-weight: bold; color: white; border-radius: 3px; }
        .btn-atk { background: #f39c12; }
        .btn-pot { background: #d354ce; } .btn-pot.greater { background: #6a5acd; } .btn-pot.ancient { background: #2ecc71; }
        .btn-stone { background: #d354ce; } .btn-stone.greater { background: #6a5acd; } .btn-stone.ancient { background: #2ecc71; }
        .btn-run { background: #e74c3c; width: 100%; margin-top: 10px; padding: 20px; }
        
        .roster-slot { background: #eee; border: 2px solid #999; aspect-ratio: 1/1.2; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 0.8em; }
        .active-slot { border-color: #3498db; background: #fff; }
        
        .hp-bar { width: 90%; height: 10px; background: #444; margin: 5px 0; }
        .hp-fill { height: 100%; background: #2ecc71; }
        .type-tag { font-size: 0.7em; background: #333; color: #fff; padding: 2px 5px; }
    </style>
</head>
<body>

<h2 style="text-align:center;">Soul Stone RPG</h2>

<div class="main-container">
    <div class="battle-column">
        <?php if($game['currentBattle']): 
            $pm = $game['player']['roster'][$game['player']['active']];
            $em = $game['currentBattle'];
        ?>
            <div class="stage-visuals">
                <div class="monster-box">
                    <div class="type-tag"><?php echo $pm['type']; ?></div>
                    <img src="images/monsters/<?php echo $pm['image']; ?>">
                    <strong><?php echo $pm['name']; ?></strong>
                    <div class="hp-bar"><div class="hp-fill" style="width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div></div>
                </div>
                <div style="align-self:center; font-weight:bold;">VS</div>
                <div class="monster-box">
                    <div class="type-tag"><?php echo $em['type']; ?></div>
                    <img src="images/monsters/<?php echo $em['image']; ?>">
                    <strong><?php echo $em['name']; ?></strong>
                    <div class="hp-bar"><div class="hp-fill" style="background:#e74c3c; width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div></div>
                </div>
            </div>

            <form method="post">
                <div class="grid-2">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn btn-atk"><?php echo $move['name']; ?></button>
                    <?php endforeach; ?>
                </div>
                <button name="action" value="run" class="btn btn-run">RUN</button>
            </form>
        <?php else: ?>
            <div style="height:300px; display:flex; align-items:center; justify-content:center;">
                <form method="post"><button name="action" value="start_battle" class="btn btn-atk" style="padding:20px 40px;">EXPLORE GRASS</button></form>
            </div>
        <?php endif; ?>
    </div>

    <div class="ui-column">
        <form method="post">
            <div class="section-label">Potions</div>
            <div class="grid-3">
                <button name="action" value="use_pot_basic" class="btn btn-pot">Basic (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                <button name="action" value="use_pot_greater" class="btn btn-pot greater">Greater (<?php echo $game['inventory']['greater_potion'] ?? 0; ?>)</button>
                <button name="action" value="use_pot_ancient" class="btn btn-pot ancient">Ancient (<?php echo $game['inventory']['ancient_potion'] ?? 0; ?>)</button>
            </div>

            <div class="section-label">Soul Stones</div>
            <div class="grid-3">
                <button name="action" value="catch_basic" class="btn btn-stone">Basic (<?php echo $game['inventory']['basic'] ?? 0; ?>)</button>
                <button name="action" value="catch_greater" class="btn btn-stone greater">Greater (<?php echo $game['inventory']['greater'] ?? 0; ?>)</button>
                <button name="action" value="catch_ancient" class="btn btn-stone ancient">Ancient (<?php echo $game['inventory']['ancient'] ?? 0; ?>)</button>
            </div>

            <div class="section-label">Monster Roster</div>
            <div class="roster-grid">
                <?php for($i=0; $i<8; $i++): ?>
                    <?php if(isset($game['player']['roster'][$i])): 
                        $m = $game['player']['roster'][$i];
                    ?>
                        <button name="switch_to" value="<?php echo $i; ?>" class="roster-slot <?php echo ($i == $game['player']['active']) ? 'active-slot' : ''; ?>">
                            <img src="images/monsters/<?php echo $m['image']; ?>" style="width:40px; height:40px;">
                            <div style="font-size:0.7em;"><?php echo $m['name']; ?></div>
                            <div class="hp-bar" style="height:4px;"><div class="hp-fill" style="width:<?php echo ($m['hp']/$m['max_hp'])*100; ?>%"></div></div>
                        </button>
                    <?php else: ?>
                        <div class="roster-slot" style="opacity:0.5;">Empty</div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </form>
        <p style="font-size:0.9em; text-align:center; margin-top:20px;">💰 Gold: <?php echo $game['player']['gold']; ?></p>
    </div>
</div>

</body>
</html>