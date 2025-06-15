<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Получаем список записей пользователя
$stmt = $pdo->prepare("
    SELECT a.id, d.name, d.specialization, a.appointment_date, a.appointment_time, a.price 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date, a.appointment_time
");
$stmt->execute([$_SESSION["user_id"]]);
$appointments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Мои записи</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container mt-5">
        <h2>Мои записи</h2>
        
        <?php if (empty($appointments)): ?>
            <div class="alert alert-info">У вас нет активных записей</div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Врач</th>
                        <th>Специализация</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Цена</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td><?= htmlspecialchars($appointment['name']) ?></td>
                            <td><?= htmlspecialchars($appointment['specialization']) ?></td>
                            <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
                            <td><?= htmlspecialchars($appointment['appointment_time']) ?></td>
                            <td><?= number_format($appointment['price'], 2) ?> руб.</td>
                            <td>
                                <form method="POST" action="cancel_appointment.php" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $appointment['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Отменить</button>
                                </form>
                            </td>
                            <td>
    <?= number_format($appointment['price'], 2) ?> руб.
    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#priceDetails<?= $appointment['id'] ?>">
        Детали
    </button>
    
    <td>
    <?= number_format($appointment['price'], 2) ?> руб.
    <?php if (!empty($appointment['price_details'])): ?>
        <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#priceDetails<?= $appointment['id'] ?>">
            Детали
        </button>
        
        <div class="modal fade" id="priceDetails<?= $appointment['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Детали расчета</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php 
                        $details = json_decode($appointment['price_details'], true);
                        if ($details) {
                            echo "<ul>";
                            foreach ($details as $detail) {
                                echo "<li>" . htmlspecialchars($detail) . "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "Информация о деталях расчета недоступна";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>