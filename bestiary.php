<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();

// Track which monsters the player has ever owned (using the 'pokedex' or 'discovered' logic)
// To make this better, you should ideally add a 'pokedex' array to your save.json 
// For now, we check the current roster.
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
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #1a1a1d; margin: 0; color: #fff; }
        
        /* NAVIGATION */
        .top-nav { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3498db; color: #333; }
        .nav-links a { text-decoration: none; color: #2c3e50; font-weight: bold; margin-left: 20px; transition: 0.3s; }
        .nav-links a:hover { color: #3498db; }

        .header-area { text-align: center; padding: 40px 0; background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/library_bg.jpg'); background-size: cover; border-bottom: 4px solid #34495e; }
        h1 { margin: 0; font-size: 3em; letter-spacing: 5px; text-shadow: 2px 2px 10px rgba(0,0,0,0.5); }

        .container { max-width: 1200px; margin: -30px auto 50px; padding: 20px; }

        /* RARITY SECTIONS */
        .rarity-title { 
            background: #34495e; padding: 10px 20px; border-radius: 5px; margin: 40px 0 20px; 
            border-left: 10px solid #f1c40f; text-transform: uppercase; letter-spacing: 2px;
        }

        .bestiary-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }

        /* MONSTER CARD (Matches Battle Card Style) */
        .monster-card {
            background: #f4e4bc; /* Parchment */
            border: 6px solid #3d2b1f; /* Wood Border */
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
            position: relative;
            color: #3d2b1f;
        }

        .monster-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.7); }

        .image-well { 
            background: #fff; border: 2px solid #3d2b1f; height: 140px; 
            display: flex; align-items: center; justify-content: center; 
            margin-bottom: 10px; border-radius: 4px; overflow: hidden;
        }
        .image-well img { width: 80%; height: auto; object-fit: contain; }

        /* TYPE STICKER */
        .type-sticker { 
            position: absolute; top: -10px; right: -10px; width: 40px; height: 40px; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            color: white; font-weight: bold; font-size: 0.6em; border: 2px solid #fff; 
            box-shadow: 0 3px 6px rgba(0,0,0,0.3); z-index: 5; text-transform: uppercase;
        }
        .fire { background: #e67e22; } .water { background: #3498db; } .earth { background: #27ae60; }

        /* UNKNOWN STATE */
        .unknown-card { background: #2c3e50; border-color: #1a1a1d; color: #7f8c8d; }
        .unknown-card .image-well { background: #1a1a1d; border-color: #000; }
        .unknown-img { filter: brightness(0); opacity: 0.3; }
        .unknown-text { font-style: italic; letter-spacing: 3px; font-weight: bold; margin-top: 10px; }

        .stats-box { 
            background: rgba(0,0,0,0.05); border-radius: 4px; padding: 5px; 
            font-size: 0.85em; font-family: monospace; border: 1px solid rgba(0,0,0,0.1); 
        }
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

<div class="header-area">
    <h1>MONSTER BESTIARY</h1>
    <p>Discover and document every creature in the realm.</p>
</div>

<div class="container">
    <?php 
    // Group monsters by rarity for a cleaner layout
    $grouped = ['basic' => [], 'greater' => [], 'ancient' => []];
    foreach($allMonsters as $m) { $grouped[$m['rarity']][] = $m; }

    foreach($grouped as $rarity => $monsters): ?>
        
        <h2 class="rarity-title"><?php echo $rarity; ?> Monsters</h2>
        
        <div class="bestiary-grid">
            <?php foreach($monsters as $monster): 
                $isFound = in_array($monster['name'], $discovered);
            ?>
                <?php if($isFound): ?>
                    <div class="monster-card">
                        <div class="type-sticker <?php echo $monster['type']; ?>"><?php echo $monster['type']; ?></div>
                        <div style="font-weight:bold; text-transform:uppercase; margin-bottom:8px; border-bottom:1px solid #3d2b1f;">
                            <?php echo $monster['name']; ?>
                        </div>
                        <div class="image-well">
                            <img src="images/monsters/<?php echo $monster['image']; ?>">
                        </div>
                        <div class="stats-box">
                            BASE HP: <?php echo $monster['max_hp']; ?><br>
                            BASE ATK: <?php echo $monster['attack']; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="monster-card unknown-card">
                        <div class="image-well">
                            <img src="images/monsters/<?php echo $monster['image']; ?>" class="unknown-img">
                        </div>
                        <div class="unknown-text">???</div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

    <?php endforeach; ?>
</div>

</body>
</html>