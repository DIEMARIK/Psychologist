<?php
session_start();

// Инициализация игры
if (!isset($_SESSION['puzzle'])) {
    resetGame();
}

// Обработка хода
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move'])) {
    $tile = (int)$_POST['move'];
    moveTile($tile);
}

// Сброс игры
if (isset($_GET['reset'])) {
    resetGame();
    header('Location: ' . str_replace('?reset=1', '', $_SERVER['REQUEST_URI']));
    exit;
}

// Функция сброса игры
function resetGame() {
    $_SESSION['puzzle'] = range(1, 15);
    $_SESSION['puzzle'][] = 0; // 0 представляет пустую клетку
    shuffle($_SESSION['puzzle']);
    
    // Проверяем, что головоломка решаема
    while (!isSolvable($_SESSION['puzzle'])) {
        shuffle($_SESSION['puzzle']);
    }
    
    $_SESSION['moves'] = 0;
}

// Проверка, можно ли решить головоломку
function isSolvable($puzzle) {
    $inversions = 0;
    $emptyRow = 0;
    
    for ($i = 0; $i < 16; $i++) {
        if ($puzzle[$i] === 0) {
            $emptyRow = floor($i / 4);
            continue;
        }
        
        for ($j = $i + 1; $j < 16; $j++) {
            if ($puzzle[$j] !== 0 && $puzzle[$i] > $puzzle[$j]) {
                $inversions++;
            }
        }
    }
    
    return ($inversions % 2 === 0) === ($emptyRow % 2 === 1);
}

// Функция перемещения плитки
function moveTile($tile) {
    $puzzle = &$_SESSION['puzzle'];
    $emptyPos = array_search(0, $puzzle);
    $tilePos = array_search($tile, $puzzle);
    
    // Проверяем, можно ли переместить плитку
    if (isAdjacent($emptyPos, $tilePos)) {
        // Меняем местами пустую клетку и плитку
        $puzzle[$emptyPos] = $tile;
        $puzzle[$tilePos] = 0;
        $_SESSION['moves']++;
        
        // Проверяем, решена ли головоломка
        if (isSolved($puzzle)) {
            $_SESSION['message'] = "Поздравляем! Вы решили головоломку за " . $_SESSION['moves'] . " ходов!";
        }
    }
}

// Проверка, являются ли позиции соседними
function isAdjacent($pos1, $pos2) {
     // Вычисляем координаты в сетке 4x4
     $row1 = intdiv($pos1, 4);
     $col1 = $pos1 % 4;
     $row2 = intdiv($pos2, 4);
     $col2 = $pos2 % 4;
 
     // Соседство по горизонтали или вертикали (не по диагонали)
     return (abs($row1 - $row2) === 1 && $col1 === $col2) || 
            (abs($col1 - $col2) === 1 && $row1 === $row2);
}

// Проверка, решена ли головоломка
function isSolved($puzzle) {
    for ($i = 0; $i < 15; $i++) {
        if ($puzzle[$i] !== $i + 1) {
            return false;
        }
    }
    return $puzzle[15] === 0;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Игра "Пятнашки"</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .puzzle-container {
            display: grid;
            grid-template-columns: repeat(4, 80px);
            grid-template-rows: repeat(4, 80px);
            gap: 5px;
            margin-bottom: 20px;
        }
        
        .tile {
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #4CAF50;
            color: white;
            font-size: 24px;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .tile:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }
        
        .empty {
            background-color: #ddd;
            box-shadow: none;
            cursor: default;
        }
        
        .empty:hover {
            background-color: #ddd;
            transform: none;
        }
        
        .controls {
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: #008CBA;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #007B9E;
        }
        
        .stats {
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .message {
            padding: 10px;
            background-color: #FFD700;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav>
<a href="index.php">Вернуться на главную</a>
    </nav>
    <h1>Пятнашки</h1>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message"><?= $_SESSION['message'] ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <div class="stats">
        Ходов: <?= $_SESSION['moves'] ?>
    </div>
    
    <form method="post" class="puzzle-container">
        <?php for ($i = 0; $i < 16; $i++): ?>
            <?php $tile = $_SESSION['puzzle'][$i]; ?>
            <?php if ($tile !== 0): ?>
                <button type="submit" name="move" value="<?= $tile ?>" class="tile">
                    <?= $tile ?>
                </button>
            <?php else: ?>
                <div class="tile empty"></div>
            <?php endif; ?>
        <?php endfor; ?>
    </form>
    
    <div class="controls">
        <a href="?reset=1" class="btn">Новая игра</a>
    </div>
    
    <div>
        <h3>Как играть:</h3>
        <p>Цель игры - упорядочить плитки по номерам от 1 до 15 слева направо и сверху вниз.</p>
        <p>Нажимайте на плитки, соседние с пустой клеткой, чтобы переместить их.</p>
    </div>
</body>
</html>