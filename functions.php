<?php
function loadGame($file = "save.json") {
    if (!file_exists($file) || filesize($file) === 0) {
        return [
            "player" => ["roster" => [], "active" => 0, "gold" => 0, "discovered" => []],
            "inventory" => ["potions" => 0, "basic" => 0, "greater" => 0, "ancient" => 0],
            "currentBattle" => null,
            "message" => "Welcome to Soul Stone RPG!"
        ];
    }
    return json_decode(file_get_contents($file), true);
}

function saveGame($game, $file = "save.json") {
    file_put_contents($file, json_encode($game));
}

function generateMonsterId() {
    return uniqid();
}

function recordCapture(&$game, $monsterName) {
    if (!isset($game['player']['discovered'])) $game['player']['discovered'] = [];
    if (!in_array($monsterName, $game['player']['discovered'])) {
        $game['player']['discovered'][] = $monsterName;
    }
}

function spawnMonster($allMonsters) {
    $roll = rand(1, 100);
    $target = ($roll <= 5) ? "ancient" : (($roll <= 30) ? "greater" : "basic");
    
    $pool = array_filter($allMonsters, function($m) use ($target) {
        return $m['rarity'] === $target;
    });
    
    $wild = $pool[array_rand($pool)];
    $wild['hp'] = $wild['max_hp'];
    return $wild;
}

function getBattleRewards(&$game) {
    $amount = rand(15, 45);
    $game['player']['gold'] += $amount;
    return $amount;
}

function buyItem(&$game, $itemType, $cost) {
    if (($game['player']['gold'] ?? 0) >= $cost) {
        $game['player']['gold'] -= $cost;
        $game['inventory'][$itemType]++;
        return true;
    }
    return false;
}

function discardFromRoster(&$game, $monsterId) {
    foreach ($game['player']['roster'] as $index => $monster) {
        if ($monster['id'] === $monsterId) {
            array_splice($game['player']['roster'], $index, 1);
            if ($game['player']['active'] >= count($game['player']['roster'])) {
                $game['player']['active'] = 0;
            }
            return true;
        }
    }
    return false;
}

function gainXP(&$monster, $amount) {
    $monster['xp'] += $amount;
    if ($monster['xp'] >= 100) {
        $monster['level']++;
        $monster['xp'] = 0;
        $monster['max_hp'] += 10;
        $monster['hp'] = $monster['max_hp'];
        $monster['attack'] += 2;
        return true;
    }
    return false;
}

function attemptCatch($h, $m, $b) {
    $chance = (1 - ($h / $m)) * 100 + $b;
    return rand(1, 100) <= $chance;
}
?>