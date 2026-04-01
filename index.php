<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// Start battle
if (!$game['currentBattle'] && $action === "start_battle") {
    $game['currentBattle'] = spawnMonster($allMonsters);
    $game['message'] = "A wild {$game['currentBattle']['name']} appears!";
}

// Battle logic
if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $playerMonster = &$game['player']['roster'][$game['player']['active']];
    $enemy = &$game['currentBattle'];

    if ($action === "attack") {
        $damage = rand(5, $playerMonster['attack']);
        $enemy['hp'] -= $damage;
        $game['message'] = "{$playerMonster['name']} hits {$enemy['name']} for $damage damage!";

        if ($enemy['hp'] <= 0) {
            $game['message'] .= " You defeated {$enemy['name']}!";
            gainXP($playerMonster, 10);
            $drop = rand(1,100);
            if ($drop < 60) $game['inventory']['basic']++;
            elseif ($drop < 90) $game['inventory']['greater']++;
            else $game['inventory']['ancient']++;
            $game['currentBattle'] = null;
        } else {
            $enemyDamage = rand(5, $enemy['attack']);
            $playerMonster['hp'] -= $enemyDamage;
            $game['message'] .= " {$enemy['name']} hits back for $enemyDamage damage!";
        }
    }

    // Catch using Soul Stones
    if (str_starts_with($action, "catch_")) {
        $stoneType = str_replace("catch_", "", $action);
        if ($game['inventory'][$stoneType] > 0) {
            $game['inventory'][$stoneType]--;
            $bonus = $soulStones[$stoneType]['bonus'];
            if (attemptCatch($enemy['hp'], $enemy['max_hp'], $bonus)) {
                $game['player']['roster'][] = $enemy;
                $game['message'] = "Captured {$enemy['name']} with a " . ucfirst($stoneType) . " Soul Stone!";
                $game['currentBattle'] = null;
            } else {
                $game['message'] = "The monster broke free!";
            }
        } else {
            $game['message'] = "No $stoneType Soul Stones left!";
        }
    }

    if ($action === "heal") {
        $heal = rand(5,15);
        $playerMonster['hp'] = min($playerMonster['hp'] + $heal, $playerMonster['max_hp']);
        $game['message'] = "{$playerMonster['name']} healed $heal HP!";
    }
}

// Save game state
saveGame($game);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Soul Stone RPG</h1>
<p><?php echo $game['message'] ?? ''; ?></p>

<div class="game-area">
    <!-- Player Monster -->
    <?php if(count($game['player']['roster'])>0): 
        $pm = $game['player']['roster'][$game['player']['active']];
    ?>
        <div class="player">
            <img src="images/monsters/<?php echo $pm['image']; ?>" alt="<?php echo $pm['name']; ?>">
            <div class="hp-bar">
                <div class="hp-inner" style="width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div>
            </div>
            <p><?php echo "{$pm['name']} Lv {$pm['level']} HP: {$pm['hp']}/{$pm['max_hp']}"; ?></p>
        </div>
    <?php endif; ?>

    <!-- Enemy Monster -->
    <?php if($game['currentBattle']): 
        $em = $game['currentBattle'];
    ?>
        <div class="monster">
            <img src="images/monsters/<?php echo $em['image']; ?>" alt="<?php echo $em['name']; ?>">
            <div class="hp-bar">
                <div class="hp-inner" style="width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div>
            </div>
            <p><?php echo "{$em['name']} Lv {$em['level']} HP: {$em['hp']}/{$em['max_hp']}"; ?></p>
        </div>
    <?php endif; ?>
</div>

<!-- Actions -->
<?php if($game['currentBattle'] && count($game['player']['roster'])>0): ?>
<form method="post">
    <button name="action" value="attack">Attack</button>
    <button name="action" value="heal">Heal</button>
    <?php foreach($soulStones as $type=>$stone): ?>
        <button name="action" value="catch_<?php echo $type; ?>">
            <?php echo $stone['name'] ?> (<?php echo $game['inventory'][$type]; ?>)
        </button>
    <?php endforeach; ?>
</form>
<?php else: ?>
<form method="post">
    <button name="action" value="start_battle">Find Wild Monster</button>
</form>
<?php endif; ?>

<!-- Player Inventory -->
<h2>Soul Stones</h2>
<ul>
<?php foreach($game['inventory'] as $type=>$amt): ?>
    <li><?php echo ucfirst($type) . ": $amt"; ?></li>
<?php endforeach; ?>
</ul>

<!-- Player Roster -->
<h2>Your Monsters</h2>
<?php if(count($game['player']['roster'])>0): ?>
<ul>
<?php foreach($game['player']['roster'] as $i=>$m): ?>
    <li><?php echo "{$m['name']} - Lv {$m['level']} HP: {$m['hp']}/{$m['max_hp']}"; ?>
        <?php if($i === $game['player']['active']) echo " (Active)"; ?>
    </li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No monsters yet!</p>
<?php endif; ?>

</body>
</html>