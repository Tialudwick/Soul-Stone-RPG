<?php
// --- ELEMENTAL TYPE CHART ---
// Multiplier: [Attacker][Defender]
$typeChart = [
    "fire"  => ["fire" => 1.0, "water" => 0.5, "earth" => 2.0],
    "water" => ["fire" => 2.0, "water" => 1.0, "earth" => 0.5],
    "earth" => ["fire" => 0.5, "water" => 2.0, "earth" => 1.0]
];

// --- MOVE DEFINITIONS ---
$moves = [
    "fire" => [
        ["name" => "Ember", "power" => 1.0, "type" => "fire"],
        ["name" => "Flame Dash", "power" => 1.2, "type" => "fire"],
        ["name" => "Fire Blast", "power" => 1.5, "type" => "fire"],
        ["name" => "Overheat", "power" => 2.0, "type" => "fire"]
    ],
    "water" => [
        ["name" => "Splash", "power" => 1.0, "type" => "water"],
        ["name" => "Water Gun", "power" => 1.2, "type" => "water"],
        ["name" => "Aqua Tail", "power" => 1.5, "type" => "water"],
        ["name" => "Hydro Cannon", "power" => 2.0, "type" => "water"]
    ],
    "earth" => [
        ["name" => "Pebble Toss", "power" => 1.0, "type" => "earth"],
        ["name" => "Mud Slap", "power" => 1.2, "type" => "earth"],
        ["name" => "Rock Slide", "power" => 1.5, "type" => "earth"],
        ["name" => "Earthquake", "power" => 2.0, "type" => "earth"]
    ]
];

