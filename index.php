<?php
session_start();
include "functions.php";
include "monsters.php";

// SECURITY CHECK
if (!file_exists("save.json") || filesize("save.json") === 0) {
    header("Location: main.php");
    exit;
}

$game = loadGame();
$action = $_POST['action'] ?? null;

// STARTER SELECTION
if (empty($game['player']['roster']) && isset($_POST['pick_starter_id'])) {
    $idx = $_POST['pick_starter_id'];
    $selected = $allMonsters[$idx];

    // Start monster with its unique ID and starting stats
    $selected['id'] = generateMonsterId(); 
    $selected['hp'] = $selected['max_hp'];
    $selected['xp'] = 0;
    
    $game['player']['roster'][] = $selected;
    $game['inventory']['potions'] = 10; // 10 free potions
    $game['player']['gold'] = 50;       // Starting money
    $game['message'] = "You chose {$selected['name']}! Adventure awaits.";
    
    saveGame($game);
    header("Location: index.php");
    exit;
}

//DISCARD MONSTER
if (isset($_POST['discard_id'])) {
    $mId = $_POST['discard_id'];
    if (discardFromRoster($game, $mId)) {
        $game['message'] = "Monster released back into the wild.";
        saveGame($game);
    }
}

// START BATTLE
if (!$game['currentBattle'] && $action === "start_battle") {
    $game['currentBattle'] = spawnMonster($allMonsters);
    $game['message'] = "A wild {$game['currentBattle']['name']} appears!";
}

// BATTLE ACTIONS (Attack, Heal, Catch)
if ($game['currentBattle'] && count($game['player']['roster']) > 0) {
    $playerMonster = &$game['player']['roster'][$game['player']['active']];
    $enemy = &$game['currentBattle'];

    if ($action === "attack") {
        $damage = rand(5, $playerMonster['attack']);
        $enemy['hp'] -= $damage;
        
        if ($enemy['hp'] <= 0) {
            $goldGained = getBattleRewards($game);
            $game['message'] = "Victory! You gained $goldGained gold.";
            gainXP($playerMonster, 10);
            $game['currentBattle'] = null;
        } else {
            $enemyDamage = rand(5, $enemy['attack']);
            $playerMonster['hp'] -= $enemyDamage;
            $game['message'] = "{$playerMonster['name']} hit for $damage! Enemy hit back for $enemyDamage.";
        }
    }

    if ($action === "heal") {
        if (($game['inventory']['potions'] ?? 0) > 0) {
            $game['inventory']['potions']--;
            $playerMonster['hp'] = min($playerMonster['hp'] + 25, $playerMonster['max_hp']);
            $game['message'] = "Healed 25 HP!";
        } else {
            $game['message'] = "No potions left!";
        }
    }

    if (str_starts_with($action, "catch_")) {
        if (count($game['player']['roster']) >= 8) {
            $game['message'] = "Pack is full! Discard someone first.";
        } else {
            $type = str_replace("catch_", "", $action);
            if ($game['inventory'][$type] > 0) {
                $game['inventory'][$type]--;
                if (attemptCatch($enemy['hp'], $enemy['max_hp'], $soulStones[$type]['bonus'])) {
                    $enemy['id'] = generateMonsterId(); // Give it an ID!
                    $game['player']['roster'][] = $enemy;
                    $game['message'] = "Captured {$enemy['name']}!";
                    $game['currentBattle'] = null;
                } else {
                    $game['message'] = "It broke free!";
                }
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
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div style="background:#333; color:white; padding:10px; display:flex; justify-content: space-around;">
        <span>Gold: **<?php echo $game['player']['gold'] ?? 0; ?>**</span>
        <span>Potions: **<?php echo $game['inventory']['potions'] ?? 0; ?>**</span>
        <a href="store.php" style="color:yellow;">Visit Store</a>
        <a href="main.php" style="color:white;">Menu</a>
    </div>

    <h1>Soul Stone RPG</h1>
    <p style="text-align:center; color:blue; font-weight:bold;">
        <?php echo $game['message'] ?? 'Welcome!'; ?>
    </p>

    <?php if(empty($game['player']['roster'])): ?>
        <div style="text-align:center; border:2px solid #333; padding:20px; margin:20px;">
            <h2>Choose Your Starter</h2>
            <form method="post">
                <?php for($i=0; $i<=2; $i++): $m = $allMonsters[$i]; ?>
                    <div style="display:inline-block; margin:10px; border:1px solid #ccc; padding:10px;">
                        <img src="images/monsters/<?php echo $m['image']; ?>" width="100"><br>
                        <strong><?php echo $m['name']; ?></strong><br>
                        <button type="submit" name="pick_starter_id" value="<?php echo $i; ?>">Choose</button>
                    </div>
                <?php endfor; ?>
            </form>
        </div>
    <?php endif; ?>

    <div class="game-area" style="display:flex; justify-content:center; gap:50px; margin:20px;">
        <?php if(!empty($game['player']['roster'])): 
            $pm = $game['player']['roster'][$game['player']['active']]; ?>
            <div class="player-side">
                <h3>Your Fighter</h3>
                <img src="images/monsters/<?php echo $pm['image']; ?>" width="120">
                <div style="width:120px; height:10px; background:#eee; border:1px solid #000;">
                    <div style="width:<?php echo ($pm['hp']/$pm['max_hp'])*100; ?>%; height:100%; background:green;"></div>
                </div>
                <p><?php echo $pm['name']; ?> (Lv <?php echo $pm['level']; ?>)</p>
            </div>
        <?php endif; ?>

        <?php if($game['currentBattle']): $em = $game['currentBattle']; ?>
            <div class="enemy-side">
                <h3>Wild Monster</h3>
                <img src="images/monsters/<?php echo $em['image']; ?>" width="120">
                <div style="width:120px; height:10px; background:#eee; border:1px solid #000;">
                    <div style="width:<?php echo ($em['hp']/$em['max_hp'])*100; ?>%; height:100%; background:red;"></div>
                </div>
                <p><?php echo $em['name']; ?> (Lv <?php echo $em['level']; ?>)</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align:center;">
        <?php if($game['currentBattle']): ?>
            <form method="post">
                <button name="action" value="attack">Attack</button>
                <button name="action" value="heal">Use Potion</button>
                <br><br>
                <?php foreach($soulStones as $type=>$stone): ?>
                    <button name="action" value="catch_<?php echo $type; ?>">
                        Use <?php echo $stone['name']; ?> (<?php echo $game['inventory'][$type] ?? 0; ?>)
                    </button>
                <?php endforeach; ?>
            </form>
        <?php elseif(!empty($game['player']['roster'])): ?>
            <form method="post">
                <button name="action" value="start_battle" style="padding:10px 20px;">Explore Tall Grass</button>
            </form>
        <?php endif; ?>
    </div>

    <hr>
    <h2>Your Monster Pack (<?php echo count($game['player']['roster']); ?>/8)</h2>
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px;">
        <?php foreach($game['player']['roster'] as $m): ?>
            <div style="border:1px solid #ccc; padding:5px; text-align:center;">
                <strong><?php echo $m['name']; ?></strong><br>
                Lv: <?php echo $m['level']; ?><br>
                <form method="post">
                    <input type="hidden" name="discard_id" value="<?php echo $m['id']; ?>">
                    <button type="submit" style="font-size:0.7em; color:red;">Discard</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>