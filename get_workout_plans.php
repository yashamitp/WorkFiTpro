<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Return empty array if not logged in
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];

// Get user's workout plans
$stmt = $conn->prepare("SELECT id, plan_name FROM workout_plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$workout_plans = $result->fetch_all(MYSQLI_ASSOC);

// Return plans as JSON
header('Content-Type: application/json');
echo json_encode($workout_plans);
?>