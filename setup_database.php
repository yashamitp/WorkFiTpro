<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";

echo "<h1>WorkFit Database Setup</h1>";

// Step 1: Connect to MySQL server (without selecting a database)
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("<p style='color: red;'>Connection to MySQL server failed: " . $conn->connect_error . "</p>");
}
echo "<p style='color: green;'>Connected to MySQL server successfully.</p>";

// Step 2: Check if database exists
$dbname = "workfit";
$result = $conn->query("SHOW DATABASES LIKE '$dbname'");
if ($result->num_rows == 0) {
    // Database doesn't exist, create it
    echo "<p>Database '$dbname' does not exist. Creating it now...</p>";
    
    if ($conn->query("CREATE DATABASE $dbname")) {
        echo "<p style='color: green;'>Database created successfully.</p>";
    } else {
        die("<p style='color: red;'>Error creating database: " . $conn->error . "</p>");
    }
} else {
    echo "<p>Database '$dbname' already exists.</p>";
}

// Step 3: Select the database
$conn->select_db($dbname);
echo "<p>Using database: $dbname</p>";

// Step 4: Create tables if they don't exist
$tables = [
    "users" => "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            calorie_goal INT DEFAULT 2000
        )
    ",
    
    "workout_plans" => "
        CREATE TABLE IF NOT EXISTS workout_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ",
    
    "exercises" => "
        CREATE TABLE IF NOT EXISTS exercises (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            exercise_name VARCHAR(100) NOT NULL,
            sets INT DEFAULT 3,
            reps INT DEFAULT 10,
            weight FLOAT,
            day_of_week VARCHAR(20) NOT NULL,
            notes TEXT,
            is_completed BOOLEAN DEFAULT 0,
            FOREIGN KEY (plan_id) REFERENCES workout_plans(id) ON DELETE CASCADE
        )
    ",
    
    "diet_entries" => "
        CREATE TABLE IF NOT EXISTS diet_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            food_name VARCHAR(255) NOT NULL,
            quantity FLOAT DEFAULT 1,
            quantity_unit VARCHAR(20) DEFAULT 'serving',
            calories INT NOT NULL,
            protein FLOAT,
            entry_date DATE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ",
    
    "progress_records" => "
        CREATE TABLE IF NOT EXISTS progress_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            weight FLOAT,
            height FLOAT,
            bmi FLOAT,
            body_fat FLOAT,
            record_date DATE NOT NULL,
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ",
    
    "goals" => "
        CREATE TABLE IF NOT EXISTS goals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            goal_name VARCHAR(255) NOT NULL,
            goal_type ENUM('weight', 'strength', 'endurance', 'other') NOT NULL,
            target_value FLOAT,
            current_value FLOAT,
            target_date DATE,
            is_achieved BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    "
];

echo "<h2>Creating Tables</h2>";
echo "<ul>";

foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql)) {
        echo "<li style='color: green;'>Table '$table_name' created or already exists.</li>";
    } else {
        echo "<li style='color: red;'>Error creating table '$table_name': " . $conn->error . "</li>";
    }
}

echo "</ul>";

// Step 5: Check if there's at least one user in the database
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // No users exist, create a default user
    echo "<h2>Creating Default User</h2>";
    
    // Hash the password
    $default_password = password_hash("password123", PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (name, email, password) VALUES ('Demo User', 'demo@example.com', '$default_password')";
    
    if ($conn->query($sql)) {
        echo "<p style='color: green;'>Default user created successfully.</p>";
        echo "<p>Email: demo@example.com<br>Password: password123</p>";
    } else {
        echo "<p style='color: red;'>Error creating default user: " . $conn->error . "</p>";
    }
} else {
    echo "<h2>User Accounts</h2>";
    echo "<p>There are already " . $row['count'] . " user(s) in the database.</p>";
}

// Close connection
$conn->close();

echo "<div style='margin-top: 30px; padding: 15px; background-color: #f0f8ff; border-radius: 5px;'>";
echo "<h2>Setup Complete!</h2>";
echo "<p>Your database has been set up successfully. You can now use the WorkFit application.</p>";
echo "<p><a href='index.php' style='padding: 10px 20px; background-color: #4cc9f0; color: white; text-decoration: none; border-radius: 5px;'>Go to Homepage</a></p>";
echo "</div>";
?>