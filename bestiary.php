<?php

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/monsters.php';


/*
| MAKE SURE A GAME IS SELECTED
*/

if (empty($_SESSION['save_file'])) {
    header('Location: index.php');
    exit;
}


/*
| LOAD THE CURRENT PLAYER'S SAVE FILE
*/

$saveFile = basename($_SESSION['save_file']);

$game = loadPlayerGame($saveFile);


/*
| SAFETY CHECKS
*/

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


/*
| GET ALL DISCOVERED / CAPTURED MONSTERS

*/

$discovered = [];

foreach ($game['player']['roster'] as $monster) {

    if (!empty($monster['name'])) {

        $discovered[] = strtolower(
            trim($monster['name'])
        );

    }

}


/*
| REMOVE DUPLICATES
*/

$discovered = array_unique($discovered);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Soul Stone RPG - Bestiary</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        /* HEADER */

        .header-area {
            text-align: center;
            padding: 40px 0;

            background:
                linear-gradient(
                    rgba(0,0,0,0.7),
                    rgba(0,0,0,0.7)
                ),
                url('images/library_bg.jpg');

            background-size: cover;
            background-position: center;

            border-bottom: 4px solid #34495e;
        }


        h1 {
            margin: 0;
            font-size: 3em;
            letter-spacing: 5px;

            text-shadow:
                2px 2px 10px
                rgba(0,0,0,0.5);
        }


        .container {
            max-width: 1200px;
            margin: -30px auto 50px;
            padding: 20px;
        }


        /* RARITY SECTIONS  */

        .rarity-title {

            background: #34495e;

            padding: 10px 20px;

            border-radius: 5px;

            margin: 40px 0 20px;

            border-left: 10px solid #f1c40f;

            text-transform: uppercase;

            letter-spacing: 2px;

        }


        .bestiary-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    auto-fill,
                    minmax(220px, 1fr)
                );

            gap: 25px;

        }


        /* MONSTER CARD */

        .monster-card {

            background: #f4e4bc;

            border: 6px solid #3d2b1f;

            border-radius: 10px;

            padding: 12px;

            text-align: center;

            transition: 0.3s;

            box-shadow:
                0 10px 20px
                rgba(0,0,0,0.5);

            position: relative;

            color: #3d2b1f;

        }


        .monster-card:hover {

            transform: translateY(-5px);

            box-shadow:
                0 15px 30px
                rgba(0,0,0,0.7);

        }


        /* IMAGE AREA */

        .image-well {

            background: #fff;

            border: 2px solid #3d2b1f;

            height: 140px;

            display: flex;

            align-items: center;

            justify-content: center;

            margin-bottom: 10px;

            border-radius: 4px;

            overflow: hidden;

        }


        .image-well img {

            width: 80%;

            height: auto;

            object-fit: contain;

        }


        /* TYPE STICKER */

        .type-sticker {

            position: absolute;

            top: -10px;

            right: -10px;

            width: 40px;

            height: 40px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: white;

            font-weight: bold;

            font-size: 0.6em;

            border: 2px solid #fff;

            box-shadow:
                0 3px 6px
                rgba(0,0,0,0.3);

            z-index: 5;

            text-transform: uppercase;

        }


        .fire {
            background: #e67e22;
        }


        .water {
            background: #3498db;
        }


        .earth {
            background: #27ae60;
        }


        /* UNKNOWN / NOT CAPTURED MONSTER */

        .unknown-card {

            background: #2c3e50;

            border-color: #1a1a1d;

            color: #7f8c8d;

        }


        .unknown-card .image-well {

            background: #1a1a1d;

            border-color: #000;

        }


       /* grey scale monster image */

        .unknown-img {

            filter:
                grayscale(100%)
                brightness(0.45);

            opacity: 0.5;

        }


        .unknown-text {

            font-style: italic;

            letter-spacing: 3px;

            font-weight: bold;

            margin-top: 10px;

            color: #95a5a6;

        }


        /*  DISCOVERED MONSTER NAME */

        .monster-name {

            font-weight: bold;

            text-transform: uppercase;

            margin-bottom: 8px;

            border-bottom:
                1px solid #3d2b1f;

        }


        /* STATS  */

        .stats-box {

            background:
                rgba(0,0,0,0.05);

            border-radius: 4px;

            padding: 5px;

            font-size: 0.85em;

            font-family: monospace;

            border:
                1px solid
                rgba(0,0,0,0.1);

        }

    </style>

</head>


<body>


<!-- NAVIGATION -->

<nav class="top-nav">

    <a
        href="index.php"
        class="logo-image-link"
    >

        <img
            src="images/logo.png"
            alt="Soul Stone RPG Logo"
            class="game-logo"
        >

    </a>


    <div class="nav-links">

        <a href="bestiary.php">
            BESTIARY
        </a>

        <a href="shop.php">
            SHOP
        </a>

        <a href="index.php">
            HOME
        </a>

    </div>

</nav>


<!-- HEADER  -->

<div class="header-area">

    <h1>
        MONSTER BESTIARY
    </h1>

    <p>
        Discover and document every creature
        in the realm.
    </p>

</div>


<!-- BESTIARY -->

<div class="container">


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

        <?php
        echo htmlspecialchars(
            ucfirst($rarity)
        );
        ?>

        Monsters

    </h2>


    <!-- MONSTER GRID -->

    <div class="bestiary-grid">


    <?php foreach ($monsters as $monster): ?>


        <?php

        /*
         * Check if this monster has ever been captured.
         */

        $monsterName =
            strtolower(
                trim(
                    $monster['name']
                )
            );


        $isFound =
            in_array(
                $monsterName,
                $discovered,
                true
            );

        ?>


        <?php if ($isFound): ?>


            <!-- CAPTURED / DISCOVERED MONSTER -->

            <div class="monster-card">


                <div
                    class="
                        type-sticker
                        <?php
                        echo htmlspecialchars(
                            $monster['type']
                        );
                        ?>
                    "
                >

                    <?php
                    echo strtoupper(
                        htmlspecialchars(
                            $monster['type']
                        )
                    );
                    ?>

                </div>


                <div class="monster-name">

                    <?php
                    echo htmlspecialchars(
                        $monster['name']
                    );
                    ?>

                </div>


                <div class="image-well">

                    <img
                        src="images/monsters/<?php
                            echo htmlspecialchars(
                                $monster['image']
                            );
                        ?>"
                        alt="<?php
                            echo htmlspecialchars(
                                $monster['name']
                            );
                        ?>"
                    >

                </div>


                <div class="stats-box">

                    BASE HP:
                    <?php
                    echo $monster['max_hp'];
                    ?>

                    <br>

                    BASE ATK:
                    <?php
                    echo $monster['attack'];
                    ?>

                </div>


            </div>


        <?php else: ?>


            <!-- ===============================
                 NOT CAPTURED / UNKNOWN MONSTER
            ================================ -->

            <div class="monster-card unknown-card">


                <div class="image-well">

                    <img
                        src="images/monsters/<?php
                            echo htmlspecialchars(
                                $monster['image']
                            );
                        ?>"
                        class="unknown-img"
                        alt="Unknown Monster"
                    >

                </div>


                <div class="unknown-text">

                    ???

                </div>


            </div>


        <?php endif; ?>


    <?php endforeach; ?>


    </div>


<?php endforeach; ?>


</div>


</body>

</html>