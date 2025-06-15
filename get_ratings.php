<?php
require "db.php";

$ratings = [];

try {
    $stmt = $pdo->prepare("
        SELECT doctor_id, AVG((q1 + q2)/2) as avg_rating, COUNT(*) as review_count
        FROM feedback
        GROUP BY doctor_id
    ");
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        $ratings[$row['doctor_id'] = [
            'rating' => round($row['avg_rating'], 1),
            'count' => $row['review_count']
        ];
    }
    
    echo json_encode($ratings);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>