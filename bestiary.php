<?php

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/monsters.php';


/* MAKE SURE A GAME IS SELECTED */

if (empty($_SESSION['save_file'])) {
    header('Location: index.php');
    exit;
}


/* LOAD THE CURRENT PLAYER'S SAVE FILE */

$saveFile = basename($_SESSION['save_file']);

$game = loadPlayerGame($saveFile);


/* SAFETY CHECKS */

if (!is_array($game)) {
    $game = [];
}

if (
    !isset($game['player']) ||
    !is_array($game['player'])
) {
    $game['player'] = [];
}

if (
    !isset($game['player']['roster']) ||
    !is_array($game['player']['roster'])
) {
    $game['player']['roster'] = [];
}


/* GET ALL DISCOVERED / CAPTURED MONSTERS */

$discovered = [];

foreach ($game['player']['roster'] as $monster) {

    if (!empty($monster['name'])) {

        $discovered[] = strtolower(
            trim($monster['name'])
        );

    }

}


/* REMOVE DUPLICATES */

$discovered = array_unique($discovered);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soul Stone RPG - Bestiary</title>
    <link rel="stylesheet" href="style.css">    

</head>
<body>


<!-- NAVIGATION -->

<nav class="top-nav">

    <a href="index.php" class="logo-image-link">

    <img src="images/logo.png" alt="Soul Stone RPG Logo" class="game-logo">
    </a>


<div class="nav-links">

    <a href="bestiary.php">BESTIARY</a>
    <a href="shop.php">SHOP</a>
    <a href="index.php">HOME</a>

</div>

</nav>


<!-- HEADER  -->

<div class="header-area">

    <h1>MONSTER BESTIARY</h1>
    <p>Discover and document every creature in the realm.</p>

</div>


<!-- BESTIARY -->

<div class="bestiary-container">

    <?php

    /* GROUP MONSTERS BY RARITY */

    $grouped = [
        'basic'   => [],
        'greater' => [],
        'ancient' => []
    ];


    foreach ($allMonsters as $monster) {

        $rarity =
        strtolower(
            $monster['rarity'] ?? 'basic'
        );

        if (isset($grouped[$rarity])) {

            $grouped[$rarity][] = $monster;

        }

    }


    foreach ($grouped as $rarity => $monsters):

        ?>


<!-- RARITY TITLE -->

<h2 class="rarity-title">

    <?php echo htmlspecialchars(ucfirst($rarity));?>Monsters
</h2>


<!-- MONSTER GRID -->

<div class="bestiary-grid">
    <?php foreach ($monsters as $monster): ?>

        <?php

/* Check if this monster has ever been captured.*/

$monsterName =
strtolower(trim($monster['name']));


$isFound =
in_array(
    $monsterName,
    $discovered,
    true
);

?>


<?php if ($isFound): ?>


<!-- CAPTURED / DISCOVERED MONSTER -->

<div class="bestiary-monster-card">


    <div class="bestiary-type-sticker
    <?php echo htmlspecialchars($monster['type']);?>">

    <?php echo strtoupper(htmlspecialchars($monster['type']));?>

</div>


<div class="bestiary-monster-name">

    <?php echo htmlspecialchars($monster['name']);?>

</div>


<div class="bestiary-image-well">

    <img src="images/monsters/<?php echo htmlspecialchars($monster['image']);?>"alt="<?php echo htmlspecialchars(
    $monster['name']);?>">

</div>


<div class="bestiary-stats-box">

    BASE HP:
    <?php echo $monster['max_hp'];?>

    <br>

    BASE ATK:
    <?php echo $monster['attack'];?>

</div>


</div>


<?php else: ?>


<!-- NOT CAPTURED / UNKNOWN MONSTER-->

<div class="bestiray-monster-card bestiary-unknown-card">


    <div class="bestiary-image-well">

        <img
        src="images/monsters/<?php
        echo htmlspecialchars($monster['image']);?>"
    class="bestiary-unknown-img" alt="Unknown Monster">

</div>


<div class="bestiary-unknown-text">???</div>


</div>


<?php endif; ?>


<?php endforeach; ?>


</div>


<?php endforeach; ?>


</div>


</body>

</html>