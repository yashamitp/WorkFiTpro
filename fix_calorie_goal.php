<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration file to get the database connection
require_once 'config.php';

echo "<h2>Fixing Calorie Goal Column</h2>";

// Check if the calorie_goal column already exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'calorie_goal'");
$column_exists = $result->num_rows > 0;

if (!$column_exists) {
    // Add the calorie_goal column
    try {
        $sql = "ALTER TABLE users ADD COLUMN calorie_goal INT DEFAULT 2000";
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color: green;'>Successfully added 'calorie_goal' column to users table.</p>";
        } else {
            echo "<p style='color: red;'>Error adding column: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: blue;'>The 'calorie_goal' column already exists in the users table.</p>";
}

// Show the current table structure
$result = $conn->query("DESCRIBE users");
echo "<h3>Current Users Table Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Set default calorie goals for all users
$sql = "UPDATE users SET calorie_goal = 2000 WHERE calorie_goal IS NULL OR calorie_goal = 0";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>Set default calorie goals for users.</p>";
} else {
    echo "<p style='color: red;'>Error setting default goals: " . $conn->error . "</p>";
}

echo "<p>Database update completed.</p>";
echo "<p><a href='diet_tracker.php' style='padding: 10px 20px; background-color: #4cc9f0; color: white; text-decoration: none; border-radius: 5px;'>Go to Diet Tracker</a></p>";
?>