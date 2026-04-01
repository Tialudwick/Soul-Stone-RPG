<?php
include "functions.php";

$gameExists = file_exists("save.json") && filesize("save.json") > 0;
$message = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (isset($_POST['new_game'])) {
        // Start a new game
        if(file_exists("save.json")) unlink("save.json"); // remove old save
        $game = loadGame(); // loads default starting game
        saveGame($game);
        header("Location: index.php");
        exit;
    } elseif (isset($_POST['continue_game']) && $gameExists) {
        // Continue existing game
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="text-align:center; margin-top:50px;">
        <h1>Welcome to Soul Stone RPG</h1>
        <p>Capture monsters, battle wild creatures, and grow your team!</p>

        <form method="post">
            <button name="new_game">Start New Game</button>
            <?php if($gameExists): ?>
                <button name="continue_game">Continue Game</button>
            <?php else: ?>
                <p>No saved game found.</p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>