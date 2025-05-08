<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "workfit";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to database.<br>";

// Execute ALTER TABLE statements directly
$sql1 = "ALTER TABLE diet_entries ADD COLUMN IF NOT EXISTS quantity FLOAT DEFAULT 1 AFTER food_name";
if ($conn->query($sql1) === TRUE) {
    echo "Added or confirmed 'quantity' column.<br>";
} else {
    echo "Error with quantity column: " . $conn->error . "<br>";
}

$sql2 = "ALTER TABLE diet_entries ADD COLUMN IF NOT EXISTS quantity_unit VARCHAR(20) DEFAULT 'serving' AFTER quantity";
if ($conn->query($sql2) === TRUE) {
    echo "Added or confirmed 'quantity_unit' column.<br>";
} else {
    echo "Error with quantity_unit column: " . $conn->error . "<br>";
}

// Show the current table structure
$result = $conn->query("DESCRIBE diet_entries");
echo "<h3>Current Table Structure:</h3>";
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    print_r($row);
}
echo "</pre>";

echo "<p>Database fix completed. <a href='nutrition_finder.php'>Return to Nutrition Finder</a></p>";

// Close connection
$conn->close();
?>