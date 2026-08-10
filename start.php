```php
<?php
// ============================================================
// SOUL STONE RPG
// START.PHP
// Main Menu / Game Launcher
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
// HANDLE NEW GAME
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_game'])) {

    // Remove any previous session battle
    unset($_SESSION['battle_code']);

    // Send player to starter selection
    header('Location: newgame.php');
    exit;
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
        // Try to load the saved game
        // ----------------------------------------------------

        $savedGame =
            loadBattleSave($battleCode);


        if ($savedGame !== null) {

            // Save the battle code in the session
            $_SESSION['battle_code'] =
                $battleCode;

            // Go to the actual game
            header('Location: game.php');
            exit;

        } else {

            $message =
                'Battle Code not found. Please check the code and try again.';

            $messageType =
                'error';
        }
    }
}

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
     START PANEL
     ======================================================== -->

<main class="start-panel">


    <!-- ====================================================
         LOGO
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
                required
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


    <!-- ====================================================
         MESSAGE
         ==================================================== -->

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
