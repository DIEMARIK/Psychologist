<?php
session_start();
require "db.php";

// Проверка авторизации пользователя
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Обработка формы
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем данные из формы
    $doctor_id = (int)$_POST["doctor"];
    $selected_date = $_POST['date'];
    $selected_time = $_POST["time"];
    $user_id = $_SESSION["user_id"];

    // Начинаем транзакцию
    $pdo->beginTransaction();

    try {
        // 1. Проверяем доступность слота
        $stmt = $pdo->prepare("
            SELECT total_slots, booked_slots 
            FROM doctor_slots 
            WHERE doctor_id = ? 
            FOR UPDATE
        ");
        $stmt->execute([$doctor_id]);
        $slot = $stmt->fetch();

        if (!$slot || $slot['booked_slots'] >= $slot['total_slots']) {
            throw new Exception("Все места у врача заняты");
        }

        // 2. Проверяем, не занято ли время
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ?
        ");
        $stmt->execute([$doctor_id, $selected_date, $selected_time]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Это время уже занято");
        }

        // 3. Получаем информацию о враче
        $stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            throw new Exception("Врач не найден");
        }

        // 4. Проверяем допустимое время для врача
        $hour = (int)explode(":", $selected_time)[0];

        if ($doctor_id == 2 && ($hour < 14 || $hour >= 20)) {
            throw new Exception("Доктор Петров принимает только с 14:00 до 20:00");
        }

        // 5. Рассчитываем цену
        $base_price = $doctor['base_price'];
        $final_price = $base_price;
        $price_details = ["Базовая цена: " . number_format($base_price, 2) . " руб."];

        if ($doctor_id == 2) {
            $final_price *= 1.15; // +15%
            $price_details[] = "Надбавка 15% за высокий спрос";
        } elseif ($doctor_id == 3) {
            $final_price *= 1.20; // +20%
            $price_details[] = "Надбавка 20% за опыт";
        }

        if ($hour < 10) {
            $final_price *= 0.90; // -10%
            $price_details[] = "Скидка 10% за раннюю запись (до 10:00)";
        }

        // Подготавливаем детали цены
        $price_details_str = json_encode($price_details);

        // 6. Создаем запись
        $stmt = $pdo->prepare("
            INSERT INTO appointments 
            (user_id, doctor_id, appointment_date, appointment_time, price, price_details) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $doctor_id,
            $selected_date,
            $selected_time,
            $final_price,
            $price_details_str
        ]);

        // Сохраняем ID записи
        $appointment_id = $pdo->lastInsertId();

        // 7. Увеличиваем количество занятых слотов
        $stmt = $pdo->prepare("
            UPDATE doctor_slots 
            SET booked_slots = booked_slots + 1 
            WHERE doctor_id = ?
        ");
        $stmt->execute([$doctor_id]);

        // Завершаем транзакцию
        $pdo->commit();

        // Выводим результат
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-success'>";
        echo "<h4>Запись успешно оформлена!</h4>";
        echo "<p><strong>Врач:</strong> {$doctor['name']}</p>";
        echo "<p><strong>Дата:</strong> $selected_date</p>";
        echo "<p><strong>Время:</strong> $selected_time</p>";
        echo "<p><strong>Итоговая стоимость:</strong> " . number_format($final_price, 2) . " руб.</p>";
        echo "<p><strong>Детали расчета:</strong></p>";
        echo "<ul>";
        foreach ($price_details as $detail) {
            echo "<li>$detail</li>";
        }
        echo "</ul>";

        // Отображаем корректное число свободных мест
        $available_now = ($slot['total_slots'] - $slot['booked_slots'] - 1);
        echo "<p class='text-info'>Свободных мест осталось: $available_now</p>";
        echo "</div>";

        // Форма отзыва
        echo "<form id='feedbackForm' method='post' action='save_feedback.php' class='mt-4'>";
        echo "<h4>Опрос о консультации</h4>";
        echo "<input type='hidden' name='appointment_id' value='$appointment_id'>";
        echo "<input type='hidden' name='doctor_id' value='$doctor_id'>";

        // Вопрос 1
        echo "<div class='form-group'>";
        echo "<label for='q1'>Насколько вежлив был доктор?</label>";
        echo "<select name='q1' class='form-control' required>";
        echo "<option value='' disabled selected>Выберите оценку</option>";
        for ($i = 5; $i >= 1; $i--) {
            echo "<option value='$i'>$i</option>";
        }
        echo "</select>";
        echo "</div>";

        // Вопрос 2
        echo "<div class='form-group mt-3'>";
        echo "<label for='q2'>Оцените качество консультации:</label>";
        echo "<select name='q2' class='form-control' required>";
        echo "<option value='' disabled selected>Выберите оценку</option>";
        for ($i = 5; $i >= 1; $i--) {
            echo "<option value='$i'>$i</option>";
        }
        echo "</select>";
        echo "</div>";

        // Комментарий
        echo "<div class='form-group mt-3'>";
        echo "<label for='comments'>Комментарий (необязательно):</label>";
        echo "<textarea name='comments' id='comments' class='form-control' rows='3'></textarea>";
        echo "</div>";

        // Кнопка
        echo "<button type='submit' class='btn btn-success mt-3'>Отправить отзыв</button>";
        echo "</form>";

        echo "<a href='index.php' class='btn btn-primary mt-4'>Вернуться на главную</a>";
        echo "</div>";

        // JS для формы отзыва
        echo <<<JS
<script>
document.getElementById('feedbackForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Отправка...';

    try {
        const response = await fetch('save_feedback.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            alert(data.message || 'Спасибо за ваш отзыв!');
            window.location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    } catch (error) {
        console.error('Ошибка:', error);
        alert('Произошла ошибка при отправке отзыва');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Отправить отзыв';
    }
});
</script>
JS;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='container mt-5'>";
        echo "<div class='alert alert-danger'>Ошибка: " . $e->getMessage() . "</div>";
        echo "<a href='index.php' class='btn btn-primary'>Вернуться на главную</a>";
        echo "</div>";
    }
}
?>