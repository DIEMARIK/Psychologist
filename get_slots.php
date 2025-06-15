<?php
require "db.php";

header('Content-Type: application/json');

if (isset($_GET['doctor_id'])) {
    $doctor_id = (int)$_GET['doctor_id'];

    $stmt = $pdo->prepare("SELECT total_slots, booked_slots FROM doctor_slots WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $slot = $stmt->fetch();

    if ($slot) {
        echo json_encode([
            'total' => (int)$slot['total_slots'],
            'booked' => (int)$slot['booked_slots'],
            'available' => (int)$slot['total_slots'] - (int)$slot['booked_slots']
        ]);
    } else {
        echo json_encode(['error' => 'Слоты не найдены']);
    }
} else {
    echo json_encode(['error' => 'Некорректный ID']);
}
