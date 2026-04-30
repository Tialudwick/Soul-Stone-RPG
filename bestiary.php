<?php
include "functions.php";
include "monsters.php";
$game = loadGame();
$disc = $game['player']['discovered'] ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <style>
        .grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .m-card { border: 1px solid #333; padding: 10px; text-align:center; }
        .locked img { filter: grayscale(100%); opacity: 0.3; }
    </style>
</head>
<body>
    <h1>Bestiary</h1>
    <a href="index.php">Back</a>
    <div class="grid">
        <?php foreach($allMonsters as $m): 
            $unlocked = in_array($m['name'], $disc); ?>
            <div class="m-card <?php echo $unlocked ? '' : 'locked'; ?>">
                <img src="images/monsters/<?php echo $m['image']; ?>" width="80">
                <p><?php echo $unlocked ? $m['name'] : "???"; ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>