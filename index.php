<?php

session_start();

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/monsters.php';

$message = '';
$messageType = '';

$selectingStarter =
    isset($_SESSION['selecting_starter'])
    && $_SESSION['selecting_starter'] === true;

$hasPlayerName =
    isset($_SESSION['player_name'])
    && trim($_SESSION['player_name']) !== '';

// =========================================
// LOG INTO AN EXISTING GAME
// =========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['login_game'])
) {

    $filename = basename(
        trim($_POST['save_file'] ?? '')
    );

    $savePath = __DIR__ . '/saves/' . $filename;

    if (
        $filename === ''
        || !is_file($savePath)
    ) {

        $message = 'That saved game could not be found.';
        $messageType = 'error';

    } else {

        $game = loadPlayerGame($filename);

        if (
            empty($game['game_started'])
            || empty($game['player']['roster'])
        ) {

            $message = 'That saved game is incomplete.';
            $messageType = 'error';

        } else {

            session_regenerate_id(true);

            $_SESSION['save_file'] = $filename;
            $_SESSION['game'] = $game;

            unset(
                $_SESSION['selecting_starter'],
                $_SESSION['player_name']
            );

            header('Location: game.php');
            exit;
        }
    }
}


// =========================================
// BEGIN NEW GAME
// =========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['new_game'])
) {

    unset(
        $_SESSION['save_file'],
        $_SESSION['game'],
        $_SESSION['player_name']
    );

    $_SESSION['selecting_starter'] = true;

    $selectingStarter = true;
    $hasPlayerName = false;
}


// =========================================
// CREATE PLAYER NAME
// =========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['create_player'])
) {

    $playerName = trim(
        $_POST['player_name'] ?? ''
    );

    if ($playerName === '') {

        $message = 'Please enter a player name.';
        $messageType = 'error';

        $_SESSION['selecting_starter'] = true;
        $selectingStarter = true;
        $hasPlayerName = false;

    } elseif (mb_strlen($playerName) > 20) {

        $message = 'Your player name must be 20 characters or less.';
        $messageType = 'error';

        $_SESSION['selecting_starter'] = true;
        $selectingStarter = true;
        $hasPlayerName = false;

    } else {

        $_SESSION['player_name'] = $playerName;
        $_SESSION['selecting_starter'] = true;

        $selectingStarter = true;
        $hasPlayerName = true;
    }
}


// =========================================
// CHOOSE STARTER
// =========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['choose_starter'])
) {

    $starterName = strtolower(
        trim($_POST['starter'] ?? '')
    );

    $playerName = trim(
        $_SESSION['player_name'] ?? ''
    );

    $starters = getStarterMonsters($allMonsters);

    if ($playerName === '') {

        $message = 'Please enter your player name first.';
        $messageType = 'error';

        $_SESSION['selecting_starter'] = true;
        $selectingStarter = true;
        $hasPlayerName = false;

    } elseif (
        $starterName === ''
        || !isset($starters[$starterName])
    ) {

        $message = 'Please choose a valid starter monster.';
        $messageType = 'error';

        $_SESSION['selecting_starter'] = true;
        $selectingStarter = true;
        $hasPlayerName = true;

    } else {

        // Create a completely fresh game.
        $game = createNewGame();

        // Store player name inside the save file.
        $game['player']['name'] = $playerName;

        // Add the selected starter.
        startGameWithStarter(
            $game,
            $starters[$starterName]
        );

        // Starting inventory.
        $game['inventory']['basic_potion'] = 3;
        $game['inventory']['greater_potion'] = 1;
        $game['inventory']['ancient_potion'] = 0;

        $game['inventory']['basic'] = 5;
        $game['inventory']['greater'] = 1;
        $game['inventory']['ancient'] = 0;

        // Give this game its own save file.
        $saveFilename = getUniqueSaveFilename($playerName);

        if (!savePlayerGame($game, $saveFilename)) {

            $message = 'The game could not be saved. Please check that the saves folder exists and is writable.';
            $messageType = 'error';

            $_SESSION['selecting_starter'] = true;
            $selectingStarter = true;
            $hasPlayerName = true;

        } else {

            session_regenerate_id(true);

            $_SESSION['save_file'] = $saveFilename;
            $_SESSION['game'] = $game;

            unset(
                $_SESSION['selecting_starter'],
                $_SESSION['player_name']
            );

            header('Location: game.php');
            exit;
        }
    }
}


// =========================================
// CANCEL NEW GAME / BACK
// =========================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['cancel_starter'])
) {

    unset(
        $_SESSION['selecting_starter'],
        $_SESSION['player_name']
    );

    $selectingStarter = false;
    $hasPlayerName = false;
}


// =========================================
// GET STARTERS AND SAVED GAMES
// =========================================

$starters = getStarterMonsters($allMonsters);
$savedGames = getSavedGames();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Soul Stone RPG</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<main class="start-panel backgroundBody body_box">

