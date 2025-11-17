<?php
session_start();

class BlockBlast {
    const ROWS = 10;
    const COLS = 10;
    
    private $pieces = [
        [[1]],
        [[1, 1]],
        [[1], [1]],
        [[1, 1, 1]],
        [[1], [1], [1]],
        [[1, 1], [1, 1]],
        [[1, 1, 1], [1, 1, 1]],
        [[1, 1, 1], [1, 1, 1], [1, 1, 1]],
        [[1, 0], [1, 1]],
        [[0, 1], [1, 1]],
        [[1, 1], [1, 0]],
        [[1, 1], [0, 1]],
        [[1, 1, 1], [0, 1, 0]],
        [[0, 1, 0], [1, 1, 1]],
        [[1, 0, 0], [1, 1, 1]],
        [[0, 0, 1], [1, 1, 1]],
        [[1, 1, 1], [1, 0, 0]],
        [[1, 1, 1], [0, 0, 1]],
        [[1, 1], [1, 1], [1, 1]],
        [[1, 1, 1, 1]],
        [[1], [1], [1], [1]],
        [[1, 0], [1, 0], [1, 1]],
        [[0, 1], [0, 1], [1, 1]],
        [[1, 1], [0, 1], [0, 1]],
        [[1, 1], [1, 0], [1, 0]],
        [[1, 1, 0], [0, 1, 1]],
        [[0, 1, 1], [1, 1, 0]],
        [[1, 0], [1, 1], [0, 1]],
        [[0, 1], [1, 1], [1, 0]],
        [[1, 1, 1], [1, 0, 1]],
        [[1, 0, 1], [1, 1, 1]],
    ];
    
    private $colors = [
        '#00f0f0', '#f0f000', '#a000f0', '#00f000', 
        '#f00000', '#0000f0', '#f0a000', '#ff69b4',
        '#00ff00', '#ff1493', '#1e90ff', '#ffa500'
    ];
    
    public function __construct() {
        if (!isset($_SESSION['game'])) {
            $this->resetGame();
        }
    }
    
    public function resetGame() {
        $_SESSION['game'] = [
            'grid' => $this->createEmptyGrid(),
            'currentPieces' => $this->generateNewPieces(),
            'score' => 0,
            'gameOver' => false
        ];
    }
    
    private function createEmptyGrid() {
        return array_fill(0, self::ROWS, array_fill(0, self::COLS, null));
    }
    
    private function generateNewPieces() {
        $pieces = [];
        for ($i = 0; $i < 3; $i++) {
            $shape = $this->pieces[array_rand($this->pieces)];
            $color = $this->colors[array_rand($this->colors)];
            $pieces[] = [
                'shape' => $shape,
                'color' => $color,
                'used' => false
            ];
        }
        return $pieces;
    }
    
