<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
// Track which monsters the player has seen/caught based on their roster history
$discovered = [];
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as $m) {
        $discovered[] = $m['name'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG - Bestiary</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #2c3e50; margin: 0; color: #333; }
        
        .top-nav { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3498db; }
        .nav-links a { text-decoration: none; color: #2c3e50; font-weight: bold; margin-left: 20px; transition: 0.3s; }
        .nav-links a:hover { color: #3498db; }

        .container { max-width: 1000px; margin: 40px auto; padding: 25px; background: #bdc3c7; border-radius: 8px; border: 4px solid #3498db; }
        
        .bestiary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        
        .monster-card { background: #ecf0f1; border: 2px solid #7f8c8d; border-radius: 8px; padding: 15px; text-align: center; transition: 0.3s; }
        .monster-card img { width: 100px; height: 100px; object-fit: contain; background: white; border-radius: 4px; margin-bottom: 10px; }
        
        /* Hidden Monster Style */
        .unknown { filter: brightness(0); opacity: 0.5; }
        .unknown-text { color: #7f8c8d; font-style: italic; }

        .type-badge { font-size: 0.7em; padding: 3px 8px; border-radius: 4px; color: white; text-transform: uppercase; font-weight: bold; }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #27ae60; }
        
        h1 { color: #fff; text-align: center; margin-top: 20px; text-transform: uppercase; letter-spacing: 2px; }
    </style>
</head>
<body>

<div class="top-nav">
    <div class="logo"><strong>SOUL STONE RPG</strong></div>
    <div class="nav-links">
        <a href="bestiary.php">BESTIARY</a>
        <a href="shop.php">SHOP</a>
        <a href="index.php">HOME</a>
    </div>
</div>

<h1>Monster Bestiary</h1>

<div class="container">
    <div class="bestiary-grid">
        <?php foreach($allMonsters as $monster): 
            $isFound = in_array($monster['name'], $discovered);
        ?>
            <div class="monster-card">
                <?php if($isFound): ?>
                    <span class="type-badge <?php echo $monster['type']; ?>"><?php echo $monster['type']; ?></span><br>
                    <img src="images/monsters/<?php echo $monster['image']; ?>">
                    <div style="font-weight:bold;"><?php echo $monster['name']; ?></div>
                    <div style="font-size:0.8em; color:#555;">Base HP: <?php echo $monster['max_hp']; ?></div>
                <?php else: ?>
                    <img src="images/monsters/<?php echo $monster['image']; ?>" class="unknown">
                    <div class="unknown-text">???</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>