<?php if (!$selectingStarter): ?>

    <!-- ================================
         MAIN MENU
    ================================= -->

    <div class="black-box">

        <div class="start-logo">
            <div class="logo-symbol">◆</div>

            <h1 class="bigFont">
                SOUL STONE RPG
            </h1>

            <p>
                Capture. Battle. Become Legendary.
            </p>
        </div>

        <br>

        <!-- ================================
             SAVED GAMES
        ================================= -->

        <section class="start-section">

            <h2 class="subFont">
                YOUR GAMES
            </h2>

            <p class="section-description">
                Select a saved game to continue your journey.
            </p>

            <?php if (empty($savedGames)): ?>

                <p class="section-description">
                    You do not have any saved games yet.
                </p>

            <?php else: ?>

                <div class="saved-games">

                    <?php foreach ($savedGames as $saved): ?>

                        <?php
                        $savedGame = $saved['game'];
                        $player = $savedGame['player'] ?? [];
                        $roster = $player['roster'] ?? [];
                        $activeIndex = $player['active'] ?? 0;
                        $activeMonster = $roster[$activeIndex] ?? ($roster[0] ?? null);
                        $activeXP = $activeMonster
                            ? getXPStats($activeMonster['xp'] ?? 0)
                            : ['level' => 1];
                        ?>

                        <div class="saved-game-card">

                            <h3>
                                <?php echo htmlspecialchars(
                                    $player['name'] ?? 'Player'
                                ); ?>
                            </h3>

                            <?php if ($activeMonster): ?>

                                <p>
                                    Active Monster:
                                    <strong>
                                        <?php echo htmlspecialchars(
                                            $activeMonster['name']
                                        ); ?>
                                    </strong>
                                </p>

                                <p>
                                    Level:
                                    <strong>
                                        <?php echo (int)$activeXP['level']; ?>
                                    </strong>
                                </p>

                            <?php endif; ?>

                            <p>
                                Gold:
                                <strong>
                                    <?php echo number_format(
                                        (int)($player['gold'] ?? 0)
                                    ); ?>
                                </strong>
                            </p>

                            <form method="post" action="index.php">

                                <input
                                    type="hidden"
                                    name="save_file"
                                    value="<?php echo htmlspecialchars(
                                        $saved['filename'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>"
                                >

                                <button
                                    type="submit"
                                    name="login_game"
                                    class="start-btn continue-btn"
                                >
                                    LOG IN
                                </button>

                            </form>

                        </div>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

        <br>

        <div class="divider">
            <span>OR</span>
        </div>

        <!-- ================================
             NEW GAME
        ================================= -->

        <section class="start-section new-game-section">

            <h2 class="subFont">
                BEGIN A NEW JOURNEY
            </h2>

            <p class="section-description">
                Start a completely new Soul Stone adventure.
            </p>

            <form method="post" action="index.php">

                <button
                    type="submit"
                    name="new_game"
                    class="start-btn new-game-btn"
                >
                    NEW GAME
                </button>

            </form>

        </section>

    </div>


<?php elseif (!$hasPlayerName): ?>

    <!-- ================================
         PLAYER NAME
    ================================= -->

    <div class="black-box">

        <div class="start-logo">

            <div class="logo-symbol">◆</div>

            <h1 class="bigFont">
                NEW JOURNEY
            </h1>

            <p>
                Every legend needs a name.
            </p>

        </div>

        <section class="start-section">

            <h2 class="subFont">
                ENTER YOUR NAME
            </h2>

            <p class="section-description">
                This name will be used for your saved game.
            </p>

            <form method="post" action="index.php">

                <label for="player_name">
                    PLAYER NAME
                </label>

                <input
                    type="text"
                    id="player_name"
                    name="player_name"
                    maxlength="20"
                    required
                    autocomplete="off"
                    placeholder="Enter your name"
                >

                <button
                    type="submit"
                    name="create_player"
                    class="start-btn new-game-btn"
                >
                    CONTINUE
                </button>

            </form>

            <br>

            <form method="post" action="index.php">
                <button
                    type="submit"
                    name="cancel_starter"
                    class="start-btn continue-btn"
                >
                    BACK
                </button>
            </form>

        </section>

    </div>


<?php else: ?>

    <!-- ================================
         STARTER SELECTION
    ================================= -->

    <div class="black-box">

        <div class="start-logo">

            <div class="logo-symbol">◆</div>

            <h1 class="bigFont">
                CHOOSE YOUR SOUL MONSTER
            </h1>

            <p>
                Your journey begins with one choice.
            </p>

        </div>

        <section class="starter-selection">

            <div class="starter-grid">

                <?php foreach ($starters as $starter): ?>

                    <?php
                    $starterKey = strtolower(
                        $starter['name']
                    );

                    $stoneSymbol = '◆';

                    if ($starter['type'] === 'fire') {
                        $stoneSymbol = '♦';
                    } elseif ($starter['type'] === 'water') {
                        $stoneSymbol = '●';
                    } elseif ($starter['type'] === 'earth') {
                        $stoneSymbol = '◆';
                    }
                    ?>

                    <div class="starter-card">

                        <!-- Stone -->
                        <div class="starter-stone">
                            <span>
                                <?php echo $stoneSymbol; ?>
                            </span>
                        </div>

                        <!-- Monster Image -->
                        <div class="starter-image">

                            <?php if (!empty($starter['image'])): ?>

                                <img
                                    src="images/monsters/<?php echo htmlspecialchars($starter['image']); ?>"
                                    alt="<?php echo htmlspecialchars($starter['name']); ?>"
                                >

                            <?php endif; ?>

                        </div>

                        <!-- Information -->
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
                                <strong>HP</strong>
                                <?php echo (int)$starter['max_hp']; ?>
                            </div>

                            <div>
                                <strong>ATK</strong>
                                <?php echo (int)$starter['attack']; ?>
                            </div>

                        </div>

                        <!-- Choose Starter -->
                        <form
                            method="post"
                            action="index.php"
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

            <!-- Back Button -->
            <form
                method="post"
                action="index.php"
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

    </div>

<?php endif; ?>


<!-- Message -->
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