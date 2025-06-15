<?php
require "db.php";

// Получаем данные врачей с рейтингами
$doctors = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            d.id,
            d.name,
            d.specialization,
            d.experience,
            d.phone,
            d.email,
            d.office,
            d.work_hours,
            d.photo,
            COALESCE(d.rating, 0) as rating,
            COALESCE(d.rating_count, 0) as rating_count
        FROM doctors d
        ORDER BY d.rating DESC
    ");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Найдено врачей: " . count($doctors));
} catch (PDOException $e) {
    error_log("Ошибка при получении данных врачей: " . $e->getMessage());
}

// Функция для отображения звёзд рейтинга
function renderRatingStars($rating) {
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
    
    $html = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    if ($hasHalfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    
    return $html;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты врачей</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
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
        .doctor-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
        }
        .doctor-img {
            max-width: 150px;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: block;
            border: 3px solid #007bff;
        }
        .specialization-badge {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .contact-info {
            margin-top: 15px;
        }
        .contact-info i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
            color: #007bff;
        }
        .rating {
            display: inline-flex;
            align-items: center;
            background-color: #f8f9fa;
            padding: 3px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        .rating-stars {
            color: #ffc107;
            margin-right: 5px;
        }
        .rating-value {
            font-weight: bold;
            margin-left: 3px;
        }
        .review-count {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 5px;
        }
        .no-rating {
            color: #6c757d;
            font-style: italic;
        }
        .doctor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .experience-badge {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <nav>
        <h2>Меню</h2>
        <a href="index.php">Главная</a>
        <a href="Комментарии.php">Отзывы и комментарии</a>
        <a href="Контакты.php">Контакты</a>
    </nav>

    <div class="main-content">
        <h1 class="text-center mb-5">Наши специалисты</h1>
        
        <div class="row">
    <!-- Доктор Иванов -->
    <div class="col-md-6">
        <div class="doctor-card">
            <img src="images/doctor1.jpg" alt="Доктор Иванов" class="doctor-img">
            <h3 class="text-center">Иванов Алексей Петрович</h3>
            <div class="text-center">
                <span class="badge bg-primary specialization-badge">Семейная поддержка</span>
                
                <?php
                // Получаем рейтинг для доктора Иванова (ID = 1)
                $stmt = $pdo->prepare("
                    SELECT 
                        AVG((q1 + q2)/2) as avg_rating,
                        COUNT(*) as review_count
                    FROM feedback
                    WHERE doctor_id = 1
                ");
                $stmt->execute();
                $rating = $stmt->fetch();
                ?>
                
                <div class="rating">
                    <div class="rating-stars">
                        <?php
                        $avg_rating = $rating['avg_rating'] ?? 0;
                        $fullStars = floor($avg_rating);
                        $hasHalfStar = ($avg_rating - $fullStars) >= 0.5;
                        
                        // Полные звёзды
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        // Половина звезды
                        if ($hasHalfStar) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        // Пустые звёзды
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span class="rating-value"><?= number_format($avg_rating, 1) ?></span>
                    <?php if ($rating['review_count'] > 0): ?>
                        <span class="review-count">(<?= $rating['review_count'] ?> отзывов)</span>
                    <?php else: ?>
                        <span class="no-rating">ещё нет оценок</span>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-center">Опыт работы: 3 года</p>
            
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> +7 (900) 123-45-67</p>
                <p><i class="fas fa-envelope"></i> ivanov.ap@example.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Кабинет 101, 1 этаж</p>
                <p><i class="fas fa-clock"></i> Пн-Пт: 9:00 - 14:00</p>
            </div>
        </div>
    </div>
    
    <!-- Доктор Петров -->
    <div class="col-md-6">
        <div class="doctor-card">
            <img src="images/doctor2.jpg" alt="Доктор Петров" class="doctor-img">
            <h3 class="text-center">Петров Дмитрий Сергеевич</h3>
            <div class="text-center">
                <span class="badge bg-success specialization-badge">Кризисная поддержка</span>
                
                <?php
                // Получаем рейтинг для доктора Петрова (ID = 2)
                $stmt = $pdo->prepare("
                    SELECT 
                        AVG((q1 + q2)/2) as avg_rating,
                        COUNT(*) as review_count
                    FROM feedback
                    WHERE doctor_id = 2
                ");
                $stmt->execute();
                $rating = $stmt->fetch();
                ?>
                
                <div class="rating">
                    <div class="rating-stars">
                        <?php
                        $avg_rating = $rating['avg_rating'] ?? 0;
                        $fullStars = floor($avg_rating);
                        $hasHalfStar = ($avg_rating - $fullStars) >= 0.5;
                        
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        if ($hasHalfStar) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span class="rating-value"><?= number_format($avg_rating, 1) ?></span>
                    <?php if ($rating['review_count'] > 0): ?>
                        <span class="review-count">(<?= $rating['review_count'] ?> отзывов)</span>
                    <?php else: ?>
                        <span class="no-rating">ещё нет оценок</span>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-center">Опыт работы: 7 лет</p>
            
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> +7 (900) 234-56-78</p>
                <p><i class="fas fa-envelope"></i> petrov.ds@example.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Кабинет 205, 2 этаж</p>
                <p><i class="fas fa-clock"></i> Пн-Пт: 14:00 - 20:00</p>
            </div>
        </div>
    </div>
    
    <!-- Доктор Сидоров -->
    <div class="col-md-6">
        <div class="doctor-card">
            <img src="images/doctor3.jpg" alt="Доктор Сидоров" class="doctor-img">
            <h3 class="text-center">Сидоров Михаил Иванович</h3>
            <div class="text-center">
                <span class="badge bg-info specialization-badge">Курс по самопознанию</span>
                
                <?php
                // Получаем рейтинг для доктора Сидорова (ID = 3)
                $stmt = $pdo->prepare("
                    SELECT 
                        AVG((q1 + q2)/2) as avg_rating,
                        COUNT(*) as review_count
                    FROM feedback
                    WHERE doctor_id = 3
                ");
                $stmt->execute();
                $rating = $stmt->fetch();
                ?>
                
                <div class="rating">
                    <div class="rating-stars">
                        <?php
                        $avg_rating = $rating['avg_rating'] ?? 0;
                        $fullStars = floor($avg_rating);
                        $hasHalfStar = ($avg_rating - $fullStars) >= 0.5;
                        
                        for ($i = 0; $i < $fullStars; $i++) {
                            echo '<i class="fas fa-star"></i>';
                        }
                        
                        if ($hasHalfStar) {
                            echo '<i class="fas fa-star-half-alt"></i>';
                        }
                        
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                        for ($i = 0; $i < $emptyStars; $i++) {
                            echo '<i class="far fa-star"></i>';
                        }
                        ?>
                    </div>
                    <span class="rating-value"><?= number_format($avg_rating, 1) ?></span>
                    <?php if ($rating['review_count'] > 0): ?>
                        <span class="review-count">(<?= $rating['review_count'] ?> отзывов)</span>
                    <?php else: ?>
                        <span class="no-rating">ещё нет оценок</span>
                    <?php endif; ?>
                </div>
            </div>
            <p class="text-center">Опыт работы: 12 лет</p>
            
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> +7 (900) 345-67-89</p>
                <p><i class="fas fa-envelope"></i> sidorov.mi@example.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Кабинет 310, 3 этаж</p>
                <p><i class="fas fa-clock"></i> Пн-Пт: 10:00 - 16:00</p>
            </div>
        </div>
    </div>
            
            <!-- Блок администрации -->
            <div class="col-md-6">
                <div class="doctor-card">
                    <img src="images/admin.jpg" alt="Администрация" class="doctor-img">
                    <h3 class="text-center">Администрация</h3>
                    <div class="text-center">
                        <span class="badge bg-secondary specialization-badge">Общие вопросы</span>
                        <div class="rating">
                            <span class="no-rating">нет оценок</span>
                        </div>
                    </div>
                    
                    <div class="contact-info">
                        <p><i class="fas fa-phone"></i> +7 (900) 230-80-24 (общий)</p>
                        <p><i class="fas fa-envelope"></i> mrblacksmith@yandex.ru</p>
                        <p><i class="fas fa-map-marker-alt"></i> Тула, ул. Ленина, д. 58</p>
                        <p><i class="fas fa-clock"></i> Пн-Сб: 8:00 - 21:00</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Как нас найти</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-subway"></i> На общественном транспорте</h5>
                        <p>Ближайшая станция метро "Центральная". Автобусы № 5, 12, 24 до остановки "Улица Ленина".</p>
                        
                        <h5 class="mt-4"><i class="fas fa-car"></i> На автомобиле</h5>
                        <p>Имеется парковка на территории клиники. Въезд с ул. Центральной.</p>
                    </div>
                    <div class="col-md-6">
                        <!-- Здесь можно разместить карту -->
                        <div style="width: 100%; height: 250px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                            [Карта будет здесь]
                        </div>
                        <p class="text-center mt-2">Тула, ул. Ленина, д. 58</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Автоматическое обновление страницы каждые 5 минут для актуальных рейтингов
        setTimeout(() => {
            window.location.reload();
        }, 300000);
    </script>
</body>
</html>