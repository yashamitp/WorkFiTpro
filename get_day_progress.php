<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if day parameter is provided
if (!isset($_GET['day'])) {
    echo json_encode(['success' => false, 'message' => 'Day parameter is required']);
    exit;
}

$day = $_GET['day'];

// Get progress for the specified day
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed
    FROM exercises e
    JOIN workout_plans p ON e.plan_id = p.id
    WHERE p.user_id = ? AND e.day_of_week = ?
");
$stmt->bind_param("is", $user_id, $day);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

$total = (int)$result['total'];
$completed = (int)$result['completed'];
$percentage = $total > 0 ? round(($completed / $total) * 100) : 0;

echo json_encode([
    'success' => true,
    'total' => $total,
    'completed' => $completed,
    'percentage' => $percentage
]);
?>