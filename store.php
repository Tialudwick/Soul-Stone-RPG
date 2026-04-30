<?php
include "functions.php";
$game = loadGame();
$stock = ["potions"=>50, "basic"=>100, "greater"=>250, "ancient"=>1000];

if (isset($_POST['item'])) {
    if (buyItem($game, $_POST['item'], $stock[$_POST['item']])) {
        $msg = "Bought " . $_POST['item'];
    } else {
        $msg = "Too poor!";
    }
    saveGame($game);
}
?>
<!DOCTYPE html>
<html>
<body>
    <h1>Shop (Gold: <?php echo $game['player']['gold']; ?>)</h1>
    <p><?php echo $msg ?? ''; ?></p>
    <?php foreach($stock as $item=>$price): ?>
        <form method="post">
            <input type="hidden" name="item" value="<?php echo $item; ?>">
            <button><?php echo ucfirst($item); ?> - <?php echo $price; ?>G</button>
        </form>
    <?php endforeach; ?>
    <a href="index.php">Back</a>
</body>
</html>