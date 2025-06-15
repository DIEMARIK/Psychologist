<?php
$host = "localhost";
$dbname = "psycologist_site";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Создание таблиц при первом подключении
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        comment TEXT NOT NULL,
        rating INT NOT NULL,
        likes INT DEFAULT 0,
        dislikes INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    );
    
    CREATE TABLE IF NOT EXISTS review_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        review_id INT NOT NULL,
        vote_type ENUM('like', 'dislike') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (review_id) REFERENCES reviews(id),
        UNIQUE KEY unique_vote (user_id, review_id)
    );
    
    CREATE TABLE IF NOT EXISTS review_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        review_id INT NOT NULL,
        comment_text TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (review_id) REFERENCES reviews(id)
    );
    CREATE TABLE comment_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    comment_id INT NOT NULL,
    vote_type ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (user_id, comment_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES review_comments(id) ON DELETE CASCADE
    );
        
        CREATE TABLE IF NOT EXISTS appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            doctor_id INT NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            price_details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS doctor_slots (
            doctor_id INT PRIMARY KEY,
            total_slots INT NOT NULL,
            booked_slots INT NOT NULL,
            FOREIGN KEY (doctor_id) REFERENCES doctors(id)
        );
        
        CREATE TABLE IF NOT EXISTS doctors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            specialization VARCHAR(100) NOT NULL,
            experience INT NOT NULL,
            base_price DECIMAL(10,2) NOT NULL
        );
        ALTER TABLE users MODIFY doctor_id INT NULL;
        ALTER TABLE users MODIFY doctor_id INT DEFAULT 1;
        CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    doctor_id INT NOT NULL,
    user_id INT NOT NULL,
    q1 INT NOT NULL,
    q2 INT NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    short_description TEXT,
    full_description TEXT,
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS client_stats (
    id int primary key AUTO_INCREMENT,
    total_clients int DEFAULT 0,
    last_updated timestamp DEFAULT CURRENT_TIMESTAMP on UPDATE CURRENT_TIMESTAMP
    );
    -- insert into client_stats (total_clients) VALUES (0) on duplicate key update total_clients = total_clients

        // Заполняем начальными данными
        INSERT IGNORE INTO doctors (id, name, specialization, experience, base_price) VALUES
        (1, 'Доктор Иванов', 'Семейная поддержка', 3, 2000),
        (2, 'Доктор Петров', 'Кризисная поддержка', 7, 2500),
        (3, 'Доктор Сидоров', 'Курс по самопознанию', 12, 3000);
        
        INSERT IGNORE INTO doctor_slots (doctor_id, total_slots, booked_slots) VALUES
        (1, 4, 0),
        (2, 4, 0),
        (3, 4, 0);
    ");
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}
?>