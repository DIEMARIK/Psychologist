<?php
session_start();
require "db.php";

// Функция для проверки мата
function containsBadWords($text) {
    $badWords = ['плохое слово', 'ругательство', 'мат']; // сюда добавляешь свои запрещённые слова
    foreach ($badWords as $word) {
        if (stripos($text, $word) !== false) {
            return true;
        }
    }
    return false;
}
function recalculateClientStats($pdo) {
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM reviews WHERE rating >= 4");
    $total = $stmt->fetchColumn();
    $pdo->prepare("UPDATE client_stats SET total_clients = ?")->execute([$total]);
}
// --- Обработка нового отзыва ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $comment = htmlspecialchars(trim($_POST['comment']));
    $rating = (int)$_POST['rating'];

    if ($user_id && !empty($comment) && $rating > 0) {
        if (!containsBadWords($comment)) {
            // Начинаем транзакцию
            $pdo->beginTransaction();
            
            try {
                // Добавляем отзыв
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, comment, rating) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $comment, $rating]);
                
                // ВСТАВЛЯЕМ КОД ЗДЕСЬ - начало
                // Проверяем, есть ли у пользователя другие положительные отзывы
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND rating >= 4");
                $stmt->execute([$user_id]);
                recalculateClientStats($pdo);
                // ВСТАВЛЯЕМ КОД ЗДЕСЬ - конец
                
                $pdo->commit();
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Произошла ошибка при сохранении отзыва.";
            }
        } else {
            $error = "Ваш комментарий содержит недопустимые выражения.";
        }
    }
}



// --- Обработка голосования за отзыв ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vote'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $review_id = (int)$_POST['review_id'];
    $vote_type = $_POST['vote_type'];

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM review_votes WHERE user_id = ? AND review_id = ?");
        $stmt->execute([$user_id, $review_id]);
        $existingVote = $stmt->fetch();

        if ($existingVote) {
            if ($existingVote['vote_type'] != $vote_type) {
                $pdo->beginTransaction();
                $oldColumn = ($existingVote['vote_type'] == 'like') ? 'likes' : 'dislikes';
                $newColumn = ($vote_type == 'like') ? 'likes' : 'dislikes';

                $pdo->prepare("UPDATE reviews SET $oldColumn = $oldColumn - 1 WHERE id = ?")->execute([$review_id]);
                $pdo->prepare("UPDATE review_votes SET vote_type = ? WHERE user_id = ? AND review_id = ?")->execute([$vote_type, $user_id, $review_id]);
                $pdo->prepare("UPDATE reviews SET $newColumn = $newColumn + 1 WHERE id = ?")->execute([$review_id]);
                $pdo->commit();
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO review_votes (user_id, review_id, vote_type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $review_id, $vote_type]);
            $column = ($vote_type == 'like') ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE reviews SET $column = $column + 1 WHERE id = ?")->execute([$review_id]);
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

// --- Обработка комментариев к отзывам ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_comment'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $review_id = (int)$_POST['review_id'];
    $comment_text = htmlspecialchars(trim($_POST['comment_text']));

    if ($user_id && !empty($comment_text)) {
        if (!containsBadWords($comment_text)) {
            $stmt = $pdo->prepare("INSERT INTO review_comments (user_id, review_id, comment_text) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $review_id, $comment_text]);
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            $error = "Комментарий содержит запрещённые слова.";
        }
    }
}

// --- Обработка лайков/дислайков на комментарии ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vote_comment'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $comment_id = (int)$_POST['comment_id'];
    $vote_type = $_POST['vote_type'];

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM comment_votes WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$user_id, $comment_id]);
        $existingVote = $stmt->fetch();

        if ($existingVote) {
            if ($existingVote['vote_type'] != $vote_type) {
                $pdo->beginTransaction();
                $oldColumn = ($existingVote['vote_type'] == 'like') ? 'likes' : 'dislikes';
                $newColumn = ($vote_type == 'like') ? 'likes' : 'dislikes';

                $pdo->prepare("UPDATE review_comments SET $oldColumn = $oldColumn - 1 WHERE id = ?")->execute([$comment_id]);
                $pdo->prepare("UPDATE comment_votes SET vote_type = ? WHERE user_id = ? AND comment_id = ?")->execute([$vote_type, $user_id, $comment_id]);
                $pdo->prepare("UPDATE review_comments SET $newColumn = $newColumn + 1 WHERE id = ?")->execute([$comment_id]);
                $pdo->commit();
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO comment_votes (user_id, comment_id, vote_type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $comment_id, $vote_type]);
            $column = ($vote_type == 'like') ? 'likes' : 'dislikes';
            $pdo->prepare("UPDATE review_comments SET $column = $column + 1 WHERE id = ?")->execute([$comment_id]);
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}
// --- Обработка удаления отзыва ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_review'])) {
    $user_id = $_SESSION['user_id'] ?? null;
    $review_id = (int)$_POST['review_id'];
    $rating = (int)$_POST['rating'];

    if ($user_id) {
        // Проверяем права пользователя
        $stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $review_owner = $stmt->fetchColumn();

        if ($review_owner == $user_id || $_SESSION['is_admin']) {
            // Начинаем транзакцию
            $pdo->beginTransaction();
            
            try {
                // Удаляем отзыв
                $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$review_id]);
                
                // Если отзыв был положительный (4-5 звезд), уменьшаем счетчик
                if ($rating >= 4) {
                    // Проверяем, есть ли у пользователя другие положительные отзывы
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND rating >= 4");
                    $stmt->execute([$review_owner]);
                    if ($stmt->fetchColumn() == 0) { // Если больше нет положительных отзывов
                        $pdo->prepare("UPDATE client_stats SET total_clients = GREATEST(total_clients - 1, 0)")->execute();
                    }
                }
                
                // Удаляем связанные данные
                $pdo->prepare("DELETE FROM review_votes WHERE review_id = ?")->execute([$review_id]);
                $pdo->prepare("DELETE FROM review_comments WHERE review_id = ?")->execute([$review_id]);
                
                $pdo->commit();
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Произошла ошибка при удалении отзыва.";
            }
        }
    }
}


