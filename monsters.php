<?php
//Element type chart
// Multiplier: [Attacker][Defender]
$typeChart = [
    "fire"  => ["fire" => 1.0, "water" => 0.5, "earth" => 2.0],
    "water" => ["fire" => 2.0, "water" => 1.0, "earth" => 0.5],
    "earth" => ["fire" => 0.5, "water" => 2.0, "earth" => 1.0]
];

// Move Definitions
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

// Monster Database
$allMonsters = [
   // --- BASIC (Common) ---
["name" => "emberling", "type" => "fire", "max_hp" => 50, "attack" => 12, "rarity" => "basic", "image" => "emberling.png"],
["name" => "tidepup", "type" => "water", "max_hp" => 60, "attack" => 10, "rarity" => "basic", "image" => "tidepup.png"],
["name" => "gravhorn", "type" => "earth", "max_hp" => 70, "attack" => 8, "rarity" => "basic", "image" => "gravhorn.png"],
["name" => "leafy", "type" => "earth", "max_hp" => 45, "attack" => 11, "rarity" => "basic", "image" => "leafy.png"],
["name" => "pebbleback", "type" => "earth", "max_hp" => 80, "attack" => 7, "rarity" => "basic", "image" => "pebbleback.png"],
["name" => "singeat", "type" => "fire", "max_hp" => 40, "attack" => 14, "rarity" => "basic", "image" => "singeat.png"],
["name" => "dewlug", "type" => "water", "max_hp" => 90, "attack" => 5, "rarity" => "basic", "image" => "dewlug.png"],
["name" => "gustirp", "type" => "fire", "max_hp" => 35, "attack" => 15, "rarity" => "basic", "image" => "gustirp.png"],
["name" => "mudig", "type" => "earth", "max_hp" => 75, "attack" => 9, "rarity" => "basic", "image" => "mudig.png"],
["name" => "zapug", "type" => "fire", "max_hp" => 30, "attack" => 18, "rarity" => "basic", "image" => "zapug.png"],
["name" => "thistox", "type" => "earth", "max_hp" => 55, "attack" => 13, "rarity" => "basic", "image" => "thistox.png"],
["name" => "sporeap", "type" => "earth", "max_hp" => 100, "attack" => 4, "rarity" => "basic", "image" => "sporeap.png"],
["name" => "flamoth", "type" => "fire", "max_hp" => 42, "attack" => 16, "rarity" => "basic", "image" => "flamoth.png"],
["name" => "rivewt", "type" => "water", "max_hp" => 65, "attack" => 11, "rarity" => "basic", "image" => "rivewt.png"],
["name" => "dustny", "type" => "earth", "max_hp" => 38, "attack" => 12, "rarity" => "basic", "image" => "dustny.png"],
["name" => "shelab", "type" => "water", "max_hp" => 110, "attack" => 6, "rarity" => "basic", "image" => "shelab.png"],
["name" => "prickledge", "type" => "earth", "max_hp" => 85, "attack" => 10, "rarity" => "basic", "image" => "prickledge.png"],
["name" => "coalole", "type" => "fire", "max_hp" => 70, "attack" => 13, "rarity" => "basic", "image" => "coalole.png"],


// --- GREATER (Rare) ---
["name" => "voltclaw", "type" => "fire", "max_hp" => 120, "attack" => 22, "rarity" => "greater", "image" => "voltclaw.png"],
["name" => "frostfangor", "type" => "water", "max_hp" => 140, "attack" => 19, "rarity" => "greater", "image" => "frostfangor.png"],
["name" => "cindeem", "type" => "fire", "max_hp" => 160, "attack" => 17, "rarity" => "greater", "image" => "cindeem.png"],
["name" => "venomflare", "type" => "fire", "max_hp" => 110, "attack" => 25, "rarity" => "greater", "image" => "venomflare.png"],
["name" => "shadowalker", "type" => "fire", "max_hp" => 100, "attack" => 30, "rarity" => "greater", "image" => "shadowalker.png"],
["name" => "ironlem", "type" => "earth", "max_hp" => 250, "attack" => 15, "rarity" => "greater", "image" => "ironlem.png"],
["name" => "tidalpent", "type" => "water", "max_hp" => 180, "attack" => 24, "rarity" => "greater", "image" => "tidalpent.png"],
["name" => "solarawk", "type" => "fire", "max_hp" => 130, "attack" => 28, "rarity" => "greater", "image" => "solarawk.png"],
["name" => "bramblebear", "type" => "earth", "max_hp" => 200, "attack" => 21, "rarity" => "greater", "image" => "bramblebear.png"],
["name" => "magmaag", "type" => "fire", "max_hp" => 190, "attack" => 23, "rarity" => "greater", "image" => "magmaag.png"],
["name" => "crystalider", "type" => "earth", "max_hp" => 150, "attack" => 26, "rarity" => "greater", "image" => "crystalider.png"],
["name" => "stormam", "type" => "water", "max_hp" => 175, "attack" => 20, "rarity" => "greater", "image" => "stormam.png"],
["name" => "abyssalay", "type" => "water", "max_hp" => 145, "attack" => 27, "rarity" => "greater", "image" => "abyssalay.png"],
["name" => "dreadolf", "type" => "earth", "max_hp" => 135, "attack" => 32, "rarity" => "greater", "image" => "dreadolf.png"],


// --- ANCIENT (Legendary) ---
["name" => "hydraskorn", "type" => "water", "max_hp" => 400, "attack" => 45, "rarity" => "ancient", "image" => "hydraskorn.png"],
["name" => "celestyr", "type" => "fire", "max_hp" => 350, "attack" => 55, "rarity" => "ancient", "image" => "celestyr.png"],
["name" => "rootan", "type" => "earth", "max_hp" => 500, "attack" => 35, "rarity" => "ancient", "image" => "rootan.png"],
["name" => "voiduiem", "type" => "fire", "max_hp" => 300, "attack" => 70, "rarity" => "ancient", "image" => "voiduiem.png"],
["name" => "aethenix", "type" => "fire", "max_hp" => 380, "attack" => 60, "rarity" => "ancient", "image" => "aethenix.png"],
["name" => "chronole", "type" => "water", "max_hp" => 600, "attack" => 40, "rarity" => "ancient", "image" => "chronole.png"],
["name" => "nebulagon", "type" => "water", "max_hp" => 420, "attack" => 58, "rarity" => "ancient", "image" => "nebulagon.png"],
["name" => "omegaruct", "type" => "earth", "max_hp" => 550, "attack" => 48, "rarity" => "ancient", "image" => "omegaruct.png"]
];

/*pawns a fresh monster with moves and sets HP
 
function spawnMonster($database) {
    global $moves;
    $monster = $database[array_rand($database)];
    
    $monster['hp'] = $monster['max_hp'];
    $monster['moves'] = $moves[$monster['type']];
    $monster['xp'] = 0; // Fresh monsters start at 0 XP (Level 1)
    
    return $monster;
}

//returns the damage multiplier based on types

function getTypeMultiplier($atkType, $defType) {
    global $typeChart;
    return $typeChart[$atkType][$defType] ?? 1.0;
}
    */
?>
