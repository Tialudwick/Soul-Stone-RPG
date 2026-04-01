<?php
function loadGame($file = "save.json") {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    // default starting game
    return [
        "player" => [
            "active" => 0,
            "roster" => []
        ],
        "inventory" => [
            "basic" => 5,
            "greater" => 2,
            "ancient" => 1
        ],
        "currentBattle" => null,
        "message" => ""
    ];
}

function saveGame($game, $file = "save.json") {
    file_put_contents($file, json_encode($game));
}

// Attempt to catch a monster
function attemptCatch($monsterHP, $maxHP, $bonus) {
    $chance = (1 - ($monsterHP / $maxHP)) * 100 + $bonus;
    return rand(1, 100) <= $chance;
}

// Gain XP and level up
function gainXP(&$monster, $xp) {
    $monster['xp'] = ($monster['xp'] ?? 0) + $xp;
    $levelUp = false;
    if ($monster['xp'] >= 20) {
        $monster['level']++;
        $monster['max_hp'] += 10;
        $monster['attack'] += 2;
        $monster['xp'] = 0;
        $levelUp = true;
    }
    return $levelUp;
}

// Choose a random wild monster
function spawnMonster($allMonsters) {
    $wild = $allMonsters[array_rand($allMonsters)];
    $wild['hp'] = $wild['max_hp'];
    return $wild;
}

$soulStones = [
    "basic" => ["name"=>"Basic Soul Stone","bonus"=>0],
    "greater" => ["name"=>"Greater Soul Stone","bonus"=>15],
    "ancient" => ["name"=>"Ancient Soul Stone","bonus"=>30]
];
?>