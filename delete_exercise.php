<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Check if exercise_id is provided
if (!isset($_POST['exercise_id'])) {
    echo json_encode(['success' => false, 'message' => 'Exercise ID is required']);
    exit;
}

$exercise_id = $_POST['exercise_id'];

// Verify that the exercise belongs to the user
$stmt = $conn->prepare("
    SELECT e.id 
    FROM exercises e
    JOIN workout_plans p ON e.plan_id = p.id
    WHERE e.id = ? AND p.user_id = ?
");
$stmt->bind_param("ii", $exercise_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Exercise not found or not authorized']);
    exit;
}

// Delete the exercise
$stmt = $conn->prepare("DELETE FROM exercises WHERE id = ?");
$stmt->bind_param("i", $exercise_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete exercise']);
}
?>