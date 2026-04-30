<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$message = "";

// Handle Purchases (Matches logic from index.php inventory keys)
if (isset($_POST['buy'])) {
    $item = $_POST['buy'];
    $prices = [
        'basic_potion' => 50, 'greater_potion' => 150, 'ancient_potion' => 500,
        'basic' => 100, 'greater' => 300, 'ancient' => 1000
    ];

    $cost = $prices[$item] ?? 999999;

    if ($game['player']['gold'] >= $cost) {
        $game['player']['gold'] -= $cost;
        $game['inventory'][$item] = ($game['inventory'][$item] ?? 0) + 1;
        $message = "You obtained the " . str_replace('_', ' ', $item) . "!";
    } else {
        $message = "Not enough gold, traveler!";
    }
    saveGame($game);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG - The Emporium</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a1d; margin: 0; color: #fff; }
        
        /* Nav matches index.php */
        .top-nav { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3498db; color: #333; }
        .nav-links a { text-decoration: none; color: #2c3e50; font-weight: bold; margin-left: 20px; }

        .shop-header { text-align: center; padding: 40px 0; background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('images/shop_bg.jpg'); background-size: cover; }
        .gold-pouch { display: inline-block; background: #f1c40f; color: #000; padding: 10px 25px; border-radius: 50px; font-weight: bold; font-size: 1.4em; box-shadow: 0 4px 15px rgba(241, 196, 15, 0.3); border: 3px solid #d4ac0d; }

        .display-case { max-width: 1100px; margin: -30px auto 50px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; padding: 20px; }

        /* ITEM CARD STYLES */
        .item-display { background: #2c3e50; border: 4px solid #8e44ad; border-radius: 12px; position: relative; padding: 20px; text-align: center; transition: transform 0.2s; box-shadow: 0 10px 20px rgba(0,0,0,0.5); }
        .item-display:hover { transform: translateY(-10px); }
        
        .item-display.potions { border-color: #27ae60; }
        .item-display.stones { border-color: #8e44ad; }

        .item-title { background: rgba(0,0,0,0.3); padding: 8px; border-radius: 5px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; color: #ecf0f1; }
        
        .item-icon { width: 80px; height: 80px; background: #fff; border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; border: 4px solid #bdc3c7; }
        .item-icon img { width: 50px; height: 50px; object-fit: contain; }

        .item-desc { font-size: 0.9em; color: #bdc3c7; height: 40px; margin-bottom: 15px; }

        .price-tag { font-size: 1.5em; font-weight: bold; color: #f1c40f; margin-bottom: 15px; display: block; }
        
        .btn-buy { width: 100%; background: #e74c3c; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; text-transform: uppercase; border-bottom: 4px solid #c0392b; }
        .btn-buy:hover { background: #ff5e4d; }
        .btn-buy:active { border-bottom: 0; transform: translateY(2px); }

        .toast { position: fixed; top: 80px; right: 20px; background: #2ecc71; padding: 15px 30px; border-radius: 5px; font-weight: bold; box-shadow: 0 5px 15px rgba(0,0,0,0.3); z-index: 100; animation: slideIn 0.5s forwards; }
        @keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
    </style>
</head>
<body>

<div class="top-nav">
    <div class="logo"><strong>SOUL STONE RPG</strong></div>
    <div class="nav-links">
        <a href="bestiary.php">BESTIARY</a>
        <a href="shop.php">SHOP</a>
        <a href="index.php">HOME</a>
    </div>
</div>

<div class="shop-header">
    <h1 style="margin-top:0; font-size: 3em;">THE MAGIC EMPORIUM</h1>
    <div class="gold-pouch">💰 <?php echo number_format($game['player']['gold']); ?> GOLD</div>
</div>

<?php if($message): ?>
    <div class="toast"><?php echo $message; ?></div>
<?php endif; ?>

<form method="post">
    <div class="display-case">
        <div class="item-display potions">
            <div class="item-title">Basic Potion</div>
            <div class="item-icon"><img src="images/items/pot_basic.png" alt="Pot"></div>
            <div class="item-desc">A standard brew. Restores 30 HP.</div>
            <span class="price-tag">50g</span>
            <button name="buy" value="basic_potion" class="btn-buy">PURCHASE</button>
        </div>

        <div class="item-display potions">
            <div class="item-title">Greater Potion</div>
            <div class="item-icon"><img src="images/items/pot_greater.png" alt="Pot"></div>
            <div class="item-desc">A concentrated elixir. Restores 80 HP.</div>
            <span class="price-tag">150g</span>
            <button name="buy" value="greater_potion" class="btn-buy">PURCHASE</button>
        </div>

        <div class="item-display potions">
            <div class="item-title">Ancient Potion</div>
            <div class="item-icon"><img src="images/items/pot_ancient.png" alt="Pot"></div>
            <div class="item-desc">Brewed by elders. Fully restores HP.</div>
            <span class="price-tag">500g</span>
            <button name="buy" value="ancient_potion" class="btn-buy">PURCHASE</button>
        </div>

        <div class="item-display stones">
            <div class="item-title">Basic Stone</div>
            <div class="item-icon"><img src="images/items/stone_basic.png" alt="Stone"></div>
            <div class="item-desc">Used to capture weak wild monsters.</div>
            <span class="price-tag">100g</span>
            <button name="buy" value="basic" class="btn-buy">PURCHASE</button>
        </div>

        <div class="item-display stones">
            <div class="item-title">Greater Stone</div>
            <div class="item-icon"><img src="images/items/stone_greater.png" alt="Stone"></div>
            <div class="item-desc">Higher success rate for mid-tier foes.</div>
            <span class="price-tag">300g</span>
            <button name="buy" value="greater" class="btn-buy">PURCHASE</button>
        </div>

        <div class="item-display stones">
            <div class="item-title">Ancient Stone</div>
            <div class="item-icon"><img src="images/items/stone_ancient.png" alt="Stone"></div>
            <div class="item-desc">The ultimate vessel. Catches almost anything.</div>
            <span class="price-tag">1,000g</span>
            <button name="buy" value="ancient" class="btn-buy">PURCHASE</button>
        </div>
    </div>
</form>

</body>
</html>