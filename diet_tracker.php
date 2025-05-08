<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Initialize variables
$success_message = '';
$error_message = '';
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Check for success message from nutrition finder
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = "Food entry added successfully!";
}

// Process add food form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_food'])) {
    // Check if the diet_entries table has the required columns
    $result = $conn->query("SHOW COLUMNS FROM diet_entries LIKE 'quantity'");
    $quantity_exists = $result->num_rows > 0;
    $result = $conn->query("SHOW COLUMNS FROM diet_entries LIKE 'quantity_unit'");
    $quantity_unit_exists = $result->num_rows > 0;
    
    $food_name = trim($_POST['food_name']);
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 1;
    $quantity_unit = isset($_POST['quantity_unit']) ? trim($_POST['quantity_unit']) : 'serving';
    $calories = floatval($_POST['calories']);
    $protein = floatval($_POST['protein']);
    $entry_date = $_POST['entry_date'];
    
    if (empty($food_name)) {
        $error_message = "Food name is required";
    } elseif ($quantity <= 0) {
        $error_message = "Quantity must be greater than zero";
    } else {
        // Format the food name to include quantity if it's not 1 serving
        $display_food_name = $food_name;
        if ($quantity != 1 || $quantity_unit != 'serving') {
            $display_food_name = $food_name . " (" . $quantity . " " . $quantity_unit . ")";
        }
        
        // Use different SQL based on whether the columns exist
        if ($quantity_exists && $quantity_unit_exists) {
            // Insert with quantity columns
            $sql = "INSERT INTO diet_entries (user_id, food_name, quantity, quantity_unit, calories, protein, entry_date) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdsids", $user_id, $display_food_name, $quantity, $quantity_unit, $calories, $protein, $entry_date);
        } else {
            // Insert without quantity columns
            $sql = "INSERT INTO diet_entries (user_id, food_name, calories, protein, entry_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isdds", $user_id, $display_food_name, $calories, $protein, $entry_date);
        }
        
        if ($stmt->execute()) {
            $success_message = "Food entry added successfully!";
            // Redirect to avoid form resubmission
            header("Location: diet_tracker.php?date=" . $entry_date);
            exit;
        } else {
            $error_message = "Failed to add food entry: " . $conn->error;
        }
    }
}

// Process delete food entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_entry'])) {
    $entry_id = $_POST['entry_id'];
    
    // Verify that the entry belongs to the user
    $stmt = $conn->prepare("SELECT id FROM diet_entries WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $entry_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        // Delete the entry
        $stmt = $conn->prepare("DELETE FROM diet_entries WHERE id = ?");
        $stmt->bind_param("i", $entry_id);
        
        if ($stmt->execute()) {
            $success_message = "Food entry deleted successfully!";
            // Redirect to avoid form resubmission
            header("Location: diet_tracker.php?date=" . $selected_date);
            exit;
        } else {
            $error_message = "Failed to delete food entry. Please try again.";
        }
    } else {
        $error_message = "Invalid food entry.";
    }
}

// Process calorie goal update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_calorie_goal'])) {
    $new_calorie_goal = intval($_POST['calorie_goal']);
    
    if ($new_calorie_goal < 500) {
        $error_message = "Calorie goal must be at least 500 calories.";
    } elseif ($new_calorie_goal > 10000) {
        $error_message = "Calorie goal cannot exceed 10,000 calories.";
    } else {
        // Check if calorie_goal column exists
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'calorie_goal'");
        $column_exists = $result->num_rows > 0;
        
        if (!$column_exists) {
            // If column doesn't exist, add it
            $conn->query("ALTER TABLE users ADD COLUMN calorie_goal INT DEFAULT 2000");
        }
        
        // Update the user's calorie goal
        $stmt = $conn->prepare("UPDATE users SET calorie_goal = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_calorie_goal, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Calorie goal updated successfully!";
            $calorie_goal = $new_calorie_goal;
            $weekly_calorie_goal = $calorie_goal * 7;
            // Redirect to avoid form resubmission
            header("Location: diet_tracker.php?date=" . $selected_date);
            exit;
        } else {
            $error_message = "Failed to update calorie goal. Please try again.";
        }
    }
}

