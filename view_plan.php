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

// Check if plan ID is provided
if (!isset($_GET['id'])) {
    header("Location: workout_plans.php");
    exit;
}

$plan_id = $_GET['id'];

// Get plan details
$stmt = $conn->prepare("SELECT * FROM workout_plans WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $plan_id, $user_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();

// If plan doesn't exist or doesn't belong to user, redirect
if (!$plan) {
    header("Location: workout_plans.php");
    exit;
}

// Check for success message
$success_message = '';
if (isset($_SESSION['message'])) {
    $success_message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying it
}

// Get exercises for this plan, grouped by day
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$exercises_by_day = [];

foreach ($days_of_week as $day) {
    $stmt = $conn->prepare("SELECT * FROM exercises WHERE plan_id = ? AND day_of_week = ? ORDER BY id ASC");
    $stmt->bind_param("is", $plan_id, $day);
    $stmt->execute();
    $exercises_by_day[$day] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Process form submission for adding a new exercise
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_exercise'])) {
    $exercise_name = trim($_POST['exercise_name']);
    $day_of_week = $_POST['day_of_week'];
    $sets_reps = isset($_POST['sets_reps']) ? trim($_POST['sets_reps']) : null;
    $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : null;
    
    if (!empty($exercise_name) && !empty($day_of_week)) {
        $stmt = $conn->prepare("INSERT INTO exercises (plan_id, exercise_name, sets_reps, duration, day_of_week) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $plan_id, $exercise_name, $sets_reps, $duration, $day_of_week);
        
        if ($stmt->execute()) {
            // Refresh the page to show the new exercise
            header("Location: view_plan.php?id=" . $plan_id);
            exit;
        }
    }
}

// Process form submission for deleting an exercise
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_exercise'])) {
    $exercise_id = $_POST['exercise_id'];
    
    $stmt = $conn->prepare("DELETE FROM exercises WHERE id = ? AND plan_id = ?");
    $stmt->bind_param("ii", $exercise_id, $plan_id);
    
    if ($stmt->execute()) {
        // Refresh the page
        header("Location: view_plan.php?id=" . $plan_id);
        exit;
    }
}

// Process form submission for toggling exercise completion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_complete'])) {
    $exercise_id = $_POST['exercise_id'];
    $is_completed = $_POST['is_completed'];
    
    $stmt = $conn->prepare("UPDATE exercises SET is_completed = ? WHERE id = ? AND plan_id = ?");
    $stmt->bind_param("iii", $is_completed, $exercise_id, $plan_id);
    
    if ($stmt->execute()) {
        // Refresh the page
        header("Location: view_plan.php?id=" . $plan_id);
        exit;
    } else {
        // If there was an error, log it
        error_log("Failed to update exercise completion status: " . $conn->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $plan['plan_name']; ?> - WorkFit</title>
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
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(76, 201, 240, 0.1);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        /* Plan Overview */
        .plan-overview {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .plan-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .plan-stat {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 15px;
            flex: 1;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        /* Workout Schedule */
        .schedule-tabs {
            display: flex;
            background: var(--card-bg);
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .day-tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .day-tab.active {
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 3px solid var(--primary);
            color: var(--primary);
        }
        
        .day-tab:hover:not(.active) {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .day-content {
            display: none;
        }
        
        .day-content.active {
            display: block;
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Exercise List */
        .exercise-list {
            margin-bottom: 30px;
        }
        
        .exercise-item {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .exercise-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .exercise-check {
            margin-right: 15px;
        }
        
        .completion-form {
            margin: 0;
            padding: 0;
        }
        
        .exercise-check input {
            display: none;
        }
        
        .exercise-check label {
            display: block;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: 2px solid var(--primary);
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .exercise-check input:checked + label {
            background: var(--primary);
        }
        
        .exercise-check input:checked + label:after {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: var(--dark-bg);
            font-size: 14px;
        }
        
        .exercise-info {
            flex: 1;
        }
        
        .exercise-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .exercise-details {
            display: flex;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .exercise-detail {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .exercise-detail i {
            margin-right: 5px;
            font-size: 12px;
        }
        
        .exercise-actions {
            display: flex;
            gap: 10px;
        }
        
        /* Empty Day */
        .empty-day {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
        }
        
        .empty-day-icon {
            font-size: 40px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }
        
        .empty-day-text {
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        /* Add Exercise Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        .modal-content {
            background: var(--darker-bg);
            border-radius: 15px;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out forwards;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            color: var(--text);
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive */
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
                cursor: pointer;
                transition: all 0.3s;
            }
            
            .mobile-toggle:hover {
                background: rgba(255, 255, 255, 0.1);
            }
            
            .schedule-tabs {
                flex-wrap: wrap;
            }
            
            .day-tab {
                flex: 0 0 calc(100% / 3);
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
                <a href="workout_plans.php" class="nav-link active">
                    <i class="fas fa-dumbbell"></i> Workout Plans
                </a>
            </li>
            <li class="nav-item">
                <a href="diet_tracker.php" class="nav-link">
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
                <h1 class="page-title"><?php echo $plan['plan_name']; ?></h1>
                <div>
                    <a href="edit_plan.php?id=<?php echo $plan_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Plan
                    </a>
                    <a href="workout_plans.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Back to Plans
                    </a>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php
                // Calculate plan statistics
                $total_exercises = 0;
                $completed_exercises = 0;
                
                foreach ($exercises_by_day as $day => $exercises) {
                    $total_exercises += count($exercises);
                    foreach ($exercises as $exercise) {
                        if ($exercise['is_completed']) {
                            $completed_exercises++;
                        }
                    }
                }
                
                $completion_percentage = $total_exercises > 0 ? round(($completed_exercises / $total_exercises) * 100) : 0;
            ?>
            
            <div class="plan-overview">
                <h2>Plan Overview</h2>
                <div class="plan-stats">
                    <div class="plan-stat">
                        <div class="stat-value"><?php echo $total_exercises; ?></div>
                        <div class="stat-label">Total Exercises</div>
                    </div>
                    <div class="plan-stat">
                        <div class="stat-value"><?php echo $completed_exercises; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="plan-stat">
                        <div class="stat-value"><?php echo $completion_percentage; ?>%</div>
                        <div class="stat-label">Completion</div>
                    </div>
                </div>
            </div>
            
            <h2>Weekly Schedule</h2>
            
            <div class="schedule-tabs">
                <?php foreach ($days_of_week as $index => $day): ?>
                    <div class="day-tab <?php echo $index === 0 ? 'active' : ''; ?>" data-day="<?php echo $day; ?>">
                        <?php echo substr($day, 0, 3); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php foreach ($days_of_week as $index => $day): ?>
                <div class="day-content <?php echo $index === 0 ? 'active' : ''; ?>" id="day-<?php echo $day; ?>">
                    <div class="day-header">
                        <h3><?php echo $day; ?>'s Workout</h3>
                        <button class="btn btn-primary btn-sm add-exercise-btn" data-day="<?php echo $day; ?>">
                            <i class="fas fa-plus"></i> Add Exercise
                        </button>
                    </div>
                    
                    <?php if (empty($exercises_by_day[$day])): ?>
                        <div class="empty-day">
                            <div class="empty-day-icon">
                                <i class="fas fa-dumbbell"></i>
                            </div>
                            <p class="empty-day-text">No exercises scheduled for <?php echo $day; ?>.</p>
                            <button class="btn btn-primary add-exercise-btn" data-day="<?php echo $day; ?>">
                                <i class="fas fa-plus"></i> Add Exercise
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="exercise-list">
                            <?php foreach ($exercises_by_day[$day] as $exercise): ?>
                                <div class="exercise-item">
                                    <div class="exercise-check">
                                        <form method="post" class="completion-form">
                                            <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                                            <input type="hidden" name="is_completed" value="<?php echo $exercise['is_completed'] ? '0' : '1'; ?>">
                                            <input type="hidden" name="toggle_complete" value="1">
                                            <input type="checkbox" id="exercise-<?php echo $exercise['id']; ?>" 
                                                   class="exercise-checkbox" 
                                                   data-id="<?php echo $exercise['id']; ?>"
                                                   <?php echo $exercise['is_completed'] ? 'checked' : ''; ?> 
                                                   onchange="this.form.submit()">
                                            <label for="exercise-<?php echo $exercise['id']; ?>"></label>
                                        </form>
                                    </div>
                                    <div class="exercise-info">
                                        <div class="exercise-name"><?php echo $exercise['exercise_name']; ?></div>
                                        <div class="exercise-details">
                                            <?php if ($exercise['sets_reps']): ?>
                                                <div class="exercise-detail">
                                                    <i class="fas fa-layer-group"></i> <?php echo $exercise['sets_reps']; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($exercise['duration']): ?>
                                                <div class="exercise-detail">
                                                    <i class="fas fa-clock"></i> <?php echo $exercise['duration']; ?> min
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="exercise-actions">
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this exercise?');">
                                            <input type="hidden" name="exercise_id" value="<?php echo $exercise['id']; ?>">
                                            <button type="submit" name="delete_exercise" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Add Exercise Modal -->
    <div class="modal" id="add-exercise-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add New Exercise</h2>
                <button class="modal-close" id="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post">
                <input type="hidden" id="day_of_week" name="day_of_week" value="Monday">
                
                <div class="form-group">
                    <label for="exercise_name">Exercise Name</label>
                    <input type="text" id="exercise_name" name="exercise_name" class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sets_reps">Sets & Reps</label>
                        <input type="text" id="sets_reps" name="sets_reps" class="form-control" placeholder="e.g. 3x12">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" class="form-control" placeholder="e.g. 30">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancel-btn">Cancel</button>
                    <button type="submit" name="add_exercise" class="btn btn-primary">Add Exercise</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Day Tabs
        const dayTabs = document.querySelectorAll('.day-tab');
        const dayContents = document.querySelectorAll('.day-content');
        
        dayTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const day = this.getAttribute('data-day');
                
                // Remove active class from all tabs and contents
                dayTabs.forEach(t => t.classList.remove('active'));
                dayContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById('day-' + day).classList.add('active');
            });
        });
        
        // No JavaScript needed for checkboxes now - using form submit
        
        // Add Exercise Modal
        const modal = document.getElementById('add-exercise-modal');
        const addExerciseBtns = document.querySelectorAll('.add-exercise-btn');
        const modalClose = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('cancel-btn');
        const dayOfWeekInput = document.getElementById('day_of_week');
        
        function openModal(day) {
            dayOfWeekInput.value = day;
            modal.classList.add('active');
        }
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        addExerciseBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const day = this.getAttribute('data-day');
                openModal(day);
            });
        });
        
        modalClose.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>