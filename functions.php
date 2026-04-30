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

// --- NEW SCALING LEVEL SYSTEM ---

// Calculates TOTAL XP needed to reach a specific level
function getXPForLevel($level) {
    if ($level <= 1) return 0;
    // Level 2: 100 XP, Level 3: 300 XP, Level 4: 600 XP (Increases by Level * 100)
    return ($level * ($level - 1) / 2) * 100;
}

function getLevel($xp) {
    $level = 1;
    while ($xp >= getXPForLevel($level + 1)) {
        $level++;
    }
    return $level;
}

// Data for the blue XP Progress Bar
function getXPStats($xp) {
    $lvl = getLevel($xp);
    $currentLvlTotal = getXPForLevel($lvl);
    $nextLvlTotal = getXPForLevel($lvl + 1);
    
    $xpInCurrentLevel = $xp - $currentLvlTotal;
    $xpNeededForNext = $nextLvlTotal - $currentLvlTotal;
    $percent = ($xpNeededForNext > 0) ? ($xpInCurrentLevel / $xpNeededForNext) * 100 : 0;

    return [
        'level' => $lvl,
        'current' => $xpInCurrentLevel,
        'needed' => $xpNeededForNext,
        'percent' => $percent
    ];
}

function gainXP(&$monster, $amount) {
    $oldLevel = getLevel($monster['xp'] ?? 0);
    $monster['xp'] += $amount;
    $newLevel = getLevel($monster['xp']);
    
    if ($newLevel > $oldLevel) {
        $levelsGained = $newLevel - $oldLevel;
        $monster['max_hp'] += (10 * $levelsGained);
        $monster['hp'] = $monster['max_hp']; 
        $monster['attack'] += (5 * $levelsGained);
        return "Level Up! {$monster['name']} is now Level $newLevel!";
    }
    return false;
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