// Get food entries for the selected date
$stmt = $conn->prepare("
    SELECT * FROM diet_entries 
    WHERE user_id = ? AND entry_date = ? 
    ORDER BY created_at ASC
");
$stmt->bind_param("is", $user_id, $selected_date);
$stmt->execute();
$food_entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate daily totals
$total_calories = 0;
$total_protein = 0;

foreach ($food_entries as $entry) {
    $total_calories += $entry['calories'];
    $total_protein += $entry['protein'];
}

// Get weekly data
$week_start = date('Y-m-d', strtotime('monday this week', strtotime($selected_date)));
$week_end = date('Y-m-d', strtotime('sunday this week', strtotime($selected_date)));

// Get all entries for the current week
$stmt = $conn->prepare("
    SELECT entry_date, SUM(calories) as daily_calories, SUM(protein) as daily_protein
    FROM diet_entries 
    WHERE user_id = ? AND entry_date BETWEEN ? AND ?
    GROUP BY entry_date
    ORDER BY entry_date ASC
");
$stmt->bind_param("iss", $user_id, $week_start, $week_end);
$stmt->execute();
$weekly_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Initialize arrays for the chart
$week_days = [];
$week_calories = [];
$week_protein = [];
$weekly_total_calories = 0;
$weekly_total_protein = 0;

// Create an array with all days of the week
$current_day = $week_start;
while ($current_day <= $week_end) {
    $day_name = date('D', strtotime($current_day));
    $week_days[$current_day] = $day_name;
    $week_calories[$current_day] = 0;
    $week_protein[$current_day] = 0;
    $current_day = date('Y-m-d', strtotime($current_day . ' +1 day'));
}

// Fill in the data we have
foreach ($weekly_data as $day_data) {
    $week_calories[$day_data['entry_date']] = (int)$day_data['daily_calories'];
    $week_protein[$day_data['entry_date']] = (float)$day_data['daily_protein'];
    $weekly_total_calories += (int)$day_data['daily_calories'];
    $weekly_total_protein += (float)$day_data['daily_protein'];
}

// Calculate daily average
$days_with_entries = count(array_filter($week_calories));
$daily_average_calories = $days_with_entries > 0 ? round($weekly_total_calories / $days_with_entries) : 0;

// Get user's calorie goal if set
$calorie_goal = 2000; // Default goal

// Check if calorie_goal column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'calorie_goal'");
$column_exists = $result->num_rows > 0;

if ($column_exists) {
    // If column exists, get the user's calorie goal
    $stmt = $conn->prepare("SELECT calorie_goal FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['calorie_goal'])) {
            $calorie_goal = (int)$row['calorie_goal'];
        }
    }
} else {
    // If column doesn't exist, add it
    $conn->query("ALTER TABLE users ADD COLUMN calorie_goal INT DEFAULT 2000");
    // Set default value for current user
    $stmt = $conn->prepare("UPDATE users SET calorie_goal = 2000 WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Calculate weekly goal
$weekly_calorie_goal = $calorie_goal * 7;

// Make sure we have valid values
if ($weekly_calorie_goal <= 0) {
    $weekly_calorie_goal = 14000; // Default weekly goal (2000 * 7)
    $calorie_goal = 2000; // Default daily goal
}

// Get recent dates with entries (for date navigation)
$stmt = $conn->prepare("
    SELECT DISTINCT entry_date 
    FROM diet_entries 
    WHERE user_id = ? 
    ORDER BY entry_date DESC 
    LIMIT 7
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet Tracker - WorkFit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        :root {
            --primary: #4cc9f0;
            --secondary: #f72585;
            --dark-bg: #1a1a2e;
            --darker-bg: #16213e;
            --card-bg: rgba(255, 255, 255, 0.05);
            --text: #fff;
            --text-muted: #ccc;
            --success: #4ade80;
            --warning: #fbbf24;
            --danger: #f87171;
        }
        
        body {
            background: linear-gradient(135deg, var(--dark-bg), var(--darker-bg));
            color: var(--text);
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
            padding: 30px 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 0 20px;
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }
        
        .logo span {
            color: var(--secondary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-radius: 10px;
            background: var(--card-bg);
            margin: 0 20px 30px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .user-name {
            font-weight: 500;
            font-size: 14px;
        }
        
        .user-email {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            background: var(--card-bg);
            color: var(--primary);
        }
        
        .nav-link i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--card-bg);
            color: var(--danger);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-btn i {
            margin-right: 10px;
        }
        
        .logout-btn:hover {
            background: rgba(248, 113, 113, 0.2);
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px 0;
            min-height: 100vh;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary), #4361ee);
            color: var(--text);
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #4361ee, var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }
        
        .btn-danger {
            background: var(--danger);
            color: var(--text);
        }
        
        .btn-danger:hover {
            background: #ef4444;
            transform: translateY(-2px);
        }
        
        /* Date Navigation */
        .date-nav {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            background: var(--card-bg);
            border-radius: 15px;
            padding: 15px 20px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .date-nav-title {
            font-size: 18px;
            font-weight: 600;
            margin-right: 20px;
        }
        
        .date-nav-buttons {
            display: flex;
            gap: 10px;
            margin-right: 20px;
        }
        
        .date-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .date-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .date-picker {
            padding: 8px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .date-picker:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        .date-nav-recent {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }
        
        .recent-date {
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .recent-date:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .recent-date.active {
            background: var(--primary);
            color: var(--dark-bg);
            font-weight: 500;
        }
        
        /* Summary Card */
        .summary-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .summary-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .summary-value {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .summary-date {
            font-size: 16px;
            font-weight: normal;
            color: var(--text-muted);
            margin-left: 10px;
        }
        
        .summary-progress {
            margin-top: 10px;
        }
        
        .progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--primary), #4361ee);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .weekly-chart-container {
            margin-top: 20px;
            height: 250px;
        }
        
        .view-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        
        .toggle-btn {
            padding: 8px 15px;
            background: var(--card-bg);
            border: none;
            border-radius: 20px;
            color: var(--text-muted);
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .toggle-btn.active {
            background: var(--primary);
            color: var(--dark-bg);
            font-weight: 500;
        }
        
        /* Calorie Goal Setting */
        .goal-setting {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .goal-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .goal-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .goal-input-group {
            display: flex;
            gap: 10px;
        }
        
        .goal-input-group input {
            flex: 1;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            color: var(--text);
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .goal-input-group input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        .goal-info {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .goal-info i {
            color: var(--primary);
            margin-right: 5px;
        }
        
        /* Feature Notice */
        .feature-notice {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            font-weight: 500;
            animation: pulse 2s infinite;
        }
        
        .feature-notice i.fa-star {
            color: #ffd700;
            margin-right: 10px;
        }
        
        .notice-close {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 16px;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .notice-close:hover {
            opacity: 1;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(76, 201, 240, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(76, 201, 240, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(76, 201, 240, 0);
            }
        }
        
        /* Food Entries */
        .entries-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .entries-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .entries-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .entries-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .entries-table th, .entries-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .entries-table th {
            font-weight: 600;
            color: var(--primary);
        }
        
        .entries-table tr:last-child td {
            border-bottom: none;
        }
        
        .entries-table tr:hover td {
            background: rgba(255, 255, 255, 0.03);
        }
        
        .entry-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.1);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .action-btn.edit:hover {
            color: var(--primary);
        }
        
        .action-btn.delete:hover {
            color: var(--danger);
        }
        
        /* Add Food Form */
        .add-food-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .add-food-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        /* Messages */
        .success-message {
            background: rgba(74, 222, 128, 0.2);
            color: var(--success);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .error-message {
            background: rgba(247, 37, 133, 0.2);
            color: var(--secondary);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 30px 0;
        }
        
        .empty-icon {
            font-size: 48px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }
        
        .empty-text {
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .date-nav {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .date-nav-title {
                margin-bottom: 10px;
            }
            
            .date-nav-recent {
                margin-left: 0;
                margin-top: 10px;
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                width: 40px;
                height: 40px;
                border-radius: 10px;
                background: var(--card-bg);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--text);
                font-size: 20px;
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .mobile-toggle:hover {
                background: rgba(255, 255, 255, 0.1);
            }
            
            .entries-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <div class="mobile-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">Work<span>Fit</span></div>
        </div>
        
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div>
                <div class="user-name"><?php echo $user['name']; ?></div>
                <div class="user-email"><?php echo $user['email']; ?></div>
            </div>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="workout_plans.php" class="nav-link">
                    <i class="fas fa-dumbbell"></i> Workout Plans
                </a>
            </li>
            <li class="nav-item">
                <a href="diet_tracker.php" class="nav-link active">
                    <i class="fas fa-utensils"></i> Diet Tracker
                </a>
            </li>
            <li class="nav-item">
                <a href="progress.php" class="nav-link">
                    <i class="fas fa-chart-line"></i> Progress
                </a>
            </li>
            <li class="nav-item">
                <a href="bmi_calculator.php" class="nav-link">
                    <i class="fas fa-calculator"></i> BMI Calculator
                </a>
            </li>
            <li class="nav-item">
                <a href="exercise_guide.php" class="nav-link">
                    <i class="fas fa-book"></i> Exercise Guide
                </a>
            </li>
            <li class="nav-item">
                <a href="nutrition_finder.php" class="nav-link">
                    <i class="fas fa-apple-alt"></i> Nutrition Finder
                </a>
            </li>
        </ul>
        
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Diet Tracker</h1>
                <a href="nutrition_finder.php" class="btn btn-primary">
                    <i class="fas fa-search"></i> Find Foods
                </a>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- New Feature Notice -->
            <div class="feature-notice">
                <i class="fas fa-star"></i> New Feature: Weekly Calorie Tracking! Use the toggle buttons below to switch between daily and weekly views.
                <button class="notice-close"><i class="fas fa-times"></i></button>
            </div>
            
            <!-- Date Navigation -->
            <div class="date-nav">
                <div class="date-nav-title">
                    <?php echo date('F j, Y', strtotime($selected_date)); ?>
                </div>
                
                <div class="date-nav-buttons">
                    <a href="?date=<?php echo date('Y-m-d', strtotime($selected_date . ' -1 day')); ?>" class="date-btn">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <a href="?date=<?php echo date('Y-m-d'); ?>" class="date-btn">
                        <i class="fas fa-calendar-day"></i>
                    </a>
                    <a href="?date=<?php echo date('Y-m-d', strtotime($selected_date . ' +1 day')); ?>" class="date-btn">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                
                <input type="date" id="date-picker" class="date-picker" value="<?php echo $selected_date; ?>">
                
                <?php if (!empty($recent_dates)): ?>
                    <div class="date-nav-recent">
                        <?php foreach ($recent_dates as $date): ?>
                            <a href="?date=<?php echo $date['entry_date']; ?>" 
                               class="recent-date <?php echo $date['entry_date'] === $selected_date ? 'active' : ''; ?>">
                                <?php echo date('M j', strtotime($date['entry_date'])); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- View Toggle -->
            <div class="view-toggle">
                <button class="toggle-btn active" data-view="daily">Daily View</button>
                <button class="toggle-btn" data-view="weekly">Weekly View</button>
            </div>
            
            <!-- Daily Summary -->
            <div class="summary-card" id="daily-summary">
                <h2 class="summary-title">Daily Summary <span class="summary-date"><?php echo date('F j, Y', strtotime($selected_date)); ?></span></h2>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($total_calories); ?></div>
                        <div class="summary-label">Total Calories</div>
                        <div class="summary-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, ($total_calories / $calorie_goal) * 100); ?>%"></div>
                            </div>
                            <div class="progress-text"><?php echo round(($total_calories / $calorie_goal) * 100); ?>% of daily goal</div>
                        </div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($total_protein, 1); ?>g</div>
                        <div class="summary-label">Total Protein</div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-value"><?php echo count($food_entries); ?></div>
                        <div class="summary-label">Food Entries</div>
                    </div>
                </div>
            </div>
            
            <!-- Weekly Summary -->
            <div class="summary-card" id="weekly-summary" style="display: none;">
                <h2 class="summary-title">Weekly Summary <span class="summary-date"><?php echo date('M j', strtotime($week_start)); ?> - <?php echo date('M j, Y', strtotime($week_end)); ?></span></h2>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($weekly_total_calories); ?></div>
                        <div class="summary-label">Weekly Calories</div>
                        <div class="summary-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, ($weekly_total_calories / $weekly_calorie_goal) * 100); ?>%"></div>
                            </div>
                            <div class="progress-text"><?php echo round(($weekly_total_calories / $weekly_calorie_goal) * 100); ?>% of weekly goal</div>
                        </div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($weekly_total_protein, 1); ?>g</div>
                        <div class="summary-label">Weekly Protein</div>
                    </div>
                    
                    <div class="summary-item">
                        <div class="summary-value"><?php echo number_format($daily_average_calories); ?></div>
                        <div class="summary-label">Daily Average</div>
                    </div>
                </div>
                
                <div class="weekly-chart-container">
                    <canvas id="weeklyCalorieChart"></canvas>
                </div>
                
                <!-- Calorie Goal Setting -->
                <div class="goal-setting">
                    <h3 class="goal-title">Set Your Daily Calorie Goal</h3>
                    <form method="post" class="goal-form">
                        <div class="goal-input-group">
                            <input type="number" name="calorie_goal" id="calorie_goal" min="500" max="10000" step="50" value="<?php echo $calorie_goal; ?>" required>
                            <button type="submit" name="update_calorie_goal" class="btn btn-primary">Update Goal</button>
                        </div>
                        <div class="goal-info">
                            <i class="fas fa-info-circle"></i> Your weekly goal will be calculated as 7 times your daily goal.
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Food Entries -->
            <div class="entries-card">
                <div class="entries-header">
                    <h2 class="entries-title">Food Entries</h2>
                </div>
                
                <?php if (empty($food_entries)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <p class="empty-text">No food entries for this date. Add your first meal below!</p>
                    </div>
                <?php else: ?>
                    <table class="entries-table">
                        <thead>
                            <tr>
                                <th>Food</th>
                                <th>Calories</th>
                                <th>Protein</th>
                                <th>Time Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($food_entries as $entry): ?>
                                <tr>
                                    <td><?php echo $entry['food_name']; ?></td>
                                    <td><?php echo $entry['calories']; ?></td>
                                    <td><?php echo $entry['protein']; ?>g</td>
                                    <td><?php echo date('h:i A', strtotime($entry['created_at'])); ?></td>
                                    <td>
                                        <div class="entry-actions">
                                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                                <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                <button type="submit" name="delete_entry" class="action-btn delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Add Food Form -->
            <div class="add-food-card">
                <h2 class="add-food-title">Add Food Entry</h2>
                
                <form method="post">
                    <input type="hidden" name="entry_date" value="<?php echo $selected_date; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="food_name">Food Name</label>
                            <input type="text" id="food_name" name="food_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control" step="0.1" min="0.1" value="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity_unit">Unit</label>
                            <select id="quantity_unit" name="quantity_unit" class="form-control">
                                <option value="serving">Serving</option>
                                <option value="g">Grams (g)</option>
                                <option value="ml">Milliliters (ml)</option>
                                <option value="cup">Cup</option>
                                <option value="tbsp">Tablespoon</option>
                                <option value="tsp">Teaspoon</option>
                                <option value="piece">Piece</option>
                                <option value="plate">Plate</option>
                                <option value="bowl">Bowl</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="calories">Calories</label>
                            <input type="number" id="calories" name="calories" class="form-control" step="1" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="protein">Protein (g)</label>
                            <input type="number" id="protein" name="protein" class="form-control" step="0.1" min="0" required>
                        </div>
                        
                        <div class="form-group" style="flex: 0 0 auto;">
                            <label>&nbsp;</label>
                            <button type="submit" name="add_food" class="btn btn-primary" style="margin-top: 8px;">
                                <i class="fas fa-plus"></i> Add Food
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Date Picker
        document.getElementById('date-picker').addEventListener('change', function() {
            window.location.href = 'diet_tracker.php?date=' + this.value;
        });
        
        // View Toggle
        const toggleBtns = document.querySelectorAll('.toggle-btn');
        const dailySummary = document.getElementById('daily-summary');
        const weeklySummary = document.getElementById('weekly-summary');
        
        toggleBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                toggleBtns.forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Show/hide appropriate view
                if (this.dataset.view === 'daily') {
                    dailySummary.style.display = 'block';
                    weeklySummary.style.display = 'none';
                } else {
                    dailySummary.style.display = 'none';
                    weeklySummary.style.display = 'block';
                }
            });
        });
        
        // Weekly Calorie Chart
        const weeklyCalorieChart = new Chart(
            document.getElementById('weeklyCalorieChart'),
            {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_values($week_days)); ?>,
                    datasets: [
                        {
                            label: 'Calories',
                            data: <?php echo json_encode(array_values($week_calories)); ?>,
                            backgroundColor: 'rgba(76, 201, 240, 0.6)',
                            borderColor: 'rgba(76, 201, 240, 1)',
                            borderWidth: 1,
                            borderRadius: 5,
                            barThickness: 20
                        },
                        {
                            label: 'Daily Goal',
                            data: Array(7).fill(<?php echo $calorie_goal; ?>),
                            type: 'line',
                            borderColor: 'rgba(255, 99, 132, 0.7)',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: 'rgba(255, 255, 255, 0.7)'
                            }
                        }
                    }
                }
            }
        );
        
        // Quantity and nutrition calculation
        const quantityInput = document.getElementById('quantity');
        const caloriesInput = document.getElementById('calories');
        const proteinInput = document.getElementById('protein');
        
        // Store original values
        let baseCalories = 0;
        let baseProtein = 0;
        let lastQuantity = 1;
        
        // Update nutrition values when quantity changes
        quantityInput.addEventListener('input', function() {
            const newQuantity = parseFloat(this.value) || 0;
            
            // If this is the first change, store the base values
            if (baseCalories === 0 && caloriesInput.value) {
                baseCalories = parseFloat(caloriesInput.value) / lastQuantity;
            }
            
            if (baseProtein === 0 && proteinInput.value) {
                baseProtein = parseFloat(proteinInput.value) / lastQuantity;
            }
            
            // Update the nutrition values based on the new quantity
            if (baseCalories > 0) {
                caloriesInput.value = Math.round(baseCalories * newQuantity);
            }
            
            if (baseProtein > 0) {
                proteinInput.value = (baseProtein * newQuantity).toFixed(1);
            }
            
            lastQuantity = newQuantity;
        });
        
        // Reset base values when food name changes
        document.getElementById('food_name').addEventListener('input', function() {
            baseCalories = 0;
            baseProtein = 0;
            lastQuantity = parseFloat(quantityInput.value) || 1;
        });
        
        // Feature notice close button
        const noticeCloseBtn = document.querySelector('.notice-close');
        if (noticeCloseBtn) {
            noticeCloseBtn.addEventListener('click', function() {
                document.querySelector('.feature-notice').style.display = 'none';
                
                // Store in localStorage to remember the user closed it
                localStorage.setItem('weeklyTrackingNoticeClosed', 'true');
            });
            
            // Check if the notice was previously closed
            if (localStorage.getItem('weeklyTrackingNoticeClosed') === 'true') {
                document.querySelector('.feature-notice').style.display = 'none';
            }
        }
    </script>
    
    <?php include 'includes/assistant.php'; ?>
    
    <!-- Include Theme Changer -->
    <?php include 'includes/theme-changer.php'; ?>
</body>
</html>