```php
<?php
// ============================================================
// SOUL STONE RPG
// START.PHP
// Main Menu + Starter Selection
// ============================================================

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/monsters.php';


// ============================================================
// VARIABLES
// ============================================================

$message = '';
$messageType = '';


// ============================================================
// CHECK IF WE ARE SELECTING A STARTER
// ============================================================

$selectingStarter = isset($_SESSION['selecting_starter'])
    && $_SESSION['selecting_starter'] === true;


// ============================================================
// HANDLE NEW GAME
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['new_game'])
) {

    // Clear any previous game session
    unset($_SESSION['battle_code']);

    // Tell start.php to display starter selection
    $_SESSION['selecting_starter'] = true;

    $selectingStarter = true;
}


// ============================================================
// HANDLE STARTER SELECTION
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['choose_starter'])
) {

    $starterName = strtolower(
        trim($_POST['starter'] ?? '')
    );

    // Get valid starter monsters
    $starters = getStarterMonsters($allMonsters);

    // Make sure selected starter is valid
    if (
        $starterName === ''
        || !isset($starters[$starterName])
    ) {

        $message = 'Please choose a valid starter monster.';
        $messageType = 'error';

        $_SESSION['selecting_starter'] = true;
        $selectingStarter = true;

    } else {

        // ----------------------------------------------------
        // Create a completely fresh game
        // ----------------------------------------------------

        $game = createNewGame();


        // ----------------------------------------------------
        // Add selected starter
        // ----------------------------------------------------

        startGameWithStarter(
            $game,
            $starters[$starterName]
        );


        // ----------------------------------------------------
        // Give player starting inventory
        // ----------------------------------------------------

        $game['inventory']['basic_potion'] = 3;
        $game['inventory']['greater_potion'] = 1;
        $game['inventory']['ancient_potion'] = 0;

        $game['inventory']['basic'] = 5;
        $game['inventory']['greater'] = 1;
        $game['inventory']['ancient'] = 0;


        // ----------------------------------------------------
        // Create Battle Code
        // ----------------------------------------------------

        try {

            $battleCode = createBattleSave($game);

        } catch (Throwable $e) {

            $message =
                'Unable to create your game save. '
                . 'Please make sure the saves folder is writable.';

            $messageType = 'error';

            $_SESSION['selecting_starter'] = true;
            $selectingStarter = true;

            $battleCode = null;
        }


        // ----------------------------------------------------
        // Start game
        // ----------------------------------------------------

        if ($battleCode !== null) {

            $_SESSION['battle_code'] =
                $battleCode;

            unset(
                $_SESSION['selecting_starter']
            );

            header(
                'Location: game.php'
            );

            exit;
        }
    }
}


// ============================================================
// HANDLE CANCEL STARTER SELECTION
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_starter'])
) {

    unset(
        $_SESSION['selecting_starter']
    );

    unset(
        $_SESSION['battle_code']
    );

    $selectingStarter = false;
}


// ============================================================
// HANDLE CONTINUE GAME
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['continue_game'])
) {

    $battleCode = strtoupper(
        trim($_POST['battle_code'] ?? '')
    );


    // Remove spaces
    $battleCode = str_replace(
        ' ',
        '',
        $battleCode
    );


    // --------------------------------------------------------
    // Make sure something was entered
    // --------------------------------------------------------

    if ($battleCode === '') {

        $message =
            'Please enter your Battle Code.';

        $messageType =
            'error';

    } else {

        // ----------------------------------------------------
        // Try to load saved game
        // ----------------------------------------------------

        $savedGame =
            loadBattleSave($battleCode);


        if ($savedGame !== null) {

            // Make sure this is actually a started game
            if (
                empty($savedGame['starter_chosen'])
                || empty($savedGame['player']['roster'])
            ) {

                $message =
                    'That Battle Code does not contain a completed game.';

                $messageType =
                    'error';

            } else {

                $_SESSION['battle_code'] =
                    $battleCode;

                unset(
                    $_SESSION['selecting_starter']
                );

                header(
                    'Location: game.php'
                );

                exit;
            }

        } else {

            $message =
                'Battle Code not found. Please check the code and try again.';

            $messageType =
                'error';
        }
    }
}


// ============================================================
// GET STARTERS
// ============================================================

$starters = getStarterMonsters(
    $allMonsters
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Soul Stone RPG
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- ========================================================
     NAVIGATION
     ======================================================== -->

<nav class="top-nav">

    <div class="logo">

        <a href="start.php">

            <strong>
                SOUL STONE RPG
            </strong>

        </a>

    </div>

</nav>


<!-- ========================================================
     MAIN
     ======================================================== -->

<main class="start-panel">


<?php if (!$selectingStarter): ?>


    <!-- ====================================================
         MAIN MENU
         ==================================================== -->

    <div class="start-logo">

        <div class="logo-symbol">
            ◆
        </div>

        <h1>
            SOUL STONE RPG
        </h1>

        <p>
            Capture. Battle. Become Legendary.
        </p>

    </div>


    <!-- ====================================================
         CONTINUE GAME
         ==================================================== -->

    <section class="start-section">

        <h2>
            CONTINUE YOUR JOURNEY
        </h2>

        <p class="section-description">

            Enter your Battle Code to continue
            a saved game.

        </p>


        <form
            method="post"
            action="start.php"
        >

            <label for="battle_code">
                BATTLE CODE
            </label>


            <input
                type="text"
                id="battle_code"
                name="battle_code"
                placeholder="SS-XXXX-XXXX"
                maxlength="11"
                autocomplete="off"
            >


            <button
                type="submit"
                name="continue_game"
                class="start-btn continue-btn"
            >

                CONTINUE GAME

            </button>

        </form>

    </section>


    <!-- ====================================================
         DIVIDER
         ==================================================== -->

    <div class="divider">

        <span>
            OR
        </span>

    </div>


    <!-- ====================================================
         NEW GAME
         ==================================================== -->

    <section class="start-section new-game-section">

        <h2>
            BEGIN A NEW JOURNEY
        </h2>

        <p class="section-description">

            Start a completely new
            Soul Stone adventure.

        </p>


        <form
            method="post"
            action="start.php"
        >

            <button
                type="submit"
                name="new_game"
                class="start-btn new-game-btn"
            >

                NEW GAME

            </button>

        </form>

    </section>


<?php else: ?>


    <!-- ====================================================
         STARTER SELECTION
         ==================================================== -->

    <div class="start-logo">

        <div class="logo-symbol">
            ◆
        </div>

        <h1>
            CHOOSE YOUR SOUL MONSTER
        </h1>

        <p>
            Your journey begins with one choice.
        </p>

    </div>


    <section class="starter-selection">

        <h2>
            CHOOSE YOUR STARTER
        </h2>

        <p class="section-description">

            Choose the Soul Monster that will
            accompany you on your adventure.

        </p>


        <div class="starter-grid">


            <?php foreach ($starters as $starter): ?>


                <?php

                $starterKey =
                    strtolower(
                        $starter['name']
                    );

                ?>


                <div class="starter-card">


                    <!-- ======================================
                         STONE
                         ====================================== -->

                    <div class="starter-stone">

                        <?php

                        $stoneSymbol = '◆';

                        if ($starter['type'] === 'fire') {

                            $stoneSymbol = '♦';

                        } elseif ($starter['type'] === 'water') {

                            $stoneSymbol = '●';

                        } elseif ($starter['type'] === 'earth') {

                            $stoneSymbol = '◆';
                        }

                        ?>

                        <span>
                            <?php echo $stoneSymbol; ?>
                        </span>

                    </div>


                    <!-- ======================================
                         MONSTER IMAGE
                         ====================================== -->

                    <div class="starter-image">

                        <?php if (!empty($starter['image'])): ?>

                            <img
                                src="images/monsters/<?php echo htmlspecialchars($starter['image']); ?>"
                                alt="<?php echo htmlspecialchars($starter['name']); ?>"
                            >

                        <?php endif; ?>

                    </div>


                    <!-- ======================================
                         INFORMATION
                         ====================================== -->

                    <h3>

                        <?php echo htmlspecialchars(
                            $starter['name']
                        ); ?>

                    </h3>


                    <div
                        class="type-badge <?php echo htmlspecialchars($starter['type']); ?>"
                    >

                        <?php echo strtoupper(
                            htmlspecialchars($starter['type'])
                        ); ?>

                    </div>


                    <div class="starter-stats">

                        <div>
                            <strong>
                                HP
                            </strong>

                            <?php echo (int) $starter['max_hp']; ?>
                        </div>


                        <div>
                            <strong>
                                ATK
                            </strong>

                            <?php echo (int) $starter['attack']; ?>
                        </div>

                    </div>


                    <!-- ======================================
                         CHOOSE
                         ====================================== -->

                    <form
                        method="post"
                        action="start.php"
                    >

                        <input
                            type="hidden"
                            name="starter"
                            value="<?php echo htmlspecialchars($starterKey); ?>"
                        >


                        <button
                            type="submit"
                            name="choose_starter"
                            class="start-btn new-game-btn"
                        >

                            CHOOSE

                        </button>

                    </form>


                </div>


            <?php endforeach; ?>


        </div>


        <!-- ================================================
             BACK BUTTON
             ================================================ -->

        <form
            method="post"
            action="start.php"
            class="starter-cancel-form"
        >

            <button
                type="submit"
                name="cancel_starter"
                class="start-btn continue-btn"
            >

                BACK

            </button>

        </form>

    </section>


<?php endif; ?>


<!-- ========================================================
     MESSAGE
     ======================================================== -->

<?php if (!empty($message)): ?>

    <div
        class="start-message <?php echo htmlspecialchars($messageType); ?>"
    >

        <?php echo htmlspecialchars($message); ?>

    </div>

<?php endif; ?>


</main>


</body>

</html>
```
