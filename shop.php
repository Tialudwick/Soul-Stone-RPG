<?php

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/monsters.php';


/* Make sure a game has been selected */

if (empty($_SESSION['save_file'])) {

    header('Location: index.php');
    exit;
}


/* Load the SAME player save used by game.php */

$saveFile = basename($_SESSION['save_file']);

$savePath = __DIR__ . '/saves/' . $saveFile;


if (!is_file($savePath)) {

    unset(
        $_SESSION['save_file'],
        $_SESSION['game']
    );

    header('Location: index.php');
    exit;
}


/* Load player's individual game */

$game = loadPlayerGame($saveFile);


/* Safety checks */

if (!isset($game['player'])) {
    $game['player'] = [];
}

if (!isset($game['player']['gold'])) {
    $game['player']['gold'] = 500;
}

if (!isset($game['player']['roster'])) {
    $game['player']['roster'] = [];
}

if (!isset($game['inventory'])) {
    $game['inventory'] = [];
}


/* Make sure inventory keys exist */

$inventoryDefaults = [

    'basic_potion'   => 0,
    'greater_potion' => 0,
    'ancient_potion' => 0,

    'basic'          => 0,
    'greater'        => 0,
    'ancient'        => 0

];


foreach ($inventoryDefaults as $item => $amount) {

    if (!isset($game['inventory'][$item])) {

        $game['inventory'][$item] = $amount;
    }
}


/* Shop prices */

$prices = [

    'basic_potion'   => 50,

    'greater_potion' => 150,

    'ancient_potion' => 500,

    'basic'          => 100,

    'greater'        => 300,

    'ancient'        => 1000

];


/* Shop purchase */

$message = '';

$messageType = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $item = $_POST['buy'] ?? '';


    /* Make sure the item actually exists */

    if (!isset($prices[$item])) {

        $message = 'That item is not available.';

        $messageType = 'error';

    } else {

        $cost = $prices[$item];


        /* Make sure gold is numeric */

        $game['player']['gold'] =
            (int) $game['player']['gold'];


        /* Check gold */

        if ($game['player']['gold'] < $cost) {

            $message =
                "Not enough gold, traveler!";

            $messageType = 'error';

        } else {

            /* Remove gold */

            $game['player']['gold'] -= $cost;


            /* Add item to inventory */

            if (!isset($game['inventory'][$item])) {

                $game['inventory'][$item] = 0;
            }


            $game['inventory'][$item]++;


            /* Save the player's EXISTING save file */

            $saved = savePlayerGame(
                $game,
                $saveFile
            );


            /* Make sure save succeeded */

            if ($saved === false) {

                /* Undo purchase if save failed */

                $game['player']['gold'] += $cost;

                $game['inventory'][$item]--;

                $message =
                    "The purchase could not be saved. Please try again.";

                $messageType = 'error';

            } else {

                $prettyName =
                    str_replace(
                        '_',
                        ' ',
                        $item
                    );


                $message =
                    "You obtained the "
                    . ucfirst($prettyName)
                    . "!";


                $messageType = 'success';


                /* Keep session data synchronized */

                $_SESSION['game'] = $game;

                $_SESSION['save_file'] = $saveFile;
            }
        }
    }
}


/* Make sure session has newest game data */

$_SESSION['game'] = $game;

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Soul Stone RPG - The Emporium</title>

    <link rel="stylesheet" href="style.css">

</head>
<body>


<!-- NAVIGATION -->

<nav class="top-nav">

    <a href="index.php" class="logo-image-link">

        <img src="images/logo.png" alt="Soul Stone RPG Logo" class="game-logo">

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


<!-- SHOP HEADER -->

<div class="shop-header">

    <h1
        style="
            margin-top:0;
            font-size:3em;
        "
    >
        THE MAGIC EMPORIUM
    </h1>


    <div class="gold-pouch">

        💰

        <?php
        echo number_format(
            (int) $game['player']['gold']
        );
        ?>

        GOLD

    </div>

</div>


<!-- PURCHASE MESSAGE -->

<?php if ($message): ?>

    <div
        class="toast
        <?php
        echo $messageType === 'error'
            ? 'error'
            : '';
        ?>"
    >

        <?php
        echo htmlspecialchars(
            $message
        );
        ?>

    </div>

<?php endif; ?>


<!-- SHOP -->

