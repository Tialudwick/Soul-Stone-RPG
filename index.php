<?php
session_start();
include "functions.php";
include "monsters.php";

$game = loadGame();
$action = $_POST['action'] ?? null;

// --- DATA REPAIR & SYNC ---
if (!empty($game['player']['roster'])) {
    foreach ($game['player']['roster'] as &$m) {
        if (!isset($m['type'])) {
            foreach ($allMonsters as $ref) { 
                if ($ref['name'] === $m['name']) { $m['type'] = $ref['type']; break; } 
            }
        }
        if (!isset($m['moves']) || empty($m['moves'])) { 
            $m['moves'] = $moves[$m['type'] ?? 'earth']; 
        }
    }
}

// --- HANDLE ACTIONS ---
if ($game['currentBattle']) {
    $pm = &$game['player']['roster'][$game['player']['active']];
    $em = &$game['currentBattle'];

    if ($pm['hp'] <= 0) {
        $game['message'] = "Your monster has fainted! Switch to another or use a potion.";
    } elseif (str_starts_with($action, "attack_")) {
        $idx = (int)str_replace("attack_", "", $action);
        $move = $pm['moves'][$idx];
        
        $dmg = floor(rand($pm['attack']-2, $pm['attack']+2) * $move['power'] * getTypeMultiplier($move['type'], $em['type']));
        $em['hp'] -= $dmg;
        
        if ($em['hp'] <= 0) {
            $em['hp'] = 0; 
            $lvlMsg = gainXP($pm, 80); 
            $game['player']['gold'] += rand(50, 100);
            $game['message'] = "Victory! " . ($lvlMsg ?: "");
            $game['currentBattle'] = null;
        } else {
            $eMove = $em['moves'][rand(0,3)];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
            if ($pm['hp'] < 0) { $pm['hp'] = 0; } 
        }
    }
}

// --- ITEM & CATCH LOGIC ---
if (str_starts_with($action, "use_pot_")) {
    $type = str_replace("use_pot_", "", $action);
    $healAmt = ["basic" => 30, "greater" => 80, "ancient" => 200][$type];
    if (($game['inventory'][$type."_potion"] ?? 0) > 0) {
        $pm = &$game['player']['roster'][$game['player']['active']];
        $pm['hp'] = min($pm['max_hp'], $pm['hp'] + $healAmt);
        $game['inventory'][$type."_potion"]--;
        $game['message'] = "Used " . ucfirst($type) . " Potion!";
    }
}

// NEW CATCH LOGIC
if (str_starts_with($action, "catch_")) {
    $stoneType = str_replace("catch_", "", $action); // 'basic', 'greater', or 'ancient'
    
    if (($game['inventory'][$stoneType] ?? 0) > 0) {
        $game['inventory'][$stoneType]--; // Consume the stone
        
        $em = &$game['currentBattle'];
        // Success Rate Calculation
        // Lower Enemy HP = Higher Catch Rate
        $hpPercent = $em['hp'] / $em['max_hp'];
        $stonePower = ["basic" => 0.3, "greater" => 0.6, "ancient" => 1.0][$stoneType];
        
        $catchChance = (1 - $hpPercent) + $stonePower;
        $roll = rand(0, 100) / 100;

        if ($roll < $catchChance) {
            // SUCCESS: Add to roster if there is room
            if (count($game['player']['roster']) < 8) {
                $newMonster = $em;
                $newMonster['hp'] = $newMonster['max_hp']; // Heal upon capture
                $game['player']['roster'][] = $newMonster;
                $game['message'] = "Gotcha! {$em['name']} was caught!";
                $game['currentBattle'] = null; // End battle
            } else {
                $game['message'] = "Roster full! Could not keep {$em['name']}.";
                $game['currentBattle'] = null;
            }
        } else {
            // FAILURE: Enemy attacks back
            $game['message'] = "Oh no! The {$em['name']} broke free!";
            $eMove = $em['moves'][rand(0,3)];
            $pm = &$game['player']['roster'][$game['player']['active']];
            $pm['hp'] -= floor(rand($em['attack']-2, $em['attack']+2) * $eMove['power']);
            if ($pm['hp'] < 0) { $pm['hp'] = 0; }
        }
    } else {
        $game['message'] = "You don't have any $stoneType stones!";
    }
}

