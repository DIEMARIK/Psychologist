<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["appointment_id"])) {
    $appointment_id = (int)$_POST["appointment_id"];
    $user_id = $_SESSION["user_id"];

    $pdo->beginTransaction();

    try {
        // 1. Получаем информацию о записи
        $stmt = $pdo->prepare("
            SELECT doctor_id 
            FROM appointments 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$appointment_id, $user_id]);
        $appointment = $stmt->fetch();

        if (!$appointment) {
            throw new Exception("Запись не найдена");
        }

        // 2. Удаляем запись
        $stmt = $pdo->prepare("
            DELETE FROM appointments 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$appointment_id, $user_id]);

        // 3. Уменьшаем счетчик занятых слотов
        $stmt = $pdo->prepare("
            UPDATE doctor_slots 
            SET booked_slots = booked_slots - 1 
            WHERE doctor_id = ? AND booked_slots > 0
        ");
        $stmt->execute([$appointment['doctor_id']]);

        $pdo->commit();
        echo "<div class='alert alert-success'>Запись успешно отменена</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Ошибка: " . $e->getMessage() . "</div>";
    }
}
?>