<form method="post">

    <div class="display-case">


        <!-- BASIC POTION -->

        <div class="item-display potions">

            <div class="item-title">
                Basic Potion
            </div>


            <div class="item-icon">

                <img
                    src="images/items/pot_basic.png"
                    alt="Basic Potion"
                >

            </div>


            <div class="item-desc">
                A standard brew.
                Restores 30 HP.
            </div>


            <span class="price-tag">
                50g
            </span>


            <div style="
                color:#bdc3c7;
                margin-bottom:10px;
            ">

                Owned:

                <?php
                echo (int)
                    $game['inventory']['basic_potion'];
                ?>

            </div>


            <button
                name="buy"
                value="basic_potion"
                class="btn-buy"
                <?php
                echo $game['player']['gold'] < 50
                    ? 'disabled'
                    : '';
                ?>
            >
                PURCHASE
            </button>

        </div>


        <!-- GREATER POTION -->

        <div class="item-display potions">

            <div class="item-title">
                Greater Potion
            </div>


            <div class="item-icon">

                <img
                    src="images/items/pot_greater.png"
                    alt="Greater Potion"
                >

            </div>


            <div class="item-desc">
                A concentrated elixir.
                Restores 80 HP.
            </div>


            <span class="price-tag">
                150g
            </span>


            <div style="
                color:#bdc3c7;
                margin-bottom:10px;
            ">

                Owned:

                <?php
                echo (int)
                    $game['inventory']['greater_potion'];
                ?>

            </div>


            <button
                name="buy"
                value="greater_potion"
                class="btn-buy"
                <?php
                echo $game['player']['gold'] < 150
                    ? 'disabled'
                    : '';
                ?>
            >
                PURCHASE
            </button>

        </div>


        <!-- ANCIENT POTION -->

        <div class="item-display potions">

            <div class="item-title">
                Ancient Potion
            </div>


            <div class="item-icon">

                <img
                    src="images/items/pot_ancient.png"
                    alt="Ancient Potion"
                >

            </div>


            <div class="item-desc">
                Brewed by elders.
                Fully restores HP.
            </div>


            <span class="price-tag">
                500g
            </span>


            <div style="
                color:#bdc3c7;
                margin-bottom:10px;
            ">

                Owned:

                <?php
                echo (int)
                    $game['inventory']['ancient_potion'];
                ?>

            </div>


            <button
                name="buy"
                value="ancient_potion"
                class="btn-buy"
                <?php
                echo $game['player']['gold'] < 500
                    ? 'disabled'
                    : '';
                ?>
            >
                PURCHASE
            </button>

        </div>


        <!-- BASIC STONE -->

        <div class="item-display stones">

            <div class="item-title">
                Basic Stone
            </div>


            <div class="item-icon">

                <img
                    src="images/items/stone_basic.png"
                    alt="Basic Soul Stone"
                >

            </div>


            <div class="item-desc">
                Used to capture
                weak wild monsters.
            </div>


            <span class="price-tag">
                100g
            </span>


            <div style="
                color:#bdc3c7;
                margin-bottom:10px;
            ">

                Owned:

                <?php
                echo (int)
                    $game['inventory']['basic'];
                ?>

            </div>


            <button
                name="buy"
                value="basic"
                class="btn-buy"
                <?php
                echo $game['player']['gold'] < 100
                    ? 'disabled'
                    : '';
                ?>
            >
                PURCHASE
            </button>

        </div>


        <!-- GREATER STONE -->

        <div class="item-display stones">

            <div class="item-title">
                Greater Stone
            </div>


            <div class="item-icon">

                <img
                    src="images/items/stone_greater.png"
                    alt="Greater Soul Stone"
                >

            </div>


            <div class="item-desc">
                Higher success rate
                for mid-tier foes.
            </div>


            <span class="price-tag">
                300g
            </span>


            <div style="
                color:#bdc3c7;
                margin-bottom:10px;
            ">

                Owned:

                <?php
                echo (int)
                    $game['inventory']['greater'];
                ?>

            </div>


            <button
                name="buy"
                value="greater"
                class="btn-buy"
                <?php
                echo $game['player']['gold'] < 300
                    ? 'disabled'
                    : '';
                ?>
            >
                PURCHASE
            </button>

        </div>


        <!-- ANCIENT STONE -->

        <div class="item-display stones">

            <div class="item-title">
                Ancient Stone
            </div>


            <div class="item-icon">

                <img
                    src="images/items/stone_ancient.png"
                    alt="Ancient Soul Stone"
                >

            </div>


            <div class="item-desc">
                The ultimate vessel.
                Catches almost anything.
            </div>


            <span class="price-tag">
                1,000g
            </span>


            <div style="
                color:#bdc3c7;
                margin-bottom:10px;
            ">

                Owned:

                <?php
                echo (int)
                    $game['inventory']['ancient'];
                ?>

            </div>


            <button
                name="buy"
                value="ancient"
                class="btn-buy"
                <?php
                echo $game['player']['gold'] < 1000
                    ? 'disabled'
                    : '';
                ?>
            >
                PURCHASE
            </button>

        </div>


    </div>

</form>


</body>

</html>
```