// --- MONSTER DATABASE ---
$allMonsters = [
    // --- BASIC (Common) ---
    ["name" => "Emberling", "type" => "fire", "max_hp" => 50, "attack" => 12, "rarity" => "basic", "image" => "emberling.png"],
    ["name" => "Tidepup", "type" => "water", "max_hp" => 60, "attack" => 10, "rarity" => "basic", "image" => "tidepup.png"],
    ["name" => "Gravhorn", "type" => "earth", "max_hp" => 70, "attack" => 8, "rarity" => "basic", "image" => "gravhorn.png"],
    ["name" => "Leafy", "type" => "earth", "max_hp" => 45, "attack" => 11, "rarity" => "basic", "image" => "leafy.png"],
    ["name" => "Pebbleback", "type" => "earth", "max_hp" => 80, "attack" => 7, "rarity" => "basic", "image" => "pebbleback.png"],
    ["name" => "Singeat", "type" => "fire", "max_hp" => 40, "attack" => 14, "rarity" => "basic", "image" => "singerat.png"],
    ["name" => "Dewlug", "type" => "water", "max_hp" => 90, "attack" => 5, "rarity" => "basic", "image" => "dewslug.png"],
    ["name" => "Gustirp", "type" => "fire", "max_hp" => 35, "attack" => 15, "rarity" => "basic", "image" => "gustchirp.png"],
    ["name" => "Mudig", "type" => "earth", "max_hp" => 75, "attack" => 9, "rarity" => "basic", "image" => "mudpig.png"],
    ["name" => "Zapug", "type" => "fire", "max_hp" => 30, "attack" => 18, "rarity" => "basic", "image" => "zapbug.png"],
    ["name" => "Thistox", "type" => "earth", "max_hp" => 55, "attack" => 13, "rarity" => "basic", "image" => "thistlefox.png"],
    ["name" => "Sporeap", "type" => "earth", "max_hp" => 100, "attack" => 4, "rarity" => "basic", "image" => "sporecap.png"],
    ["name" => "Flamoth", "type" => "fire", "max_hp" => 42, "attack" => 16, "rarity" => "basic", "image" => "flamemoth.png"],
    ["name" => "Rivewt", "type" => "water", "max_hp" => 65, "attack" => 11, "rarity" => "basic", "image" => "rivernewt.png"],
    ["name" => "Dustny", "type" => "earth", "max_hp" => 38, "attack" => 12, "rarity" => "basic", "image" => "dustbunny.png"],
    ["name" => "Shelab", "type" => "water", "max_hp" => 110, "attack" => 6, "rarity" => "basic", "image" => "shellcrab.png"],
    ["name" => "Prickledge", "type" => "earth", "max_hp" => 85, "attack" => 10, "rarity" => "basic", "image" => "pricklehedge.png"],
    ["name" => "Coalole", "type" => "fire", "max_hp" => 70, "attack" => 13, "rarity" => "basic", "image" => "coalmole.png"],

    // --- GREATER (Rare) ---
    ["name" => "Voltclaw", "type" => "fire", "max_hp" => 120, "attack" => 22, "rarity" => "greater", "image" => "voltclaw.png"],
    ["name" => "Frostfangor", "type" => "water", "max_hp" => 140, "attack" => 19, "rarity" => "greater", "image" => "frostfangor.png"],
    ["name" => "Cindeem", "type" => "fire", "max_hp" => 160, "attack" => 17, "rarity" => "greater", "image" => "cindeem.png"],
    ["name" => "Venomflare", "type" => "fire", "max_hp" => 110, "attack" => 25, "rarity" => "greater", "image" => "venomflare.png"],
    ["name" => "Shadowalker", "type" => "fire", "max_hp" => 100, "attack" => 30, "rarity" => "greater", "image" => "shadowstalker.png"],
    ["name" => "Ironlem", "type" => "earth", "max_hp" => 250, "attack" => 15, "rarity" => "greater", "image" => "irongolem.png"],
    ["name" => "Tidalpent", "type" => "water", "max_hp" => 180, "attack" => 24, "rarity" => "greater", "image" => "tidalserpent.png"],
    ["name" => "Solarawk", "type" => "fire", "max_hp" => 130, "attack" => 28, "rarity" => "greater", "image" => "solarhawk.png"],
    ["name" => "Brambleear", "type" => "earth", "max_hp" => 200, "attack" => 21, "rarity" => "greater", "image" => "bramblebear.png"],
    ["name" => "Magmaag", "type" => "fire", "max_hp" => 190, "attack" => 23, "rarity" => "greater", "image" => "magmacrag.png"],
    ["name" => "Crystalider", "type" => "earth", "max_hp" => 150, "attack" => 26, "rarity" => "greater", "image" => "crystalspider.png"],
    ["name" => "Stormam", "type" => "water", "max_hp" => 175, "attack" => 20, "rarity" => "greater", "image" => "stormram.png"],
    ["name" => "Abyssalay", "type" => "water", "max_hp" => 145, "attack" => 27, "rarity" => "greater", "image" => "abyssalray.png"],
    ["name" => "Dreadolf", "type" => "earth", "max_hp" => 135, "attack" => 32, "rarity" => "greater", "image" => "dreadwolf.png"],

    // --- ANCIENT (Legendary) ---
    ["name" => "Hydraskorn", "type" => "water", "max_hp" => 400, "attack" => 45, "rarity" => "ancient", "image" => "hydraskorn.png"],
    ["name" => "Celestyr", "type" => "fire", "max_hp" => 350, "attack" => 55, "rarity" => "ancient", "image" => "celestyr.png"],
    ["name" => "Rootan", "type" => "earth", "max_hp" => 500, "attack" => 35, "rarity" => "ancient", "image" => "roottitan.png"],
    ["name" => "Voiduiem", "type" => "fire", "max_hp" => 300, "attack" => 70, "rarity" => "ancient", "image" => "voidrequiem.png"],
    ["name" => "Aethenix", "type" => "fire", "max_hp" => 380, "attack" => 60, "rarity" => "ancient", "image" => "phoenix.png"],
    ["name" => "Chronole", "type" => "water", "max_hp" => 600, "attack" => 40, "rarity" => "ancient", "image" => "whale.png"],
    ["name" => "Nebulagon", "type" => "water", "max_hp" => 420, "attack" => 58, "rarity" => "ancient", "image" => "nebuladragon.png"],
    ["name" => "Omegaruct", "type" => "earth", "max_hp" => 550, "attack" => 48, "rarity" => "ancient", "image" => "omega.png"]
];

/**
 * spawns a fresh monster with moves and sets HP
 */
function spawnMonster($database) {
    global $moves;
    $monster = $database[array_rand($database)];
    
    $monster['hp'] = $monster['max_hp'];
    $monster['moves'] = $moves[$monster['type']];
    $monster['xp'] = 0; // Fresh monsters start at 0 XP (Level 1)
    
    return $monster;
}

/**
 * returns the damage multiplier based on types
 */
function getTypeMultiplier($atkType, $defType) {
    global $typeChart;
    return $typeChart[$atkType][$defType] ?? 1.0;
}
?>