<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = $_POST['title'];
    $short_desc = $_POST['short_description'];
    $full_desc = $_POST['full_description'];
    $image_url = $_POST['image_url'];

    $stmt = $pdo->prepare("INSERT INTO services (title, short_description, full_description, image_url) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $short_desc, $full_desc, $image_url]);

    header("Location: index.php");
    exit();
}
?>
