```php
<?php

// new game & starter selection

// Load game functions
require_once __DIR__ . '/functions.php';

// Load monster database
require_once __DIR__ . '/monsters.php';


// initial game setup

// Get the three starter monsters
$starters = getStarterMonsters($allMonsters);

// Current screen
$step = 'intro';

// Selected starter
$selectedStarter = null;

// Message
$message = '';


// form action handler

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';


    // new game

    if ($action === 'begin') {

        $step = 'selection';
    }


    // view starter

    elseif ($action === 'view_starter') {

        $starterName = strtolower(
            trim($_POST['starter'] ?? '')
        );


        // Make sure the selected monster is one of
        // the three valid starters.

        if (isset($starters[$starterName])) {

            $selectedStarter = $starterName;

            $step = 'preview';

        } else {

            $message =
                'Invalid starter selection.';

            $step = 'selection';
        }
    }


    // pick a different starter

    elseif ($action === 'pick_another') {

        // Return to the three Soul Stones

        $selectedStarter = null;

        $step = 'selection';
    }


    // choose a starter

    elseif ($action === 'choose_starter') {

        /*
         * The selected starter is sent as a hidden field.
         * This prevents relying on the browser session alone.
         */

        $starterName = strtolower(
            trim($_POST['starter'] ?? '')
        );


        // Verify the starter exists

        $starter = getStarterMonster(
            $allMonsters,
            $starterName
        );


        // Only allow the official three starters

        if (
            $starter === null ||
            !isset($starters[$starterName])
        ) {

            $message =
                'That monster cannot be selected.';

            $step = 'selection';

        } else {

            // creates a brand new game

            $game = createNewGame();


            // adds a starter

            startGameWithStarter(
                $game,
                $starter
            );


            // generates a battle code

            $battleCode =
                createBattleSave($game);


            // Make sure the code exists in memory

            $game['battle_code'] =
                $battleCode;


            // Save the completed new game

            saveBattleGame(
                $battleCode,
                $game
            );


            // sends player to main game

            header(
                'Location: main.php?code=' .
                urlencode($battleCode)
            );

            exit;
        }
    }
}


// starter descriptions

$descriptions = [

    'emberling' =>
        'A fiery little companion with a brave heart. Emberling is quick, aggressive, and excels at dealing damage.',

    'tidepup' =>
        'A loyal water companion known for its adaptability. Tidepup has strong health and can withstand difficult battles.',

    'gravhorn' =>
        'A sturdy earth monster built for endurance. Gravhorn has powerful defenses and the highest starting HP of the three.'
];


// actual page html

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        New Game | Soul Stone RPG
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

</head>


<body>


<!-- nav-->

<nav class="top-nav">

    <div class="logo">

        <a href="index.php">

            <strong>
                SOUL STONE RPG
            </strong>

        </a>

    </div>

</nav>


<!-- game container -->

<main class="new-game-container">


<?php if ($step === 'intro'): ?>


    <!-- introduction -->

    <section class="tutorial-panel">

        <div class="tutorial-icon">
            ◆
        </div>


        <p class="tutorial-step">
            WELCOME, SOUL KEEPER
        </p>


        <h1>
            YOUR JOURNEY BEGINS
        </h1>


        <p class="tutorial-text">

            The world of Soul Stone is filled with
            mysterious creatures known as monsters.

        </p>


        <p class="tutorial-text">

            Soul Keepers travel the world,
            discover monsters, battle wild creatures,
            and use Soul Stones to capture them.

        </p>


        <p class="tutorial-text">

            Before you begin your adventure,
            you must choose your first companion.

        </p>


        <div class="tutorial-tip">

            <strong>
                YOUR STARTING RESOURCES
            </strong>

            <br>

            You will begin with:

            <br><br>

            <strong>500 GOLD</strong>

            <br>

            <strong>1 STARTER MONSTER</strong>

        </div>


        <form method="post">

            <button
                type="submit"
                name="action"
                value="begin"
                class="tutorial-btn"
            >
                CHOOSE YOUR FIRST MONSTER
            </button>

        </form>

    </section>


<?php elseif ($step === 'selection'): ?>


    <!-- choose starter -->

    <section class="starter-selection">

        <p class="tutorial-step">
            STEP 1
        </p>


        <h1>
            CHOOSE YOUR FIRST SOUL
        </h1>


        <p class="tutorial-text">

            Three Soul Stones await you.

            Each contains a monster with a
            different elemental affinity.

        </p>


        <?php if ($message): ?>

            <div class="start-message error">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php endif; ?>


        <div class="starter-stones">


            <?php foreach ($starters as $name => $starter): ?>


                <form
                    method="post"
                    class="starter-stone-form"
                >

                    <input
                        type="hidden"
                        name="starter"
                        value="<?php echo htmlspecialchars($name); ?>"
                    >


                    <button
                        type="submit"
                        name="action"
                        value="view_starter"
                        class="starter-stone <?php echo htmlspecialchars($starter['type']); ?>"
                    >


                        <!-- STONE SYMBOL -->

                        <div class="stone-glow">


                            <?php if ($starter['type'] === 'fire'): ?>

                                🔥

                            <?php elseif ($starter['type'] === 'water'): ?>

                                💧

                            <?php else: ?>

                                🌱

                            <?php endif; ?>


                        </div>


                        <!-- STONE TYPE -->

                        <div class="stone-type">

                            <?php echo strtoupper(
                                $starter['type']
                            ); ?>

                            STONE

                        </div>


                        <!-- MONSTER IMAGE -->

                        <img
                            src="images/monsters/<?php echo htmlspecialchars($starter['image']); ?>"
                            alt="<?php echo htmlspecialchars($starter['name']); ?>"
                        >


                        <!-- MONSTER NAME -->

                        <strong>

                            <?php echo htmlspecialchars(
                                ucfirst($starter['name'])
                            ); ?>

                        </strong>


                        <span>
                            Click to learn more
                        </span>


                    </button>

                </form>


            <?php endforeach; ?>


        </div>


        <div class="tutorial-tip">

            <strong>
                CHOOSE CAREFULLY
            </strong>

            <br>

            Your first monster will become
            the foundation of your team.

        </div>


    </section>


<?php elseif ($step === 'preview' && $selectedStarter !== null): ?>


    <!-- starter preview -->

    <?php

    $starter =
        $starters[$selectedStarter];


    $starterLevel =
        getLevel(
            $starter['xp'] ?? 0
        );


    $description =
        $descriptions[
            strtolower(
                $starter['name']
            )
        ]
        ?? 'A mysterious creature ready to begin its journey with you.';

    ?>


    <section class="starter-preview">


        <p class="tutorial-step">

            YOUR POTENTIAL COMPANION

        </p>


        <div class="preview-card">


            <!-- type -->

            <div
                class="preview-type <?php echo htmlspecialchars($starter['type']); ?>"
            >

                <?php echo strtoupper(
                    $starter['type']
                ); ?>

            </div>


            <!-- NAME -->

            <h1>

                <?php echo htmlspecialchars(
                    ucfirst($starter['name'])
                ); ?>

            </h1>


            <!-- image -->

            <div class="preview-image">

                <img
                    src="images/monsters/<?php echo htmlspecialchars($starter['image']); ?>"
                    alt="<?php echo htmlspecialchars($starter['name']); ?>"
                >

            </div>


            <!-- description -->

            <div class="preview-description">

                <?php echo htmlspecialchars(
                    $description
                ); ?>

            </div>


            <!-- stats -->

            <div class="preview-stats">


                <div>

                    <span>
                        TYPE
                    </span>

                    <strong>
                        <?php echo htmlspecialchars(
                            strtoupper($starter['type'])
                        ); ?>
                    </strong>

                </div>


                <div>

                    <span>
                        HP
                    </span>

                    <strong>
                        <?php echo (int)$starter['max_hp']; ?>
                    </strong>

                </div>


                <div>

                    <span>
                        ATTACK
                    </span>

                    <strong>
                        <?php echo (int)$starter['attack']; ?>
                    </strong>

                </div>


                <div>

                    <span>
                        LEVEL
                    </span>

                    <strong>
                        <?php echo (int)$starterLevel; ?>
                    </strong>

                </div>


            </div>


            <!-- action buttons -->

            <div class="starter-actions">


                <!-- CHOOSE -->

                <form method="post">

                    <input
                        type="hidden"
                        name="starter"
                        value="<?php echo htmlspecialchars($selectedStarter); ?>"
                    >


                    <button
                        type="submit"
                        name="action"
                        value="choose_starter"
                        class="choose-starter-btn"
                    >

                        CHOOSE

                        <?php echo strtoupper(
                            $starter['name']
                        ); ?>

                    </button>

                </form>


                <!-- PICK ANOTHER -->

                <form method="post">

                    <button
                        type="submit"
                        name="action"
                        value="pick_another"
                        class="another-starter-btn"
                    >

                        PICK ANOTHER

                    </button>

                </form>


            </div>


        </div>


    </section>


<?php endif; ?>


</main>


</body>

</html>
```
