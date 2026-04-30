<?php
session_start();
include "functions.php";
include "monsters.php";

// Redirect to main page if no save exists
if (!file_exists("save.json") || filesize("save.json") === 0) {
    header("Location: main.php");
    exit;
}

$game = loadGame();
$action = $_POST['action'] ?? null;

//Starter selection if roster is empty
if (empty($game['player']['roster']) && isset($_POST['pick_starter'])) {
    $starterIndex = $_POST['starter_index'];
    $selected = $allMonsters[$starterIndex];
    $selected['hp'] = $selected['max_hp'];
    $selected['xp'] = 0;
    
    $game['player']['roster'][] = $selected;
    $game['inventory']['potions'] = 10; // Give the 10 free potions
    $game['player']['gold'] = 50;       // Starting pocket money
    $game['message'] = "You chose {$selected['name']} and received 10 Potions!";
    saveGame($game);
}

//discard 

if (isset($_POST['discard_id'])) {
    $idx = $_POST['discard_id'];
    $name = $game['player']['roster'][$idx]['name'];
    if (discardFromRoster($game, $idx)) {
        $game['message'] = "Released $name back into the wild.";
        saveGame($game);
    }
}

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
            // --- 3. MONEY REWARDS ADDED HERE ---
            $goldGained = getBattleRewards($game);
            $game['message'] = "You defeated {$enemy['name']}! Gained $goldGained gold.";
            
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

    // Catch logic (Limited to 8)
    if (str_starts_with($action, "catch_")) {
        if (count($game['player']['roster']) >= 8) {
            $game['message'] = "Your pack is full! Discard a monster first.";
        } else {
            $stoneType = str_replace("catch_", "", $action);
            if ($game['inventory'][$stoneType] > 0) {
                $game['inventory'][$stoneType]--;
                $bonus = $soulStones[$stoneType]['bonus'];
                if (attemptCatch($enemy['hp'], $enemy['max_hp'], $bonus)) {
                    $game['player']['roster'][] = $enemy;
                    $game['message'] = "Captured {$enemy['name']}!";
                    $game['currentBattle'] = null;
                } else {
                    $game['message'] = "The monster broke free!";
                }
            }
        }
    }

    if ($action === "heal") {
        if (($game['inventory']['potions'] ?? 0) > 0) {
            $game['inventory']['potions']--;
            $heal = 20;
            $playerMonster['hp'] = min($playerMonster['hp'] + $heal, $playerMonster['max_hp']);
            $game['message'] = "Used a Potion! {$playerMonster['name']} healed $heal HP.";
        } else {
            $game['message'] = "No potions left!";
        }
    }
}

saveGame($game);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="top-nav" style="background:#f4f4f4; padding:10px; border-bottom:1px solid #ccc;">
    <strong>Gold: <?php echo $game['player']['gold'] ?? 0; ?></strong> | 
    <a href="store.php">Go to Store</a> | 
    <a href="main.php">Main Menu</a>
</div>

<h1>Soul Stone RPG</h1>
<p><strong><?php echo $game['message'] ?? ''; ?></strong></p>

<?php if(empty($game['player']['roster'])): ?>
    <div class="starter-box" style="text-align:center; border:2px solid #333; padding:20px;">
        <h2>Choose Your Starter</h2>
        <form method="post">
            <?php foreach([0, 1, 2] as $idx): // Picks first 3 monsters from monsters.php ?>
                <button name="pick_starter" value="1" style="margin:10px;">
                    <input type="hidden" name="starter_index" value="<?php echo $idx; ?>">
                    Pick <?php echo $allMonsters[$idx]['name']; ?>
                </button>
            <?php endforeach; ?>
        </form>
    </div>
<?php endif; ?>

<div class="game-area">
    <?php if(count($game['player']['roster'])>0): 
        $pm = $game['player']['roster'][$game['player']['active']];
    ?>
        <div class="player">
            <img src="images/monsters/<?php echo $pm['image']; ?>" width="100">
            <div class="hp-bar"><div class="hp-inner" style="width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%"></div></div>
            <p><?php echo "{$pm['name']} Lv {$pm['level']} (HP: {$pm['hp']}/{$pm['max_hp']})"; ?></p>
        </div>
    <?php endif; ?>

    <?php if($game['currentBattle']): 
        $em = $game['currentBattle'];
    ?>
        <div class="monster">
            <img src="images/monsters/<?php echo $em['image']; ?>" width="100">
            <div class="hp-bar"><div class="hp-inner" style="width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%"></div></div>
            <p><?php echo "{$em['name']} Lv {$em['level']} (HP: {$em['hp']}/{$em['max_hp']})"; ?></p>
        </div>
    <?php endif; ?>
</div>

<?php if($game['currentBattle'] && count($game['player']['roster'])>0): ?>
<form method="post">
    <button name="action" value="attack">Attack</button>
    <button name="action" value="heal">Use Potion (<?php echo $game['inventory']['potions'] ?? 0; ?>)</button>
    <br><br>
    <?php foreach($soulStones as $type=>$stone): ?>
        <button name="action" value="catch_<?php echo $type; ?>">
            Catch with <?php echo $stone['name'] ?> (<?php echo $game['inventory'][$type]; ?>)
        </button>
    <?php endforeach; ?>
</form>
<?php elseif(!empty($game['player']['roster'])): ?>
<form method="post">
    <button name="action" value="start_battle">Explore for Monsters</button>
</form>
<?php endif; ?>

<h2>Your Pack (<?php echo count($game['player']['roster']); ?>/8)</h2>
<ul style="list-style:none; padding:0;">
<?php foreach($game['player']['roster'] as $i=>$m): ?>
    <li style="margin-bottom:10px; border-bottom:1px solid #eee;">
        <?php echo "{$m['name']} - Lv {$m['level']}"; ?>
        <form method="post" style="display:inline; margin-left:10px;">
            <input type="hidden" name="discard_id" value="<?php echo $i; ?>">
            <button type="submit" style="color:red; font-size:0.8em;" onclick="return confirm('Release?')">Discard</button>
        </form>
    </li>
<?php endforeach; ?>
</ul>

</body>
</html>