<?php
function loadGame($file = "save.json") {
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    // default starting game
    return [
        "player" => [
            "active" => 0,
            "roster" => [],
            "gold" => 0
        ],
        "inventory" => [
            "potions" => 10, 
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


// reward logic -----
function getBattleRewards(&$game){
    //random money generate between 10 and 100)
    $amount = rand(10, 100);
    $game['player']['gold'] += $amount;
    return $amount;
}

// store logic
function buyItem(&$game, $itemType, $cost) {
    if ($game['player']['gold'] >= $cost){
        $game['player']['gold'] -= $cost;
        $game['inventory'][$itemType] ++;
        return true;
    }
    return false;
}

//Roster Mangagement
function addToRoster(&$game, $monster) {
    if (count($game['player']['roster']) < 8){
        $game['player']['roster'][] = $monster;
        return true; //for successful adding
    }
    return false; //the roster is full
}

//random id for monsters
function generateMonsterId() {
    return uniqid(); 
}

//discard from roster
function discardFromRoster(&$game, $index){
    if (isset($game['player']['roster'][$index])){
        //remove a specific monster and rest the array
        array_splice($game['player']['roster'], $index, 1);

        //if the active monster gets deleted accidentally the active goes to 0
        if ($game['player']['active'] >= count($game['player']['roster']))
            {
                $game['player']['active'] = 0;
            }
            return true;
    }
    return false; 
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