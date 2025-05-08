<?php
require_once 'config.php';

// Check if the exercises table has the is_completed column
$result = $conn->query("SHOW COLUMNS FROM exercises LIKE 'is_completed'");
if ($result->num_rows == 0) {
    // Add the is_completed column if it doesn't exist
    $conn->query("ALTER TABLE exercises ADD COLUMN is_completed TINYINT(1) DEFAULT 0");
    echo "Added is_completed column to exercises table.<br>";
} else {
    // Check the data type of the is_completed column
    $column_info = $result->fetch_assoc();
    echo "Current is_completed column type: " . $column_info['Type'] . "<br>";
    
    // If it's not TINYINT(1), modify it
    if ($column_info['Type'] != 'tinyint(1)') {
        $conn->query("ALTER TABLE exercises MODIFY COLUMN is_completed TINYINT(1) DEFAULT 0");
        echo "Modified is_completed column type to TINYINT(1).<br>";
    }
}

// Test updating an exercise's completion status
if (isset($_GET['test']) && isset($_GET['exercise_id'])) {
    $exercise_id = $_GET['exercise_id'];
    $is_completed = isset($_GET['completed']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE exercises SET is_completed = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_completed, $exercise_id);
    
    if ($stmt->execute()) {
        echo "Successfully updated exercise #$exercise_id completion status to $is_completed.<br>";
    } else {
        echo "Failed to update exercise: " . $conn->error . "<br>";
    }
}

// Display all exercises with their completion status
$result = $conn->query("SELECT id, exercise_name, day_of_week, is_completed FROM exercises ORDER BY id");

if ($result->num_rows > 0) {
    echo "<h2>Exercises in Database</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Exercise Name</th><th>Day</th><th>Completed</th><th>Actions</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['exercise_name'] . "</td>";
        echo "<td>" . $row['day_of_week'] . "</td>";
        echo "<td>" . ($row['is_completed'] ? 'Yes' : 'No') . "</td>";
        echo "<td>";
        if ($row['is_completed']) {
            echo "<a href='fix_exercises_table.php?test=1&exercise_id=" . $row['id'] . "'>Mark as Incomplete</a>";
        } else {
            echo "<a href='fix_exercises_table.php?test=1&exercise_id=" . $row['id'] . "&completed=1'>Mark as Complete</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No exercises found in the database.";
}
?>