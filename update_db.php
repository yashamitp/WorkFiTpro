<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the configuration file
require_once 'config.php';

echo "<h2>Database Update Script</h2>";

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful.<br>";

// Check if diet_entries table exists
$result = $conn->query("SHOW TABLES LIKE 'diet_entries'");
if ($result->num_rows == 0) {
    die("Error: diet_entries table does not exist.<br>");
}
echo "diet_entries table exists.<br>";

// Show current table structure
echo "<h3>Current Table Structure:</h3>";
$result = $conn->query("DESCRIBE diet_entries");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

// Check if the quantity column already exists
$result = $conn->query("SHOW COLUMNS FROM diet_entries LIKE 'quantity'");
$quantity_exists = $result->num_rows > 0;
echo "Quantity column exists: " . ($quantity_exists ? "Yes" : "No") . "<br>";

// Check if the quantity_unit column already exists
$result = $conn->query("SHOW COLUMNS FROM diet_entries LIKE 'quantity_unit'");
$quantity_unit_exists = $result->num_rows > 0;
echo "Quantity_unit column exists: " . ($quantity_unit_exists ? "Yes" : "No") . "<br>";

// Add the quantity column if it doesn't exist
if (!$quantity_exists) {
    $sql = "ALTER TABLE diet_entries ADD COLUMN quantity FLOAT DEFAULT 1 AFTER food_name";
    if ($conn->query($sql) === TRUE) {
        echo "Added 'quantity' column to diet_entries table.<br>";
    } else {
        echo "Error adding 'quantity' column: " . $conn->error . "<br>";
    }
} else {
    echo "Quantity column already exists, no action needed.<br>";
}

// Add the quantity_unit column if it doesn't exist
if (!$quantity_unit_exists) {
    $sql = "ALTER TABLE diet_entries ADD COLUMN quantity_unit VARCHAR(20) DEFAULT 'serving' AFTER quantity";
    if ($conn->query($sql) === TRUE) {
        echo "Added 'quantity_unit' column to diet_entries table.<br>";
    } else {
        echo "Error adding 'quantity_unit' column: " . $conn->error . "<br>";
    }
} else {
    echo "Quantity_unit column already exists, no action needed.<br>";
}

// Show updated table structure
echo "<h3>Updated Table Structure:</h3>";
$result = $conn->query("DESCRIBE diet_entries");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

echo "<h3>Database update completed.</h3>";
echo "<p><a href='nutrition_finder.php'>Return to Nutrition Finder</a></p>";
?>