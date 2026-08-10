<?php

session_start();

require_once 'functions.php';
require_once 'monsters.php';


// ============================================================
// STARTER DATA
// ============================================================

$starters = getStarterMonsters($allMonsters);


// ============================================================
// NEW GAME
// ============================================================

if (
    !isset(
        $_SESSION['new_game']
    )
) {

    $_SESSION['new_game'] =
        createNewGame();
}


$game =
    &$_SESSION['new_game'];


// ============================================================
// CURRENT STEP
// ============================================================

$step =
    $_SESSION['new_game_step']
    ?? 'welcome';


// ============================================================
// SELECTED STARTER
// ============================================================

$selectedStarter =
    $_SESSION['selected_starter']
    ?? null;


// ============================================================
// POST ACTIONS
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    $action =
        $_POST['action'] ?? '';


    // --------------------------------------------------------
    // BEGIN
    // --------------------------------------------------------

    if ($action === 'begin') {

        $_SESSION['new_game_step'] =
            'choose';

        header(
            'Location: newgame.php'
        );

        exit;
    }


    // --------------------------------------------------------
    // VIEW STARTER
    // --------------------------------------------------------

    if ($action === 'view_starter') {

        $starterName =
            strtolower(
                trim(
                    $_POST['starter'] ?? ''
                )
            );


        if (
            isset(
                $starters[$starterName]
            )
        ) {

            $_SESSION['selected_starter'] =
                $starterName;

            $_SESSION['new_game_step'] =
                'preview';
        }


        header(
            'Location: newgame.php'
        );

        exit;
    }


    // --------------------------------------------------------
    // PICK ANOTHER
    // --------------------------------------------------------

    if ($action === 'pick_another') {

        unset(
            $_SESSION['selected_starter']
        );

        $_SESSION['new_game_step'] =
            'choose';


        header(
            'Location: newgame.php'
        );

        exit;
    }


    // --------------------------------------------------------
    // CHOOSE STARTER
    // --------------------------------------------------------

    if ($action === 'choose_starter') {

        $starterName =
            $_SESSION['selected_starter']
            ?? null;


        if (
            $starterName &&
            isset(
                $starters[$starterName]
            )
        ) {

            startGameWithStarter(
                $game,
                $starters[$starterName]
            );


            /*
             * Create the Battle Code AFTER the
             * starter has been selected.
             */

            $battleCode =
                createBattleSave(
                    $game
                );


            $_SESSION['battle_code'] =
                $battleCode;


            unset(
                $_SESSION['new_game']
            );

            unset(
                $_SESSION['new_game_step']
            );

            unset(
                $_SESSION['selected_starter']
            );


            header(
                'Location: index.php'
            );

            exit;
        }
    }
}

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

<body class="new-game-page">


<nav class="top-nav">

    <div class="logo">

        <a href="start.php">

            <strong>
                SOUL STONE RPG
            </strong>

        </a>

    </div>

</nav>


<main class="new-game-container">


<?php if ($step === 'welcome'): ?>


    <!-- =====================================================
         INTRODUCTION
         ===================================================== -->

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


<?php elseif ($step === 'choose'): ?>


    <!-- =====================================================
         CHOOSE STARTER
         ===================================================== -->

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


                        <div class="stone-glow">

                            <?php if ($starter['type'] === 'fire'): ?>

                                🔥

                            <?php elseif ($starter['type'] === 'water'): ?>

                                💧

                            <?php else: ?>

                                🌱

                            <?php endif; ?>

                        </div>


                        <div class="stone-type">

                            <?php echo strtoupper(
                                $starter['type']
                            ); ?>

                            STONE

                        </div>


                        <img
                            src="images/monsters/<?php echo htmlspecialchars($starter['image']); ?>"
                            alt="<?php echo htmlspecialchars($starter['name']); ?>"
                        >


                        <strong>

                            <?php echo htmlspecialchars(
                                $starter['name']
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

            Your first monster will become
            the foundation of your team.

        </div>

    </section>


<?php elseif (
    $step === 'preview' &&
    $selectedStarter &&
    isset($starters[$selectedStarter])
): ?>


    <!-- =====================================================
         STARTER PREVIEW
         ===================================================== -->

    <?php

    $starter =
        $starters[$selectedStarter];

    $starterLevel =
        getLevel(
            $starter['xp'] ?? 0
        );

    ?>


    <section class="starter-preview">

        <p class="tutorial-step">

            YOUR POTENTIAL COMPANION

        </p>


        <div class="preview-card">

            <div
                class="preview-type <?php echo htmlspecialchars($starter['type']); ?>"
            >

                <?php echo strtoupper(
                    $starter['type']
                ); ?>

            </div>


            <h1>

                <?php echo htmlspecialchars(
                    $starter['name']
                ); ?>

            </h1>


            <div class="preview-image">

                <img
                    src="images/monsters/<?php echo htmlspecialchars($starter['image']); ?>"
                    alt="<?php echo htmlspecialchars($starter['name']); ?>"
                >

            </div>


            <div class="preview-description">

                <?php

                $descriptions = [

                    'emberling' =>
                        'A fiery little companion with a brave heart. Emberling is quick, aggressive, and excels at dealing damage.',

                    'tidepup' =>
                        'A loyal water companion known for its adaptability. Tidepup has strong health and can withstand difficult battles.',

                    'gravhorn' =>
                        'A sturdy earth monster built for endurance. Gravhorn has powerful defenses and the highest starting HP of the three.'

                ];


                echo htmlspecialchars(
                    $descriptions[
                        strtolower(
                            $starter['name']
                        )
                    ]
                    ?? 'A mysterious creature ready to begin its journey with you.'
                );

                ?>

            </div>


            <div class="preview-stats">

                <div>

                    <span>
                        TYPE
                    </span>

                    <strong>
                        <?php echo htmlspecialchars(
                            $starter['type']
                        ); ?>
                    </strong>

                </div>


                <div>

                    <span>
                        HP
                    </span>

                    <strong>
                        <?php echo $starter['max_hp']; ?>
                    </strong>

                </div>


                <div>

                    <span>
                        ATTACK
                    </span>

                    <strong>
                        <?php echo $starter['attack']; ?>
                    </strong>

                </div>


                <div>

                    <span>
                        LEVEL
                    </span>

                    <strong>
                        <?php echo $starterLevel; ?>
                    </strong>

                </div>

            </div>


            <!-- ==============================================
                 ACTIONS
                 ============================================== -->

            <div class="starter-actions">


                <form method="post">

                    <button
                        type="submit"
                        name="action"
                        value="choose_starter"
                        class="choose-starter-btn"
                    >
                        CHOOSE <?php echo strtoupper(
                            $starter['name']
                        ); ?>

                    </button>

                </form>


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