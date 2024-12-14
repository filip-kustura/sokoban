<?php
class Sokoban {
    private $nameInputValid, $playerName, $numberOfMoves, $level;
    private $grid, $diamondsCoordinates, $playerCoordinates;
    private $gameOver, $result, $totalDiamonds;
    
    function __construct() {
        $this->playerName = false;
    }

    function run() {
        if (isset($_POST['name-input'])) {
            if ($_POST['name-input'] !== '') {
                $this->nameInputValid = true;
                $this->playerName = $_POST['name-input'];
                $this->level = $_POST['level-selection'];
                $this->initializeGame();
                $this->displayGame();
    
                return;
            } else {
                $this->nameInputValid = false;
                $this->playerName = false;
            }
        }

        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'restart-radio') {
                $this->initializeGame();
            } else if ($_POST['action'] === 'delete-diamond' && isset($_POST['diamond-selection'])) {
                $diamond_coordinates = explode('-', $_POST['diamond-selection']);
                $this->deleteDiamond($diamond_coordinates);
                $this->gameOver = $this->checkForEnding();
            }
        } else if (isset($_POST['restart-button'])) {
            $this->initializeGame();
        } else if (isset($_POST['go-to-menu-button'])) {
            $this->playerName = false;
        } else if (isset($_POST['previous-level-button'])) {
            if ($this->level > 1) --$this->level;
            $this->initializeGame();
        } else if (isset($_POST['next-level-button'])) {
            if ($this->level < 3) ++$this->level;
            $this->initializeGame();
        }

        if (!$this->playerName)
            $this->displayNameInputForm();
        
        if (isset($_POST['cell-button']))
            $direction = $_POST['cell-button'];
        else
            $direction = checkForMovement();

        if ($direction !== false) { // The player tried to move
            if ($this->isMoveValid($direction) && !$this->gameOver) {
                $this->movePlayer($direction);
                ++$this->numberOfMoves;
            }
            $this->gameOver = $this->checkForEnding();
        }

        if ($this->playerName !== false) // Player name had been set
            $this->displayGame();
    }

    function displayGame() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sokoban</title>
            <?php gameStyle(); ?>
        </head>
        <body>
            <h1>Sokoban</h1>
            <h2>Level <?php echo $this->level; ?></h2>
            <p>
                Igrač <?php echo $this->playerName; ?> je dosad napravio <?php echo $this->numberOfMoves; ?>
                <?php if ($this->numberOfMoves % 10 !== 1 || $this->numberOfMoves % 100 === 11) echo 'pomaka.'; else echo 'pomak.'; ?>
            </p>
            <div class="container">
                <div class="grid">
                    <?php $this->displayGrid(); ?>
                </div>
                <div class="actions">
                    <p>Pomakni igrača za jedno mjesto u smjeru:</p>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                        <?php $this->displayMovementButtons(); ?>
                    </form>
                    <p>(igrača je također moguće pomaknuti klikom na željeno dostupno susjedno polje)</p>

                    <p>Ili odaberi željenu akciju:</p>
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                        <input type="radio" name="action" id="restart-radio" value="restart-radio">
                        <label for="restart-radio">Pokreni sve ispočetka</label>
                        
                        <br>
                        
                        <input type="radio" name="action" id="delete-diamond" value="delete-diamond"
                        <?php if ($this->gameOver || count($this->diamondsCoordinates) <= 1) echo ' disabled'; ?>
                        >
                        
                        <label for="delete-diamond">Obriši dijamant s pozicije (red, stupac) = </label>
                        <select name="diamond-selection" id="diamond-selection"
                        <?php if ($this->gameOver || count($this->diamondsCoordinates) <= 1) echo ' disabled'; ?>
                        >
                            <?php $this->displayOptions(); ?>
                        </select>
                        
                        <p><input type="submit" name="execute-action-button" value="Izvrši akciju!"></p>
                    </form>
                </div>
            </div>
            <?php if ($this->gameOver) $this->displayGameOverMessages(); ?>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <p>
                    <?php
                    if ($this->level > 1) echo '<input type="submit" name ="previous-level-button" value="Prethodni level">';
                    if ($this->level < 3) echo '<input type="submit" name ="next-level-button" value="Sljedeći level">';
                    ?>

                    <br>

                    <p>
                        <input type="submit" name="go-to-menu-button" value="Povratak u izbornik">
                    </p>
                </p>
            </form>
        </body>
        </html>
        <?php
    }
    
    function displayOptions() {
        foreach ($this->diamondsCoordinates as $diamond_coordinates) {
            $x = $diamond_coordinates[0];
            $y = $diamond_coordinates[1];

            $value = $x . '-' . $y;
            echo '<option value="' . $value . '">' . '(' . $x . ', ' . $y . ')</option>';
        }
    }

    function displayGrid() {
        $up_cell = getFacingCellCoordinates($this->playerCoordinates, 'up');
        $right_cell = getFacingCellCoordinates($this->playerCoordinates, 'right');
        $down_cell = getFacingCellCoordinates($this->playerCoordinates, 'down');
        $left_cell = getFacingCellCoordinates($this->playerCoordinates, 'left');

        $i = 0;
        echo '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        foreach ($this->grid as $row) {
            ++$i;
            $j = 0;
            echo '<div class="row">'; // Row start
            foreach ($row as $cell) {
                ++$j;
                echo '<div class="cell';

                if ($cell === '-')
                    echo ' wall';
                else if ($cell === 'X')
                    echo ' target';
                
                echo '">';
                $cell = [$i, $j];
                
                if ($cell === $up_cell && $this->isMoveValid('up')) echo '<button type="submit" name="cell-button" value="up"><img src="black-circle.png" alt="&bull;" class="black-circle">';
                else if ($cell === $right_cell && $this->isMoveValid('right')) echo '<button type="submit" name="cell-button" value="right"><img src="black-circle.png" alt="&bull;" class="black-circle">';
                else if ($cell === $down_cell && $this->isMoveValid('down')) echo '<button type="submit" name="cell-button" value="down"><img src="black-circle.png" alt="&bull;" class="black-circle">';
                else if ($cell === $left_cell && $this->isMoveValid('left')) echo '<button type="submit" name="cell-button" value="left"><img src="black-circle.png" alt="&bull;" class="black-circle">';

                if ($this->isDiamond($cell))
                    echo '<img src="diamond-emoji.png" alt="D" class="diamond-emoji">';
                else if ([$i, $j] === $this->playerCoordinates)
                    echo '<img src="player-emoji.png" alt="X" id="player-emoji">';

                echo '</button>';

                echo '</div>';
            }
            echo '</div>'; // Row end
        }
        echo '</form>';
    }

    function isDiamond($cell) {
        return in_array($cell, $this->diamondsCoordinates);
    }

    function isMoveValid($direction) {
        $facing_cell = getFacingCellCoordinates($this->playerCoordinates, $direction);
        
        if ($this->isWall($facing_cell))
            return false; // The move is not valid (player bumping into wall)

        if ($this->isDiamond($facing_cell)) {
            $diamonds_facing_cell = getFacingCellCoordinates($facing_cell, $direction);

            if ($this->isWall($diamonds_facing_cell) || $this->isDiamond($diamonds_facing_cell))
                return false; // The move is not valid (diamond bumping into wall or into another diamond)
        }

        return true;
    }

    function movePlayer($direction) {
        $facing_cell = getFacingCellCoordinates($this->playerCoordinates, $direction);
        
        if ($this->isDiamond($facing_cell))
            $this->alterDiamondsCoordinates($facing_cell, $direction);

        $this->playerCoordinates = $facing_cell;
    }

    function isWall($cell) {
        $x_coordinate = $cell[0];
        $y_coordinate = $cell[1];

        return $this->grid[$x_coordinate - 1][$y_coordinate - 1] === '-';
    }

    function alterDiamondsCoordinates($cell, $direction) {
        $diamond_index = array_search($cell, $this->diamondsCoordinates);

        $this->diamondsCoordinates[$diamond_index] = getFacingCellCoordinates($cell, $direction);
        usort($this->diamondsCoordinates, 'customSort');
    }

    function checkForEnding() {
        foreach ($this->diamondsCoordinates as $diamond_coordinates) {
            if (!$this->isTarget($diamond_coordinates))
                return false;
        }

        return true;
    }

    function isTarget($cell) {
        $x_coordinate = $cell[0];
        $y_coordinate = $cell[1];

        return $this->grid[$x_coordinate - 1][$y_coordinate - 1] === 'X';
    }

    function deleteDiamond($diamond_coordinates) {
        $diamond_index = array_search($diamond_coordinates, $this->diamondsCoordinates);
        unset($this->diamondsCoordinates[$diamond_index]);
        --$this->result;
    }

    function displayMovementButtons() {
        ?>
        <div class="buttons-up-down">
            <input type="submit" name="up" value="Gore" class="movement-buttons"
            <?php if ($this->gameOver || !$this->isMoveValid('up')) echo ' disabled'; ?>
            >
        </div>
        <div>
            <input type="submit" name="left" value="Lijevo" class="movement-buttons"
            <?php if ($this->gameOver || !$this->isMoveValid('left')) echo ' disabled'; ?>
            >
            
            <input type="submit" name="right" value="Desno" class="movement-buttons"
            <?php if ($this->gameOver || !$this->isMoveValid('right')) echo ' disabled'; ?>
            >
        </div>
        <div class="buttons-up-down">
            <input type="submit" name="down" value="Dolje" class="movement-buttons"
            <?php if ($this->gameOver || !$this->isMoveValid('down')) echo ' disabled'; ?>
            >
        </div>
        <?php                        
    }

    function displayGameOverMessages() {
        echo '<h1>Igra je gotova!</h1>';
        echo '<h4>Broj pomaka: ' . $this->numberOfMoves;
        echo '<br>Rezultat: ' . $this->result . '/' . $this->totalDiamonds . ' dijamanata</h4>';
        if ($this->result < $this->totalDiamonds) {
            echo '<h4>Možeš li postići ' . $this->totalDiamonds . '/' . $this->totalDiamonds . '?';
            echo ' <img src="winking-emoji.png" alt =";)" id="winking-emoji">';
            echo '</h4>';
        } else {
            echo '<h1>Čestitke!</h1>';
        }
        ?>
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <input type="submit" name="restart-button" value="Ponovi">
        </form>
        <?php
    }

    function getGrid() {
        switch ($this->level) {
            // '-' - wall; 'O' - passable; 'X' - target
            case 1:
                return [['O', 'O', '-', '-', '-', '-', '-', 'O'],
                        ['-', '-', '-', 'O', 'O', 'O', '-', 'O'],
                        ['-', 'X', 'O', 'O', 'O', 'O', '-', 'O'],
                        ['-', '-', '-', 'O', 'O', 'X', '-', 'O'],
                        ['-', 'X', '-', '-', 'O', 'O', '-', 'O'],
                        ['-', 'O', '-', 'O', 'X', 'O', '-', '-'],
                        ['-', 'O', 'O', 'X', 'O', 'O', 'X', '-'],
                        ['-', 'O', 'O', 'O', 'X', 'O', 'O', '-'],
                        ['-', '-', '-', '-', '-', '-', '-', '-']];
            case 2:
                return [['O', 'O', 'O', 'O', '-', '-', '-', '-', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'],
                        ['O', 'O', 'O', 'O', '-', 'O', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'],
                        ['O', 'O', 'O', 'O', '-', 'O', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'],
                        ['O', 'O', '-', '-', '-', 'O', 'O', 'O', '-', '-', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'],
                        ['O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'],
                        ['-', '-', '-', 'O', '-', 'O', '-', '-', '-', 'O', '-', 'O', 'O', 'O', 'O', 'O', '-', '-', '-', '-', '-', '-'],
                        ['-', 'O', 'O', 'O', '-', 'O', '-', '-', '-', 'O', '-', '-', '-', '-', '-', '-', '-', 'O', 'O', 'X', 'X', '-'],
                        ['-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'X', 'X', '-'],
                        ['-', '-', '-', '-', '-', 'O', '-', '-', '-', '-', 'O', '-', 'O', '-', '-', '-', '-', 'O', 'O', 'X', 'X', '-'],
                        ['O', 'O', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', '-', '-', '-', 'O', 'O', '-', '-', '-', '-', '-', '-'],
                        ['O', 'O', 'O', 'O', '-', '-', '-', '-', '-', '-', '-', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O']];
            case 3:
                return [['-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', 'O', 'O'],
                        ['-', 'X', 'X', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', '-', '-', '-'],
                        ['-', 'X', 'X', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', '-'],
                        ['-', 'X', 'X', 'O', 'O', '-', 'O', '-', '-', '-', '-', 'O', 'O', '-'],
                        ['-', 'X', 'X', 'O', 'O', 'O', 'O', 'O', 'O', '-', '-', 'O', 'O', '-'],
                        ['-', 'X', 'X', 'O', 'O', '-', 'O', '-', 'O', 'O', 'O', 'O', '-', '-'],
                        ['-', '-', '-', '-', '-', '-', 'O', '-', '-', 'O', 'O', 'O', 'O', '-'],
                        ['O', 'O', '-', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', '-'],
                        ['O', 'O', '-', 'O', 'O', 'O', 'O', '-', 'O', 'O', 'O', 'O', 'O', '-'],
                        ['O', 'O', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-']];
        }
    }

    function getDiamondsStartingCoordinates() {
        switch ($this->level) {
            case 1:
                return [[3, 4],
                        [4, 5],
                        [5, 5],
                        [7, 2],
                        [7, 4],
                        [7, 5],
                        [7, 6]];
            case 2:
                return [[3, 6],
                        [4, 8],
                        [5, 6],
                        [5, 9],
                        [8, 3],
                        [8, 6]];
            case 3:
                return [[3, 8],
                        [3, 11],
                        [4, 7],
                        [6, 11],
                        [7, 10],
                        [7, 12],
                        [8, 5],
                        [8, 8],
                        [8, 10],
                        [8, 12]];
        }
    }

    function getPlayerStartingCoordinates() {
        switch ($this->level) {
            case 1:
                return [3, 3];
            case 2:
                return [9, 13];
            case 3:
                return [5, 8];
        }
    }       

    function initializeGame() {
        $this->numberOfMoves = 0;
        $this->grid = $this->getGrid();
        $this->diamondsCoordinates = $this->getDiamondsStartingCoordinates();
        $this->playerCoordinates = $this->getPlayerStartingCoordinates();
        $this->gameOver = false;
        $this->totalDiamonds = count($this->diamondsCoordinates);
        $this->result = $this->totalDiamonds;
    }

    function displayNameInputForm() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Sokoban - Unos imena</title>
            <style>
                body {
                    font-family: Calibri, sans-serif;
                }
                <?php gameStyle(); ?>
            </style>
        </head>
        <body>
            <h1>Sokoban</h1>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <?php if ($this->nameInputValid === false)
                        echo '<p id="warning">Potrebno je unijeti ime.</p>'; ?>
    
                <p>
                    <label for="name-input">Unesi ime igrača:</label>
                    <input type="text" name="name-input" id="name-input">                
                </p>
    
                <p>
                    <label for="level-selection">Odaberi level:</label>
                    <select name="level-selection" id="level-selection">
                        <option value="1">Level 1</option>
                        <option value="2">Level 2</option>
                        <option value="3">Level 3</option>
                    </select>
                </p>
                
                <p>
                    <input type="submit" name="start-button" value="Započni igru!">
                </p>
            </form>
        </body>
        </html>
        <?php
    }
}

function gameStyle() {
    ?>
    <style>
        body {
            font-family: Calibri, sans-serif;
        }

        .container {
            display: flex;
        }
    
        .grid {
            display: flex;
            flex-direction: column;
            border: 1px solid;
            background-color: white;
            align-self: flex-start;
        }
    
        .row {
            display: flex;
        }
    
        .cell {
            width: 30px;
            height: 30px;
            border: 1px solid;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
    
        .wall {
            background-color: blue;
        }
    
        .target {
            background-color: yellow;
        }
    
        .actions {
            width: 400px;
            margin-left: 20px;
            align-self: flex-start;
        }
    
        .diamond-emoji {
            max-width: 70%;
            max-height: 70%;
        }

        .movement-buttons {
            width: 60px;
        }

        .buttons-up-down {
            margin-left: 30px;
            margin-bottom: 6px;
            margin-top: 6px;
        }

        #player-emoji {
            max-width: 75%;
            max-height: 75%;
        }

        #winking-emoji {
            width: 23px;
            height: auto;
            vertical-align: middle;
        }

        button {
            width: 100%;
            height: 100%;
            border: none;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0px;
            background-color: transparent;
        }

        .black-circle {
            max-width: 15%;
            max-height: 15%;
        }

        button img:first-child{
            z-index: 1;
        }

        img {
            position: absolute;
        }

        #warning {
            color: red;
        }
    </style>
    <?php
}

function checkForMovement() {
    if (isset($_POST['up'])) return 'up';
    else if (isset($_POST['right'])) return 'right';
    else if (isset($_POST['down'])) return 'down';
    else if (isset($_POST['left'])) return 'left';
    else return false;

}

function getFacingCellCoordinates($origin_cell, $direction) {
    $target_x_coordinate = $origin_cell[0];
    $target_y_coordinate = $origin_cell[1];

    switch($direction) {
        case 'up':
            --$target_x_coordinate;
            break;
        case 'right':
            ++$target_y_coordinate;
            break;
        case 'down':
            ++$target_x_coordinate;
            break;
        case 'left':
            --$target_y_coordinate;
    }

    return [$target_x_coordinate, $target_y_coordinate];
}

function customSort($a, $b) {
    if ($a[0] === $b[0]) {
        return $a[1] - $b[1];
    }
    return $a[0] - $b[0];
}

session_start();

if (isset($_SESSION['game'])) {
    $game = $_SESSION['game'];
} else {
    $game = new Sokoban();
    $_SESSION['game'] = $game;
}

$game->run();
?>