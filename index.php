<?php
session_start();
require "db.php";

// Получаем список врачей из БД
$doctors = $pdo->query("SELECT * FROM doctors")->fetchAll();

// Получаем услуги
$services = $pdo->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll();

// Получаем информацию о слотах
$slotsInfo = [];
$stmt = $pdo->query("SELECT doctor_id, total_slots, booked_slots FROM doctor_slots");
while ($row = $stmt->fetch()) {
    $slotsInfo[$row['doctor_id']] = $row;
}

// Количество клиентов
$totalClients = $pdo->query("SELECT total_clients FROM client_stats LIMIT 1")->fetchColumn();

// Выбранный врач
$selectedDoctor = isset($_POST['doctor']) ? (int)$_POST['doctor'] : 1;

// Сезон с наибольшим числом записей
function getSeasonByMonth($month) {
    if (in_array($month, [12, 1, 2])) return "Зима";
    if (in_array($month, [3, 4, 5])) return "Весна";
    if (in_array($month, [6, 7, 8])) return "Лето";
    return "Осень";
}

function getMostPopularSeason($pdo) {
    $stmt = $pdo->query("SELECT appointment_date FROM appointments");
    $counts = ["Зима" => 0, "Весна" => 0, "Лето" => 0, "Осень" => 0];
    while ($row = $stmt->fetch()) {
        $month = (int)date("m", strtotime($row['appointment_date']));
        $season = getSeasonByMonth($month);
        $counts[$season]++;
    }
    arsort($counts);
    reset($counts);
    return key($counts);
}

$popularSeason = getMostPopularSeason($pdo);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Психолог Онлайн</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"  rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> 
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
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
    </style>
</head>
<body>

<nav>
    <h2>Меню</h2>
    <a href="index.php">Главная</a>
    <a href="Контакты.php">Контакты</a>
    <a href="#services">Услуги</a>
    <a href="Формы.html">Записаться на консультацию</a>
    <a href="Комментарии.php">Отзывы</a>
    <a href="puzzles.php">Пятнашки</a>
</nav>

<div class="main-content">
    <div class="jumbotron">
        <h1 class="display-4">Психолог Онлайн</h1>
        <p class="lead">Личный сайт профессионального психолога. Помощь в преодолении сложных жизненных ситуаций.</p>
        <div class="d-flex justify-content-center">
            <img src="psychology.png" class="img-fluid" style="max-width: 30%" alt="Психолог Онлайн">
        </div>
    </div>

    <div class="row my-5">
        <div class="col-12 text-center">
            <div class="p-4 bg-light rounded">
                <h3>Нам доверяют</h3>
                <div class="display-4 text-primary my-3" id="clientCounter">
                    <?= number_format($totalClients, 0, '', ' ') ?>
                </div>
                <p class="lead">людей получили квалифицированную помощь</p>
            </div>
        </div>
    </div>

    <section id="services">
        <div class="row mt-4">
            <?php foreach ($services as $service): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="<?= htmlspecialchars($service['image_url']) ?>" class="card-img-top" alt="<?= $service['title'] ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($service['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($service['short_description']) ?></p>
                            <a href="service.php?id=<?= $service['id'] ?>" class="btn btn-primary">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="schedule" class="mt-5">
        <h2>Расписание консультаций</h2>
        <table class="table table-striped">
            <thead class="thead-dark">
            <tr>
                <th>День недели</th>
                <th>Время</th>
                <th>Тип консультации</th>
            </tr>
            </thead>
            <tbody>
            <tr><td>Понедельник</td><td>10:00 - 14:00</td><td>Индивидуальная консультация</td></tr>
            <tr><td>Вторник</td><td>12:00 - 16:00</td><td>Семейная терапия</td></tr>
            <tr><td>Среда</td><td>14:00 - 18:00</td><td>Поддержка в кризисной ситуации</td></tr>
            <tr><td>Четверг</td><td>11:00 - 15:00</td><td>Индивидуальная консультация</td></tr>
            <tr><td>Пятница</td><td>13:00 - 17:00</td><td>Курс по самопознанию</td></tr>
            </tbody>
        </table>
    </section>

    <section id="booking" class="mt-5">
        <h2>Запись на консультацию</h2>
        <form id="bookingForm" method="POST" action="calculate.php">
            <div class="mb-3">
                <label for="dateInput">Выберите дату:</label>
                <input type="date" name="date" id="dateInput" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="doctorSelect">Выберите врача:</label>
                <select name="doctor" id="doctorSelect" class="form-control" required>
                    <?php foreach ($doctors as $doctor): ?>
                        <option value="<?= $doctor['id'] ?>" <?= ($selectedDoctor == $doctor['id']) ? 'selected' : '' ?>>
                            <?= $doctor['name'] ?> (<?= $doctor['specialization'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p id="availableSlots" class="text-info">Загрузка свободных мест...</p>
            </div>
            <div class="mb-3">
                <label for="timeInput">Выберите время:</label>
                <select name="time" id="timeInput" class="form-control" required>
                    <?php
                    $startHour = ($selectedDoctor == 2) ? 14 : 7;
                    $endHour = ($selectedDoctor == 2) ? 20 : 14;
                    for ($hour = $startHour; $hour < $endHour; $hour++) {
                        $timeValue = sprintf("%02d:00", $hour);
                        echo "<option value=\"$timeValue\">$timeValue</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Рассчитать стоимость</button>
        </form>
    </section>

    <div class="alert alert-info mt-4">
        <h5><i class="fas fa-chart-line"></i> Статистика:</h5>
        <p>Чаще всего пользователи записываются на приём в <strong><?= $popularSeason ?></strong>.</p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const doctorSelect = document.getElementById('doctorSelect');
    const timeSelect = document.getElementById('timeInput');
    const availableSlots = document.getElementById('availableSlots');

    function updateSlotsAndTimes(doctorId) {
        // Обновляем время
        let startHour = (doctorId == 2) ? 14 : 7;
        let endHour = (doctorId == 2) ? 20 : 14;
        timeSelect.innerHTML = '';
        for (let hour = startHour; hour < endHour; hour++) {
            const timeValue = `${hour.toString().padStart(2, '0')}:00`;
            const option = new Option(timeValue, timeValue);
            timeSelect.appendChild(option);
        }

        // Запрашиваем данные о слотах
        fetch('get_slots.php?doctor_id=' + doctorId)
            .then(response => response.json())
            .then(data => {
                availableSlots.textContent = `Свободных мест: ${data.available} из ${data.total}`;
            })
            .catch(error => {
                console.error("Ошибка получения данных:", error);
                availableSlots.textContent = "Не удалось загрузить данные";
            });
    }

    // Инициализация при загрузке
    if (doctorSelect && availableSlots && timeSelect) {
        updateSlotsAndTimes(doctorSelect.value);

        doctorSelect.addEventListener('change', function () {
            updateSlotsAndTimes(this.value);
        });
    }
});
</script>

<!-- Анимация счетчика -->
<script>
function animateCounter(element, target, duration = 2000) {
    const start = parseInt(element.textContent.replace(/\s/g, '')) || 0;
    const increment = (target - start) / (duration / 16);
    let current = start;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            clearInterval(timer);
            current = target;
        }
        element.textContent = Math.floor(current).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }, 16);
}

document.addEventListener('DOMContentLoaded', function() {
    const counterElement = document.getElementById('clientCounter');
    const targetValue = parseInt(counterElement.textContent.replace(/\s/g, '')) || 0;
    counterElement.textContent = "0";
    animateCounter(counterElement, targetValue);
});
</script>

</body>
</html>