if ($action === "start_battle") { $game['currentBattle'] = spawnMonster($allMonsters); }
if (isset($_POST['switch_to'])) { $game['player']['active'] = (int)$_POST['switch_to']; }
if ($action === "run") { $game['currentBattle'] = null; }

saveGame($game);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Soul Stone RPG</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #1a1a1d; margin: 0; color: #333; }
        .top-nav { background: #fff; padding: 15px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #3498db; }
        .nav-links a { text-decoration: none; color: #2c3e50; font-weight: bold; margin-left: 20px; }
        
/* ================================
MAIN GAME LAYOUT
================================ */

.main-container {
    display: grid;
    grid-template-columns: minmax(650px, 2fr) minmax(300px, 1fr);
    gap: 20px;

    max-width: 1400px;
    width: 100%;
    margin: 20px auto;
    padding: 0 15px;

    box-sizing: border-box;
}


/* ================================
BATTLE COLUMN
================================ */

.battle-column {
    background: #2c3e50;
    padding: 20px;
    border-radius: 12px;
    border: 4px solid #34495e;

    min-width: 0;
}


/* ================================
UI COLUMN
================================ */

.ui-column {
    background: #bdc3c7;
    padding: 15px;
    border-radius: 8px;
    border: 4px solid #3498db;

    min-width: 280px;
}

/* ================================
BATTLEFIELD
================================ */

.stage {
    position: relative;

    display: flex;
    justify-content: space-between;
    align-items: center;

    min-height: 520px;

    padding: 30px 25px 60px;

    background:
        radial-gradient(
            ellipse at center,
            #40566b 0%,
            #30465a 55%,
            #263b4d 100%
        );

    border:5px solid #34495e;
    border-radius:18px;

    overflow:hidden;
}

/* Battlefield floor */
.stage::after {
    content: "";
    position: absolute;

    left: 5%;
    right: 5%;
    bottom: 35px;

    height: 90px;

    background: rgba(20, 30, 35, 0.35);

    border-radius: 50%;

    box-shadow:
        0 10px 15px rgba(0,0,0,0.35),
        inset 0 5px 15px rgba(255,255,255,0.05);

    z-index: 0;
}


/* ================================
MONSTER CARDS
================================ */

.monster-card {

    width: clamp(190px, 22vw, 250px);

    min-height: 360px;

    background:#f4e4bc;

    border:8px solid #3d2b1f;
    border-radius:12px;

    padding:10px;

    box-shadow:
        0 12px 25px rgba(0,0,0,.55);

    z-index:2;

    flex-shrink:1;
}


/* Enemy card */
.stage .monster-card:nth-child(2) {
    border-color: #b83b32;
}


/* =====================================================
   CARD HEADER
   ===================================================== */

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    font-weight: bold;

    border-bottom: 2px solid #3d2b1f;

    padding-bottom: 5px;
    margin-bottom: 10px;

    position: relative;
    z-index: 5;
}


/* ================================
IMAGE WELL
================================ */

.image-well {

    width:100%;
    height:170px;

    background:
    radial-gradient(
        circle,
        #fff8df,
        #f4e4bc
    );

    border:3px solid #3d2b1f;
    border-radius:8px;

    display:flex;
    justify-content:center;
    align-items:center;

    overflow:hidden;
}


.image-well img {

    width:90%;
    height:90%;

    object-fit:contain;

    display:block;

    filter:
    drop-shadow(
        3px 5px 3px rgba(0,0,0,.35)
    );
}



/* =====================================================
   TYPE BADGE
   ===================================================== */

.type-badge {
    position: absolute;

    top: -15px;
    right: -15px;

    padding: 5px 12px;

    border-radius: 20px;

    color: white;

    font-size: 0.7em;
    font-weight: bold;

    border: 3px solid white;

    box-shadow: 0 3px 6px rgba(0,0,0,0.4);

    z-index: 10;
}


/* =====================================================
   TYPES
   ===================================================== */

.fire {
    background: #e67e22;
}

.water {
    background: #3498db;
}

.earth {
    background: #27ae60;
}


/* =====================================================
   HP BAR
   ===================================================== */

.hp-bar {
    width: 100%;
    height: 13px;

    background: #7f8c8d;

    border-radius: 7px;

    overflow: hidden;

    border: 2px solid #3d2b1f;

    margin-top: 5px;
}

.hp-fill {
    height: 100%;

    background: linear-gradient(
        to bottom,
        #55e889,
        #20a653
    );

    transition: width 0.4s;
}

.hp-enemy {
    background: linear-gradient(
        to bottom,
        #ff6b5f,
        #c0392b
    );
}


/* =====================================================
   BATTLE VS
   ===================================================== */

.stage::before {
    content: "VS";

    position: absolute;

    left: 50%;
    top: 50%;

    transform: translate(-50%, -50%);

    font-size: 42px;
    font-weight: 900;

    color: #ffd21c;

    text-shadow:
        3px 3px 0 #3d2b1f,
        -2px -2px 0 #3d2b1f,
        0 5px 8px rgba(0,0,0,0.5);

    z-index: 4;
}


/* ======== UI Elements ======== */

.section-title {
    font-weight: bold;
    text-align: center;
    background: #34495e;
    color: white;
    padding: 5px;
    margin: 10px 0;
    border-radius: 4px;
    font-size: 0.9em;
}

.btn-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
    margin-bottom: 15px;
}

