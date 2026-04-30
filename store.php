<?php
include "functions.php";
$game = loadGame();

$shopItems = [
    "potions" => ["name" => "Healing Potion", "price" => 50],
    "basic"   => ["name" => "Basic Soul Stone", "price" => 100],
    "greater" => ["name" => "Greater Soul Stone", "price" => 250]
];

if (isset($_POST['buy'])) {
    $key = $_POST['item_key'];
    $cost = $shopItems[$key]['price'];
    if (buyItem($game, $key, $cost)) {
        $game['message'] = "Bought 1 " . $shopItems[$key]['name'];
        saveGame($game);
    } else {
        $game['message'] = "Not enough gold!";
    }
}
?>
<!DOCTYPE html>
<html>
<head><link rel="stylesheet" href="style.css"></head>
<body>
    <h1>Fantasy Shop</h1>
    <p>Gold: <?php echo $game['player']['gold'] ?? 0; ?></p>
    <p><strong><?php echo $game['message'] ?? ''; ?></strong></p>

    <?php foreach($shopItems as $key => $item): ?>
        <div style="border:1px solid #ccc; margin:10px; padding:10px;">
            <strong><?php echo $item['name']; ?></strong> - <?php echo $item['price']; ?> Gold
            <form method="post">
                <input type="hidden" name="item_key" value="<?php echo $key; ?>">
                <button name="buy">Buy</button>
            </form>
        </div>
    <?php endforeach; ?>
    <a href="index.php">Return to Adventure</a>
</body>
</html>