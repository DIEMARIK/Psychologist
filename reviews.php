<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $comment = $_POST["comment"];
    $rating = $_POST["rating"];
    $user_id = $_SESSION["user_id"];

    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, comment, rating) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $comment, $rating]);
    $success = "Отзыв успешно добавлен!";
}

$reviews = $pdo->query("
    SELECT users.username, reviews.comment, reviews.rating, reviews.created_at 
    FROM reviews JOIN users ON reviews.user_id = users.id 
    ORDER BY reviews.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h2 class="mb-4">Оставить отзыв</h2>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <form method="POST" class="mb-5">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Ваш отзыв</label>
                        <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="rating" class="form-label">Оценка</label>
                        <select class="form-select" id="rating" name="rating" required>
                            <option value="5">5 - Отлично</option>
                            <option value="4">4 - Хорошо</option>
                            <option value="3">3 - Нормально</option>
                            <option value="2">2 - Плохо</option>
                            <option value="1">1 - Ужасно</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </form>

                <h2 class="mb-4">Отзывы наших клиентов</h2>
                
                <?php foreach ($reviews as $review): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($review["username"]) ?></h5>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="fa-star <?= $i <= $review["rating"] ? 'fas text-warning' : 'far' ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <p class="card-text"><?= nl2br(htmlspecialchars($review["comment"])) ?></p>
                            <p class="text-muted small">
                                <?= date("d.m.Y H:i", strtotime($review["created_at"])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-code.js" crossorigin="anonymous"></script>
</body>
</html>