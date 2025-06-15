<?php
session_start();
require "db.php";

// Включим отладку
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Получаем данные из формы
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Валидация входных данных
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Все поля обязательны для заполнения";
        header("Location: login.php");
        exit;
    }

    try {
        // Подготовка запроса
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса");
        }

        // Выполнение запроса
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Проверка пользователя
        if (!$user) {
            $_SESSION['error'] = "Пользователь с таким email не найден";
            header("Location: login.php");
            exit;
        }

        // Проверка пароля
        if (password_verify($password, $user['password'])) {
            // Успешная авторизация
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error'] = "Неверный пароль";
            header("Location: login.php");
            exit;
        }

    } catch (PDOException $e) {
        error_log("Ошибка базы данных: " . $e->getMessage());
        $_SESSION['error'] = "Произошла ошибка при авторизации";
        header("Location: login.php");
        exit;
    } catch (Exception $e) {
        error_log("Ошибка: " . $e->getMessage());
        $_SESSION['error'] = "Произошла ошибка при авторизации";
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Вход в систему</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Войти</button>
        </form>
        
        <div class="mt-3 text-center">
            <a href="register.php">Регистрация</a> | <a href="forgot_password.php">Забыли пароль?</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
