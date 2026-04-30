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

//save game 
function saveGame($game, $file = "save.json") {
    file_put_contents($file, json_encode($game));
}

//generates monester unique IDs
function generateMonsterId() {
    return uniqid();
}

//record capture
function recordCapture(&$game, $monsterName) {
    if (!isset($game['player']['discovered'])) $game['player']['discovered'] = [];
    if (!in_array($monsterName, $game['player']['discovered'])) {
        $game['player']['discovered'][] = $monsterName;
    }
}

//spawn monster
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

//battle rewards
function getBattleRewards(&$game) {
    $amount = rand(15, 45);
    $game['player']['gold'] += $amount;
    return $amount;
}

//buy items
function buyItem(&$game, $itemType, $cost) {
    if (($game['player']['gold'] ?? 0) >= $cost) {
        $game['player']['gold'] -= $cost;
        $game['inventory'][$itemType]++;
        return true;
    }
    return false;
}

//discard from roster
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

// Calculate level based on total XP
function getLevel($xp) {
    // Each level requires 100 XP (Level 1 = 0-99 XP, Level 2 = 100-199, etc.)
    return floor($xp / 100) + 1;
}

// Updated gainXP with a "Level Up" message check
function gainXP(&$monster, $amount) {
    $oldLevel = getLevel($monster['xp']);
    $monster['xp'] += $amount;
    $newLevel = getLevel($monster['xp']);
    
    if ($newLevel > $oldLevel) {
        // Boost stats on level up
        $monster['max_hp'] += 10;
        $monster['hp'] = $monster['max_hp']; // Full heal on level up!
        $monster['attack'] += 5;
        return "Level Up! {$monster['name']} is now Level $newLevel!";
    }
    return false;
}

//catch logic
function attemptCatch($h, $m, $b) {
    $chance = (1 - ($h / $m)) * 100 + $b;
    return rand(1, 100) <= $chance;
}

// potions logic
function usePotion(&$game, $type = 'basic') {
    $pm = &$game['player']['roster'][$game['player']['active']];
    
    // Define healing amounts
    $heals = [
        'potions' => 30,      // Basic
        'super_potions' => 100, // Super
        'max_potions' => 999    // Full Heal
    ];

    if (($game['inventory'][$type] ?? 0) > 0) {
        $game['inventory'][$type]--;
        $pm['hp'] = min($pm['hp'] + $heals[$type], $pm['max_hp']);
        return true;
    }
    return false;
}
?>