// --- Вывод отзывов и комментариев ---
$reviews = $pdo->query("
    SELECT r.*, u.username
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll();


foreach ($reviews as &$review) {
    $stmt = $pdo->prepare("
        SELECT rc.*, u.username 
        FROM review_comments rc
        JOIN users u ON rc.user_id = u.id
        WHERE rc.review_id = ?
        ORDER BY rc.created_at ASC
    ");
    $stmt->execute([$review['id']]);
    $review['comments'] = $stmt->fetchAll();
}
unset($review);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы и комментарии</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        nav {
            width: 250px;
            background-color: #f0f0f0;
            padding: 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        nav a {
            display: block;
            padding: 10px;
            margin-bottom: 5px;
            background-color: lightblue;
            text-decoration: none;
            color: black;
            border-radius: 4px;
        }
        nav a:hover {
            background-color: darkblue;
            color: white;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            nav {
                width: 100%;
                position: relative;
                height: auto;
            }
            .main-content {
                margin-left: 0;
            }
        }
        .review-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: white;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .review-author {
            font-weight: bold;
        }
        .review-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        .review-rating {
            color: #ffc107;
            margin-bottom: 10px;
        }
        .vote-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .vote-count {
            font-weight: bold;
        }
        .comments-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .comment {
            padding: 10px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .comment-form {
            margin-top: 15px;
        }
        .star-rating {
            direction: rtl;
            unicode-bidi: bidi-override;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            font-size: 1.5em;
            cursor: pointer;
        }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <nav>
        <h2>Меню</h2>
        <a href="index.php">Главная</a>
        <a href="Контакты.php">Контакты</a>
        <a href="Комментарии.php">Отзывы и комментарии</a>
    </nav>

    <div class="main-content">
        <h1 class="mb-4">Отзывы и комментарии</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Оставить отзыв</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Ваша оценка:</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="5 stars">★</label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 stars">★</label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 stars">★</label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 stars">★</label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 star">★</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="comment" class="form-label">Ваш отзыв:</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-primary">Отправить отзыв</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Чтобы оставить отзыв, пожалуйста, <a href="login.php">войдите</a> или <a href="register.php">зарегистрируйтесь</a>.
            </div>
        <?php endif; ?>
        
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <span class="review-author"><?= htmlspecialchars($review['username']) ?></span>
                        <span class="review-date"><?= date('d.m.Y H:i', strtotime($review['created_at'])) ?></span>
                    </div>
                    <div class="review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $review['rating']): ?>
                                ★
                            <?php else: ?>
                                ☆
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <p><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                    
                    <div class="vote-buttons">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <input type="hidden" name="vote_type" value="like">
                            <button type="submit" name="vote" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-thumbs-up"></i> <span class="vote-count"><?= $review['likes'] ?></span>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                            <input type="hidden" name="vote_type" value="dislike">
                            <button type="submit" name="vote" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-thumbs-down"></i> <span class="vote-count"><?= $review['dislikes'] ?></span>
                            </button>
                        </form>
                    </div>
                    
                    <div class="comments-section">
                        <h6>Комментарии:</h6>
                        <?php foreach ($review['comments'] as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <span class="comment-author"><?= htmlspecialchars($comment['username']) ?></span>
                                    <span class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <p><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $review['user_id'] || $_SESSION['is_admin'])): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Вы уверены, что хотите удалить этот отзыв?');">
                         <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                         <input type="hidden" name="rating" value="<?= $review['rating'] ?>">
                         <button type="submit" name="delete_review" class="btn btn-sm btn-outline-danger">
                           <i class="fas fa-trash"></i> Удалить
                             </button>
                        </form>
                        <?php endif; ?>

                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="comment-form">
                                <form method="POST">
                                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" name="comment_text" placeholder="Ваш комментарий..." required>
                                        <button class="btn btn-primary" type="submit" name="submit_comment">Отправить</button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>