    public function canPlacePiece($shape, $x, $y) {
        $grid = $_SESSION['game']['grid'];
        
        foreach ($shape as $dy => $row) {
            foreach ($row as $dx => $cell) {
                if ($cell) {
                    $newX = $x + $dx;
                    $newY = $y + $dy;
                    
                    if ($newX < 0 || $newX >= self::COLS || $newY < 0 || $newY >= self::ROWS) {
                        return false;
                    }
                    
                    if ($grid[$newY][$newX] !== null) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    public function placePiece($pieceIndex, $x, $y) {
        $piece = $_SESSION['game']['currentPieces'][$pieceIndex];
        
        if ($piece['used'] || !$this->canPlacePiece($piece['shape'], $x, $y)) {
            return false;
        }
        
        $grid = &$_SESSION['game']['grid'];
        
        foreach ($piece['shape'] as $dy => $row) {
            foreach ($row as $dx => $cell) {
                if ($cell) {
                    $grid[$y + $dy][$x + $dx] = $piece['color'];
                }
            }
        }
        
        $_SESSION['game']['currentPieces'][$pieceIndex]['used'] = true;
        
        $linesCleared = $this->clearLines();
        
        if ($this->allPiecesUsed()) {
            $_SESSION['game']['currentPieces'] = $this->generateNewPieces();
        }
        
        if ($this->checkGameOver()) {
            $_SESSION['game']['gameOver'] = true;
        }
        
        return true;
    }
    
    private function clearLines() {
        $grid = &$_SESSION['game']['grid'];
        $rowsToClear = [];
        $colsToClear = [];
        
        for ($y = 0; $y < self::ROWS; $y++) {
            $full = true;
            foreach ($grid[$y] as $cell) {
                if ($cell === null) {
                    $full = false;
                    break;
                }
            }
            if ($full) {
                $rowsToClear[] = $y;
            }
        }
        
        for ($x = 0; $x < self::COLS; $x++) {
            $full = true;
            for ($y = 0; $y < self::ROWS; $y++) {
                if ($grid[$y][$x] === null) {
                    $full = false;
                    break;
                }
            }
            if ($full) {
                $colsToClear[] = $x;
            }
        }
        
        foreach ($rowsToClear as $y) {
            $grid[$y] = array_fill(0, self::COLS, null);
        }
        
        foreach ($colsToClear as $x) {
            for ($y = 0; $y < self::ROWS; $y++) {
                $grid[$y][$x] = null;
            }
        }
        
        $cleared = count($rowsToClear) + count($colsToClear);
        
        if ($cleared > 0) {
            $_SESSION['game']['score'] += $cleared * 100;
        }
        
        return $cleared;
    }
    
    private function allPiecesUsed() {
        foreach ($_SESSION['game']['currentPieces'] as $piece) {
            if (!$piece['used']) {
                return false;
            }
        }
        return true;
    }
    
    private function checkGameOver() {
        $grid = $_SESSION['game']['grid'];
        
        foreach ($_SESSION['game']['currentPieces'] as $piece) {
            if (!$piece['used']) {
                for ($y = 0; $y < self::ROWS; $y++) {
                    for ($x = 0; $x < self::COLS; $x++) {
                        if ($this->canPlacePiece($piece['shape'], $x, $y)) {
                            return false;
                        }
                    }
                }
            }
        }
        
        return !$this->allPiecesUsed();
    }
    
    public function getState() {
        return $_SESSION['game'];
    }
    
    public function getPreview($pieceIndex, $x, $y) {
        $piece = $_SESSION['game']['currentPieces'][$pieceIndex];
        
        if ($piece['used']) {
            return null;
        }
        
        if (!$this->canPlacePiece($piece['shape'], $x, $y)) {
            return null;
        }
        
        return [
            'shape' => $piece['shape'],
            'color' => $piece['color'],
            'x' => $x,
            'y' => $y
        ];
    }
}

$game = new BlockBlast();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'place':
            if (!isset($data['pieceIndex']) || !isset($data['x']) || !isset($data['y'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            $pieceIndex = filter_var($data['pieceIndex'], FILTER_VALIDATE_INT);
            $x = filter_var($data['x'], FILTER_VALIDATE_INT);
            $y = filter_var($data['y'], FILTER_VALIDATE_INT);
            
            if ($pieceIndex === false || $x === false || $y === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data types']);
                exit;
            }
            
            if ($pieceIndex < 0 || $pieceIndex > 2) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid piece index']);
                exit;
            }
            
            if ($x < 0 || $x >= BlockBlast::COLS || $y < 0 || $y >= BlockBlast::ROWS) {
                http_response_code(400);
                echo json_encode(['error' => 'Coordinates out of bounds']);
                exit;
            }
            
            $result = $game->placePiece($pieceIndex, $x, $y);
            echo json_encode(['success' => $result, 'state' => $game->getState()]);
            break;
            
        case 'preview':
            if (!isset($data['pieceIndex']) || !isset($data['x']) || !isset($data['y'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            $pieceIndex = filter_var($data['pieceIndex'], FILTER_VALIDATE_INT);
            $x = filter_var($data['x'], FILTER_VALIDATE_INT);
            $y = filter_var($data['y'], FILTER_VALIDATE_INT);
            
            if ($pieceIndex === false || $x === false || $y === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data types']);
                exit;
            }
            
            if ($pieceIndex < 0 || $pieceIndex > 2) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid piece index']);
                exit;
            }
            
            if ($x < 0 || $x >= BlockBlast::COLS || $y < 0 || $y >= BlockBlast::ROWS) {
                echo json_encode(['preview' => null]);
                exit;
            }
            
            $preview = $game->getPreview($pieceIndex, $x, $y);
            echo json_encode(['preview' => $preview]);
            break;
            
        case 'canPlace':
            if (!isset($data['pieceIndex']) || !isset($data['x']) || !isset($data['y'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                exit;
            }
            
            $pieceIndex = filter_var($data['pieceIndex'], FILTER_VALIDATE_INT);
            $x = filter_var($data['x'], FILTER_VALIDATE_INT);
            $y = filter_var($data['y'], FILTER_VALIDATE_INT);
            
            if ($pieceIndex === false || $x === false || $y === false) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data types']);
                exit;
            }
            
            if ($pieceIndex < 0 || $pieceIndex > 2) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid piece index']);
                exit;
            }
            
            if ($x < 0 || $x >= BlockBlast::COLS || $y < 0 || $y >= BlockBlast::ROWS) {
                echo json_encode(['canPlace' => false]);
                exit;
            }
            
            $piece = $game->getState()['currentPieces'][$pieceIndex];
            $canPlace = !$piece['used'] && $game->canPlacePiece($piece['shape'], $x, $y);
            echo json_encode(['canPlace' => $canPlace]);
            break;
            
        case 'reset':
            $game->resetGame();
            echo json_encode(['state' => $game->getState()]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
    exit;
}

$state = $game->getState();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block Blast</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .game-container {
            text-align: center;
            max-width: 700px;
            width: 100%;
        }
        
        h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 8px;
            background: linear-gradient(90deg, #00d9ff, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .game-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .score {
            font-size: 1.5rem;
            font-weight: bold;
            color: #00d9ff;
        }
        
        .grid-container {
            display: inline-block;
            position: relative;
        }
        
        .grid {
            display: inline-block;
            background: #1a1f3a;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5),
                        inset 0 0 20px rgba(0, 217, 255, 0.2);
            margin-bottom: 20px;
            cursor: pointer;
        }
        
        .grid-row {
            display: flex;
        }
        
        .cell {
            width: 40px;
            height: 40px;
            border: 1px solid #2a3f5f;
            background: rgba(255, 255, 255, 0.02);
            transition: all 0.1s ease;
        }
        
        .cell.filled {
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: inset 0 0 5px rgba(255, 255, 255, 0.3),
                        0 0 10px rgba(0, 0, 0, 0.5);
        }
        
        .cell.preview {
            opacity: 0.5;
            border: 2px solid #ffffff;
        }
        
        .pieces-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .piece-holder {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .piece-holder:hover:not(.used) {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.1);
        }
        
        .piece-holder.selected {
            border: 3px solid #00d9ff;
            box-shadow: 0 0 20px rgba(0, 217, 255, 0.6);
        }
        
        .piece-holder.used {
            opacity: 0.3;
            cursor: not-allowed;
        }
        
        .piece-grid {
            display: inline-block;
        }
        
        .piece-row {
            display: flex;
        }
        
        .piece-cell {
            width: 25px;
            height: 25px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .piece-cell.filled {
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: inset 0 0 3px rgba(255, 255, 255, 0.3);
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(10, 14, 39, 0.95);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid #00d9ff;
            box-shadow: 0 0 50px rgba(0, 217, 255, 0.5);
            z-index: 1000;
        }
        
        .overlay.show {
            display: block;
        }
        
        .overlay h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #00d9ff;
        }
        
        button {
            background: linear-gradient(135deg, #00d9ff, #ff00ff);
            color: #ffffff;
            border: none;
            padding: 15px 30px;
            font-size: 1rem;
            border-radius: 50px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(0, 217, 255, 0.4);
            margin: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0, 217, 255, 0.6);
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
                letter-spacing: 4px;
            }
            
            .cell {
                width: 30px;
                height: 30px;
            }
            
            .piece-cell {
                width: 20px;
                height: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .cell {
                width: 25px;
                height: 25px;
            }
            
            .piece-cell {
                width: 15px;
                height: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <h1>BLOCK BLAST</h1>
        
        <div class="game-info">
            <div>Score: <span class="score" id="score"><?php echo $state['score']; ?></span></div>
        </div>
        
        <div class="grid-container">
            <div class="grid" id="grid">
                <?php
                foreach ($state['grid'] as $y => $row) {
                    echo '<div class="grid-row" data-y="' . $y . '">';
                    foreach ($row as $x => $cell) {
                        $color = $cell ?? 'transparent';
                        $filled = $cell ? 'filled' : '';
                        echo "<div class='cell $filled' data-x='$x' data-y='$y' style='background-color: $color'></div>";
                    }
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="pieces-container" id="pieces">
            <?php foreach ($state['currentPieces'] as $index => $piece): ?>
                <div class="piece-holder <?php echo $piece['used'] ? 'used' : ''; ?>" 
                     data-piece="<?php echo $index; ?>"
                     onclick="selectPiece(<?php echo $index; ?>)">
                    <div class="piece-grid">
                        <?php foreach ($piece['shape'] as $row): ?>
                            <div class="piece-row">
                                <?php foreach ($row as $cell): ?>
                                    <div class="piece-cell <?php echo $cell ? 'filled' : ''; ?>" 
                                         style="background-color: <?php echo $cell ? $piece['color'] : 'transparent'; ?>"></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="overlay" id="gameOver">
            <h2>Game Over!</h2>
            <p>Final Score: <span id="finalScore"></span></p>
            <button onclick="resetGame()">Play Again</button>
        </div>
    </div>
    
    <script>
        let selectedPiece = null;
        let gameOver = <?php echo $state['gameOver'] ? 'true' : 'false'; ?>;
        
        if (gameOver) {
            document.getElementById('finalScore').textContent = <?php echo $state['score']; ?>;
            document.getElementById('gameOver').classList.add('show');
        }
        
        function selectPiece(index) {
            const holder = document.querySelector(`.piece-holder[data-piece="${index}"]`);
            if (holder.classList.contains('used')) return;
            
            document.querySelectorAll('.piece-holder').forEach(p => p.classList.remove('selected'));
            holder.classList.add('selected');
            selectedPiece = index;
        }
        
        document.getElementById('grid').addEventListener('click', async (e) => {
            if (!e.target.classList.contains('cell') || selectedPiece === null || gameOver) return;
            
            const x = parseInt(e.target.dataset.x);
            const y = parseInt(e.target.dataset.y);
            
            const response = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'place',
                    pieceIndex: selectedPiece,
                    x: x,
                    y: y
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload();
            }
        });
        
        document.getElementById('grid').addEventListener('mouseover', async (e) => {
            if (!e.target.classList.contains('cell') || selectedPiece === null || gameOver) return;
            
            const x = parseInt(e.target.dataset.x);
            const y = parseInt(e.target.dataset.y);
            
            const response = await fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'preview',
                    pieceIndex: selectedPiece,
                    x: x,
                    y: y
                })
            });
            
            const result = await response.json();
            
            document.querySelectorAll('.cell.preview').forEach(c => {
                c.classList.remove('preview');
                if (!c.classList.contains('filled')) {
                    c.style.backgroundColor = 'transparent';
                }
            });
            
            if (result.preview) {
                result.preview.shape.forEach((row, dy) => {
                    row.forEach((cell, dx) => {
                        if (cell) {
                            const cellEl = document.querySelector(`.cell[data-x="${x + dx}"][data-y="${y + dy}"]`);
                            if (cellEl && !cellEl.classList.contains('filled')) {
                                cellEl.classList.add('preview');
                                cellEl.style.backgroundColor = result.preview.color;
                            }
                        }
                    });
                });
            }
        });
        
        document.getElementById('grid').addEventListener('mouseleave', () => {
            document.querySelectorAll('.cell.preview').forEach(c => {
                c.classList.remove('preview');
                if (!c.classList.contains('filled')) {
                    c.style.backgroundColor = 'transparent';
                }
            });
        });
        
        function resetGame() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset' })
            }).then(() => location.reload());
        }
    </script>
</body>
</html>
