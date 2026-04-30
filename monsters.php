<?php
$allMonsters = [
    // --- BASIC / COMMON (Spawn rate: 70%) ---
    ["name" => "Emberling", "max_hp" => 50, "attack" => 12, "level" => 1, "image" => "emberling.png", "rarity" => "basic"],
    ["name" => "Tidepup", "max_hp" => 60, "attack" => 10, "level" => 1, "image" => "tidepup.png", "rarity" => "basic"],
    ["name" => "Gravhorn", "max_hp" => 70, "attack" => 8, "level" => 1, "image" => "gravhorn.png", "rarity" => "basic"],
    ["name" => "Leafy", "max_hp" => 45, "attack" => 11, "level" => 2, "image" => "leafy.png", "rarity" => "basic"],
    ["name" => "Pebbleback", "max_hp" => 80, "attack" => 7, "level" => 2, "image" => "pebbleback.png", "rarity" => "basic"],
    ["name" => "Singeat", "max_hp" => 40, "attack" => 14, "level" => 2, "image" => "singerat.png", "rarity" => "basic"],
    ["name" => "Dewlug", "max_hp" => 90, "attack" => 5, "level" => 1, "image" => "dewslug.png", "rarity" => "basic"],
    ["name" => "Gustirp", "max_hp" => 35, "attack" => 15, "level" => 3, "image" => "gustchirp.png", "rarity" => "basic"],
    ["name" => "Mudig", "max_hp" => 75, "attack" => 9, "level" => 2, "image" => "mudpig.png", "rarity" => "basic"],
    ["name" => "Zapug", "max_hp" => 30, "attack" => 18, "level" => 3, "image" => "zapbug.png", "rarity" => "basic"],
    ["name" => "Thistox", "max_hp" => 55, "attack" => 13, "level" => 3, "image" => "thistlefox.php", "rarity" => "basic"],
    ["name" => "Sporeap", "max_hp" => 100, "attack" => 4, "level" => 2, "image" => "sporecap.png", "rarity" => "basic"],
    ["name" => "Flamoth", "max_hp" => 42, "attack" => 16, "level" => 3, "image" => "flamemoth.png", "rarity" => "basic"],
    ["name" => "Rivewt", "max_hp" => 65, "attack" => 11, "level" => 2, "image" => "rivernewt.png", "rarity" => "basic"],
    ["name" => "Dustny", "max_hp" => 38, "attack" => 12, "level" => 1, "image" => "dustbunny.png", "rarity" => "basic"],
    ["name" => "Shelab", "max_hp" => 110, "attack" => 6, "level" => 4, "image" => "shellcrab.png", "rarity" => "basic"],
    ["name" => "Prickledge", "max_hp" => 85, "attack" => 10, "level" => 4, "image" => "pricklehedge.png", "rarity" => "basic"],
    ["name" => "Coalole", "max_hp" => 70, "attack" => 13, "level" => 3, "image" => "coalmole.png", "rarity" => "basic"],

    // --- GREATER / UNCOMMON (Spawn rate: 25%) ---
    ["name" => "Voltclaw", "max_hp" => 120, "attack" => 22, "level" => 10, "image" => "voltclaw.png", "rarity" => "greater"],
    ["name" => "Frostfangor", "max_hp" => 140, "attack" => 19, "level" => 12, "image" => "frostfangor.png", "rarity" => "greater"],
    ["name" => "Cindeem", "max_hp" => 160, "attack" => 17, "level" => 15, "image" => "cindeem.png", "rarity" => "greater"],
    ["name" => "Venomflare", "max_hp" => 110, "attack" => 25, "level" => 14, "image" => "venomflare.png", "rarity" => "greater"],
    ["name" => "Shadowalker", "max_hp" => 100, "attack" => 30, "level" => 18, "image" => "shadowstalker.png", "rarity" => "greater"],
    ["name" => "Ironlem", "max_hp" => 250, "attack" => 15, "level" => 20, "image" => "irongolem.png", "rarity" => "greater"],
    ["name" => "Tidalpent", "max_hp" => 180, "attack" => 24, "level" => 18, "image" => "tidalserpent.png", "rarity" => "greater"],
    ["name" => "Solarawk", "max_hp" => 130, "attack" => 28, "level" => 16, "image" => "solarhawk.png", "rarity" => "greater"],
    ["name" => "Brambleear", "max_hp" => 200, "attack" => 21, "level" => 15, "image" => "bramblebear.png", "rarity" => "greater"],
    ["name" => "Magmaag", "max_hp" => 190, "attack" => 23, "level" => 17, "image" => "magmacrag.png", "rarity" => "greater"],
    ["name" => "Crystalider", "max_hp" => 150, "attack" => 26, "level" => 19, "image" => "crystalspider.png", "rarity" => "greater"],
    ["name" => "Stormam", "max_hp" => 175, "attack" => 20, "level" => 18, "image" => "stormram.png", "rarity" => "greater"],
    ["name" => "Abyssalay", "max_hp" => 145, "attack" => 27, "level" => 21, "image" => "abyssalray.png", "rarity" => "greater"],
    ["name" => "Dreadolf", "max_hp" => 135, "attack" => 32, "level" => 22, "image" => "dreadwolf.png", "rarity" => "greater"],

    // --- ANCIENT / RARE (Spawn rate: 5%) ---
    ["name" => "Hydraskorn", "max_hp" => 400, "attack" => 45, "level" => 45, "image" => "hydraskorn.png", "rarity" => "ancient"],
    ["name" => "Celestyr", "max_hp" => 350, "attack" => 55, "level" => 50, "image" => "celestyr.png", "rarity" => "ancient"],
    ["name" => "Rootan", "max_hp" => 500, "attack" => 35, "level" => 40, "image" => "roottitan.png", "rarity" => "ancient"],
    ["name" => "Voiduiem", "max_hp" => 300, "attack" => 70, "level" => 55, "image" => "voidrequiem.png", "rarity" => "ancient"],
    ["name" => "Aethenix", "max_hp" => 380, "attack" => 60, "level" => 52, "image" => "phoenix.png", "rarity" => "ancient"],
    ["name" => "Chronole", "max_hp" => 600, "attack" => 40, "level" => 60, "image" => "whale.png", "rarity" => "ancient"],
    ["name" => "Nebulagon", "max_hp" => 420, "attack" => 58, "level" => 55, "image" => "nebuladragon.png", "rarity" => "ancient"],
    ["name" => "Omegaruct", "max_hp" => 550, "attack" => 48, "level" => 58, "image" => "omega.png", "rarity" => "ancient"]
];
?>