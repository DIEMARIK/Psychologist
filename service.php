<?php
require 'db.php';

if (!isset($_GET['id'])) {
    echo "Услуга не найдена.";
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    echo "Услуга не найдена.";
    exit;
}
?>

<h2><?= htmlspecialchars($service['title']) ?></h2>
<img src="<?= htmlspecialchars($service['image_url']) ?>" width = "600" class="img-fluid mb-3" alt="">
<p><?= nl2br(htmlspecialchars($service['full_description'])) ?></p>
<a href="index.php" class="btn btn-secondary">Назад</a>
