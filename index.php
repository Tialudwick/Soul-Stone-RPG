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

// Starter
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

// SWITCHING 
if (isset($_POST['switch_to'])) {
    $game['player']['active'] = $_POST['switch_to'];
}

//  HEAL TEAM 
if (isset($_POST['heal_team'])) {
    foreach($game['player']['roster'] as &$m) $m['hp'] = $m['max_hp'];
    unset($game['showHeal']);
}

// BATTLE 
if ($action === "start_battle") $game['currentBattle'] = spawnMonster($allMonsters);

if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    if ($action === "attack" && $pm['hp'] > 0) {
        $dmg = rand(5, $pm['attack']);
        $em['hp'] -= $dmg;
        if ($em['hp'] <= 0) {
            $gold = getBattleRewards($game);
            gainXP($pm, 25);
            $game['message'] = "Victory! Gained $gold Gold.";
            $game['currentBattle'] = null;
            $game['showHeal'] = true;
        } else {
            $eDmg = rand(5, $em['attack']);
            $pm['hp'] -= $eDmg;
        }
    }
    
    if (str_starts_with($action, "catch_")) {
        $type = str_replace("catch_", "", $action);
        if ($game['inventory'][$type] > 0 && count($game['player']['roster']) < 8) {
            $game['inventory'][$type]--;
            if (attemptCatch($em['hp'], $em['max_hp'], $soulStones[$type]['bonus'])) {
                $em['id'] = generateMonsterId();
                $game['player']['roster'][] = $em;
                recordCapture($game, $em['name']);
                $game['currentBattle'] = null;
            }
        }
    }
}
saveGame($game);
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <nav>Gold: <?php echo $game['player']['gold']; ?> | <a href="store.php">Shop</a> | <a href="bestiary.php">Bestiary</a></nav>
    
    <?php if(empty($game['player']['roster'])): ?>
        <h2>Pick a Starter</h2>
        <form method="post"><?php for($i=0;$i<3;$i++): ?>
            <button name="pick_id" value="<?php echo $i; ?>">Pick <?php echo $allMonsters[$i]['name']; ?></button>
        <?php endfor; ?></form>
    <?php endif; ?>

    <div class="battle-view">
        <?php if($game['currentBattle']): ?>
            <div class="monster">Wild <?php echo $game['currentBattle']['name']; ?> (HP: <?php echo $em['hp']; ?>)</div>
            <div class="player">Active: <?php echo $pm['name']; ?> (HP: <?php echo $pm['hp']; ?>)</div>
            <form method="post">
                <button name="action" value="attack">Attack</button>
                <?php foreach($soulStones as $k=>$v): ?><button name="action" value="catch_<?php echo $k; ?>">Use <?php echo $k; ?></button><?php endforeach; ?>
            </form>
        <?php elseif(!empty($game['player']['roster'])): ?>
            <form method="post"><button name="action" value="start_battle">Explore</button></form>
        <?php endif; ?>
    </div>

    <?php if(isset($game['showHeal'])): ?>
        <form method="post"><button name="heal_team">Heal Roster (Victory Bonus)</button></form>
    <?php endif; ?>

    <h3>Your Pack</h3>
    <?php foreach($game['player']['roster'] as $i=>$m): ?>
        <form method="post" style="display:inline;">
            <button name="switch_to" value="<?php echo $i; ?>" <?php if($m['hp']<=0)echo 'disabled';?>>
                <?php echo $m['name']; ?> (<?php echo $m['hp']; ?>/<?php echo $m['max_hp']; ?>)
            </button>
        </form>
    <?php endforeach; ?>
</body>
</html>