<?php

session_start();

require_once 'functions.php';

$message = '';
$messageType = '';


// ============================================================
// CONTINUE EXISTING GAME
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['continue_game'])) {

        $code = strtoupper(trim($_POST['battle_code'] ?? ''));

        if ($code === '') {

            $message = 'Please enter your Battle Code.';
            $messageType = 'error';

        } else {

            $game = loadBattleSave($code);

            if ($game === null) {

                $message = 'Battle Code not found. Check the code and try again.';
                $messageType = 'error';

            } else {

                $_SESSION['battle_code'] = $code;

                header('Location: index.php');
                exit;
            }
        }
    }


    // ========================================================
    // START NEW GAME
    // ========================================================

    if (isset($_POST['new_game'])) {

        /*
         * Load your existing default game.
         *
         * This uses save.json as the template for a new game.
         */
        $game = loadGame();

        // Remove anything left over from an old battle
        $game['currentBattle'] = null;

        $game['message'] = 'Welcome to Soul Stone RPG!';

        // Create unique Battle Code
        $code = createBattleSave($game);

        // Remember this game's code
        $_SESSION['battle_code'] = $code;

        // Send player to game
        header('Location: index.php');
        exit;
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

    <title>Soul Stone RPG</title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>

<body class="start-page">


    <!-- ======================================================
         NAVIGATION
         ====================================================== -->

    <nav class="top-nav">

        <div class="logo">
            <a href="start.php">
                <strong>SOUL STONE RPG</strong>
            </a>
        </div>

    </nav>


    <!-- ======================================================
         START SCREEN
         ====================================================== -->

    <main class="start-container">

        <div class="start-panel">


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


            <!-- ==================================================
                 CONTINUE GAME
                 ================================================== -->

            <section class="start-section">

                <h2>
                    CONTINUE YOUR JOURNEY
                </h2>

                <p class="section-description">
                    Enter your Battle Code to continue a saved game.
                </p>


                <form method="post">

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


            <div class="divider">
                <span>OR</span>
            </div>


            <!-- ==================================================
                 NEW GAME
                 ================================================== -->

            <section class="start-section new-game-section">

                <h2>
                    BEGIN A NEW JOURNEY
                </h2>

                <p class="section-description">
                    Start a completely new Soul Stone adventure.
                </p>


                <form method="post">

                    <button
                        type="submit"
                        name="new_game"
                        class="start-btn new-game-btn"
                    >
                        NEW GAME
                    </button>

                </form>

            </section>


            <?php if ($message): ?>

                <div class="start-message <?php echo $messageType; ?>">

                    <?php echo htmlspecialchars($message); ?>

                </div>

            <?php endif; ?>


        </div>

    </main>

</body>

</html>