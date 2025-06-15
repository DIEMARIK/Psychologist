<?php
session_start();
require "db.php";

// Включим отладку
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Проверка авторизации
if (!isset($_SESSION["user_id"])) {
    echo json_encode(['error' => 'Требуется авторизация']);
    exit;
}

// Проверка метода запроса
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['error' => 'Неподдерживаемый метод запроса']);
    exit;
}

// Получаем и проверяем данные
$required = ['appointment_id', 'doctor_id', 'q1', 'q2'];
foreach ($required as $field) {
    if (!isset($_POST[$field])) {
        echo json_encode(['error' => "Не заполнено поле: $field"]);
        exit;
    }
}

// Приводим данные к нужному типу
$appointment_id = (int)$_POST['appointment_id'];
$doctor_id = (int)$_POST['doctor_id'];
$user_id = (int)$_SESSION['user_id'];
$q1 = (int)$_POST['q1'];
$q2 = (int)$_POST['q2'];
$comments = !empty($_POST['comments']) ? trim($_POST['comments']) : null;

// Проверяем диапазон оценок
if ($q1 < 1 || $q1 > 5 || $q2 < 1 || $q2 > 5) {
    echo json_encode(['error' => 'Оценки должны быть от 1 до 5']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Проверяем существование записи
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->execute([$appointment_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Запись не найдена или не принадлежит вам");
    }

    // 2. Проверяем, не оставлял ли уже отзыв
    $stmt = $pdo->prepare("SELECT id FROM feedback WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    
    if ($stmt->fetch()) {
        throw new Exception("Вы уже оставляли отзыв для этой записи");
    }

    // 3. Сохраняем отзыв
    $stmt = $pdo->prepare("
        INSERT INTO feedback 
        (appointment_id, doctor_id, user_id, q1, q2, comments, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt->execute([$appointment_id, $doctor_id, $user_id, $q1, $q2, $comments])) {
        throw new Exception("Ошибка сохранения отзыва");
    }

    // 4. Обновляем рейтинг врача
    $stmt = $pdo->prepare("
        UPDATE doctors 
        SET 
            rating = (SELECT AVG((q1+q2)/2) FROM feedback WHERE doctor_id = ?),
            rating_count = (SELECT COUNT(*) FROM feedback WHERE doctor_id = ?)
        WHERE id = ?
    ");
    
    if (!$stmt->execute([$doctor_id, $doctor_id, $doctor_id])) {
        throw new Exception("Ошибка обновления рейтинга врача");
    }

    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Отзыв успешно сохранён']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("DB Error: " . $e->getMessage());
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>