.btn {
    border: none;
    padding: 10px 5px;
    cursor: pointer;
    color: white;
    border-radius: 4px;
    font-weight: bold;
    font-size: 0.75em;
    transition: 0.2s;
}

.btn-pot {
    background: #9b59b6;
}

.btn-stone {
    background: #2ecc71;
}

.btn:hover {
    opacity: 0.8;
    transform: scale(1.02);
}


.roster-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.roster-card {
    background: white;
    border: 2px solid #7f8c8d;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.active-slot {
    border-color: #3498db;
    box-shadow: 0 0 10px rgba(52, 152, 219, 0.5);
    background: #ebf5fb;
}

.roster-card img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.roster-info {
    flex: 1;
    font-size: 0.8em;
}

.fainted-img {
    filter: grayscale(100%) opacity(0.5);
}
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

<div class="main-container">
    <div class="battle-column">
        <?php if($game['currentBattle']): 
            $pm = $game['player']['roster'][$game['player']['active']];
            $em = $game['currentBattle'];
            $pmXP = getXPStats($pm['xp'] ?? 0); 
            $pmHP = ($pm['hp'] / $pm['max_hp']) * 100;
            $emHP = ($em['hp'] / $em['max_hp']) * 100;
        ?>
            <div class="stage">
                <div class="monster-card">
                    <div class="type-badge <?php echo $pm['type']; ?>"><?php echo strtoupper($pm['type']); ?></div>
                    <div class="card-header"><span><?php echo $pm['name']; ?></span> <span>Lv.<?php echo $pmXP['level']; ?></span></div>
                    <div class="image-well"><img src="images/monsters/<?php echo $pm['image']; ?>" class="<?php echo $pm['hp'] <= 0 ? 'fainted-img' : ''; ?>"></div>
                    <div class="hp-bar"><div class="hp-fill" style="width:<?php echo $pmHP; ?>%"></div></div>
                    <div style="margin-top: 5px;">
                    <div class="hp-bar" style="height: 6px; background: #34495e;">
                        <div class="hp-fill" style="width:<?php echo $pmXP['percent']; ?>%; background: #3498db;"></div>
                    </div>
                    <div style="font-size: 0.65em; display: flex; justify-content: space-between; color: #3d2b1f; font-weight: bold;">
                        <span>XP: <?php echo $pm['xp']; ?> / <?php echo $pmXP['next_lvl']; ?></span>
                        <span><?php echo floor($pmXP['percent']); ?>%</span>
                    </div>
                    </div>
                    <div style="font-size: 0.7em; text-align: center; margin-top: 5px;">HP: <?php echo $pm['hp']."/".$pm['max_hp']; ?> | ATK: <?php echo $pm['attack']; ?></div>
                </div>

                <div style="font-size: 3em; font-weight: bold; color: #f1c40f; text-shadow: 2px 2px #000;">VS</div>

                <div class="monster-card" style="border-color: #c0392b;">
                    <div class="type-badge <?php echo $em['type']; ?>"><?php echo strtoupper($em['type']); ?></div>
                    <div class="card-header"><span>Wild <?php echo $em['name']; ?></span></div>
                    <div class="image-well"><img src="images/monsters/<?php echo $em['image']; ?>"></div>
                    <div class="hp-bar"><div class="hp-fill hp-enemy" style="width:<?php echo $emHP; ?>%"></div></div>
                    <div style="font-size: 0.7em; text-align: center; margin-top: 5px;">WILD BEAST</div>
                </div>
            </div>

            <form method="post">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <?php foreach($pm['moves'] as $i => $move): ?>
                        <button name="action" value="attack_<?php echo $i; ?>" class="btn" style="background:#f39c12; height:50px; border-bottom:4px solid #d35400;" <?php echo $pm['hp'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo strtoupper($move['name']); ?><br><small>PWR: <?php echo $move['power']; ?></small>
                        </button>
                    <?php endforeach; ?>
                </div>
                <button name="action" value="run" class="btn" style="background:#e74c3c; width:100%; margin-top:10px; padding:15px;">RUN AWAY</button>
            </form>
            <div style="color:white; text-align:center; margin-top:15px; font-weight:bold;"><?php echo $game['message']; ?></div>

        <?php else: ?>
            <div style="height:400px; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                <h3 style="color:white;"><?php echo $game['message'] ?: "Explore the world of Soul Stones!"; ?></h3>
                <form method="post"><button name="action" value="start_battle" class="btn" style="background:#27ae60; padding:20px 50px; font-size:1.5em;">EXPLORE GRASS</button></form>
            </div>
        <?php endif; ?>
    </div>

    <div class="ui-column">
        <form method="post">
            <div class="section-title">POTIONS</div>
            <div class="btn-grid">
                <button name="action" value="use_pot_basic" class="btn btn-pot">Basic (<?php echo $game['inventory']['basic_potion'] ?? 0; ?>)</button>
                <button name="action" value="use_pot_greater" class="btn btn-pot">Great (<?php echo $game['inventory']['greater_potion'] ?? 0; ?>)</button>
                <button name="action" value="use_pot_ancient" class="btn btn-pot">Ancient (<?php echo $game['inventory']['ancient_potion'] ?? 0; ?>)</button>
            </div>

            <div class="section-title">SOUL STONES</div>
            <div class="btn-grid">
                <button name="action" value="catch_basic" class="btn btn-stone">
                    Basic (<?php echo $game['inventory']['basic'] ?? 0; ?>)
                </button>
                <button name="action" value="catch_greater" class="btn btn-stone">
                    Great (<?php echo $game['inventory']['greater'] ?? 0; ?>)
                </button>
                <button name="action" value="catch_ancient" class="btn btn-stone">
                     Ancient (<?php echo $game['inventory']['ancient'] ?? 0; ?>)
                </button>
            </div>

            <div class="section-title">MONSTER ROSTER</div>
            <div class="roster-grid">
                <?php for($i=0; $i<8; $i++): ?>
                    <?php if(isset($game['player']['roster'][$i])): 
                        $m = $game['player']['roster'][$i];
                        $hpP = ($m['hp'] / $m['max_hp']) * 100;
                        $stats = getXPStats($m['xp'] ?? 0);
                    ?>
                        <button name="switch_to" value="<?php echo $i; ?>" class="roster-card <?php echo $i == $game['player']['active'] ? 'active-slot' : ''; ?>">
                            <img src="images/monsters/<?php echo $m['image']; ?>" class="<?php echo $m['hp'] <= 0 ? 'fainted-img' : ''; ?>">
                            <div class="roster-info">
                                <div style="font-weight:bold; font-size: 0.9em;"><?php echo $m['name']; ?></div>
                                <div style="font-size: 0.8em; color: #7f8c8d;">Lv.<?php echo $stats['level']; ?> <span class="type-text <?php echo $m['type']; ?>" style="font-size: 0.8em; padding: 1px 4px; border-radius: 3px; color: white;"><?php echo $m['type']; ?></span></div>
                                <div class="hp-bar" style="height:5px; margin-top:3px;"><div class="hp-fill" style="width:<?php echo $hpP; ?>%"></div></div>
                            </div>
                        </button>
                    <?php else: ?>
                        <div class="roster-card" style="justify-content:center; color:#bdc3c7; border-style:dashed;">Empty</div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>

            <div style="background:#2c3e50; color:#f1c40f; padding:15px; border-radius:8px; margin-top:20px; text-align:center; font-weight:bold; border: 2px solid #f1c40f;">
                GOLD AMOUNT: <?php echo number_format($game['player']['gold']); ?>
            </div>
        </form>
    </div>
</div>

</body>
</html>