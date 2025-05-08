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

// Redirect to login if user data not found
if (!$user) {
    // Clear the session as it might be invalid
    session_unset();
    session_destroy();
    header("Location: login.php?error=invalid_user");
    exit;
}

// Get user's workout plans
$stmt = $conn->prepare("SELECT * FROM workout_plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$workout_plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's exercises
$today = date('l'); // Current day of the week
$exercises_today = [];

foreach ($workout_plans as $plan) {
    $stmt = $conn->prepare("SELECT * FROM exercises WHERE plan_id = ? AND day_of_week = ? ORDER BY id ASC");
    $stmt->bind_param("is", $plan['id'], $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($exercise = $result->fetch_assoc()) {
        $exercise['plan_name'] = $plan['plan_name'];
        $exercises_today[] = $exercise;
    }
}

// Get weekly progress
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$weekly_progress = [];

foreach ($days_of_week as $day) {
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
    
    $weekly_progress[$day] = [
        'total' => (int)$result['total'],
        'completed' => (int)$result['completed'],
        'percentage' => $result['total'] > 0 ? round(($result['completed'] / $result['total']) * 100) : 0
    ];
}

// Get recent diet entries
$stmt = $conn->prepare("
    SELECT * FROM diet_entries 
    WHERE user_id = ? 
    ORDER BY entry_date DESC, created_at DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_diet_entries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user goals
$stmt = $conn->prepare("
    SELECT * FROM goals 
    WHERE user_id = ? 
    ORDER BY is_achieved ASC, target_date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$goals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get latest BMI record
$stmt = $conn->prepare("
    SELECT * FROM progress_records 
    WHERE user_id = ? 
    ORDER BY record_date DESC 
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$latest_bmi = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WorkFit</title>
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
            /* background: var(--primary); */
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
        
        .greeting {
            font-size: 24px;
            font-weight: 600;
        }
        
        .date {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
        }
        
        .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: rgba(76, 201, 240, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 20px;
        }
        
        .card-content {
            margin-bottom: 15px;
        }
        
        .stat {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        
        .trend {
            display: flex;
            align-items: center;
        }
        
        .trend.up {
            color: var(--success);
        }
        
        .trend.down {
            color: var(--danger);
        }
        
        .trend i {
            margin-right: 5px;
        }
        
        /* Today's Workout Section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
        }
        
        .btn i {
             margin-right: 5px; 
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary), #4361ee);
            color: var(--text);
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #4361ee, var(--primary));
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
        
        .workout-list {
            margin-bottom: 30px;
        }
        
        .workout-item {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .workout-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }
        
        .workout-check {
            margin-right: 15px;
        }
        
        .workout-check input {
            display: none;
        }
        
        .workout-check label {
            display: block;
            width: 24px;
            height: 24px;
            border-radius: 6px;
            border: 2px solid var(--primary);
            position: relative;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .workout-check input:checked + label {
            background: var(--primary);
        }
        
        .workout-check input:checked + label:after {
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
        
        .workout-info {
            flex: 1;
        }
        
        .workout-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .workout-details {
            display: flex;
            font-size: 14px;
            color: var(--text-muted);
        }
        
        .workout-detail {
            display: flex;
            align-items: center;
            margin-right: 15px;
        }
        
        .workout-detail i {
            margin-right: 5px;
            font-size: 12px;
        }
        
        .workout-actions {
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
        
        /* Weekly Progress */
        .progress-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .progress-day {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        
        .day-name {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .progress-bar {
            width: 100%;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress-fill {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, var(--primary), #4361ee);
            border-radius: 10px;
            transition: height 0.5s;
        }
        
        .progress-percentage {
            font-size: 14px;
            font-weight: 600;
        }
        
        /* Stopwatch */
        .stopwatch {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .stopwatch-display {
            font-size: 48px;
            font-weight: 700;
            margin: 20px 0;
            font-family: monospace;
        }
        
        .stopwatch-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .stopwatch-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .start-btn {
            background: var(--success);
            color: #fff;
        }
        
        .pause-btn {
            background: var(--warning);
            color: #fff;
        }
        
        .reset-btn {
            background: var(--danger);
            color: #fff;
        }
        
        .stopwatch-btn:hover {
            transform: scale(1.1);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .progress-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }
            
            .progress-day:nth-child(n+5) {
                margin-top: 15px;
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
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .progress-grid {
                grid-template-columns: repeat(2, 1fr);
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
        
        .card, .workout-item, .progress-day, .stopwatch {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .dashboard-grid .card:nth-child(1) {
            animation-delay: 0.1s;
        }
        
        .dashboard-grid .card:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .dashboard-grid .card:nth-child(3) {
            animation-delay: 0.3s;
        }
        
        /* Chat Bot */
        .chat-bot-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), #4361ee);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
            z-index: 999;
            transition: all 0.3s;
        }
        
        .chat-bot-toggle:hover {
            transform: scale(1.1);
        }
        
        .chat-bot {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 350px;
            height: 500px;
            background: var(--dark-bg);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 998;
            overflow: hidden;
            display: none;
            flex-direction: column;
            animation: slideUp 0.3s ease-out forwards;
        }
        
        .chat-header {
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .chat-title {
            display: flex;
            align-items: center;
        }
        
        .chat-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .chat-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 18px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .chat-close:hover {
            color: var(--danger);
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .message {
            margin-bottom: 15px;
            max-width: 80%;
        }
        
        .message-content {
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
        }
        
        .bot-message {
            align-self: flex-start;
        }
        
        .bot-message .message-content {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .user-message {
            margin-left: auto;
        }
        
        .user-message .message-content {
            background: var(--primary);
        }
        
        .chat-input {
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
        }
        
        .chat-input input {
            flex: 1;
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 20px;
            color: var(--text);
            font-size: 14px;
        }
        
        .chat-input input:focus {
            outline: none;
        }
        
        .chat-input button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: var(--primary);
            color: var(--dark-bg);
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .chat-input button:hover {
            background: #4361ee;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
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
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="workout_plans.php" class="nav-link">
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
                <div>
                    <h1 class="greeting">Hello, <?php echo $user['name']; ?>!</h1>
                    <p class="date"><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
            
            <!-- Dashboard Stats -->
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Workout Plans</h3>
                        <div class="card-icon">
                            <i class="fas fa-dumbbell"></i>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="stat"><?php echo count($workout_plans); ?></div>
                        <div class="stat-label">Active Plans</div>
                    </div>
                    <div class="card-footer">
                        <a href="workout_plans.php" class="btn btn-outline btn-sm">View All</a>
                        <div class="trend up">
                            <i class="fas fa-arrow-up"></i> 12% from last week
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Today's Exercises</h3>
                        <div class="card-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <div class="card-content">
                        <div class="stat"><?php echo count($exercises_today); ?></div>
                        <div class="stat-label">Scheduled for Today</div>
                    </div>
                    <div class="card-footer">
                        <a href="#today-workout" class="btn btn-outline btn-sm">View All</a>
                        <div class="trend up">
                            <i class="fas fa-check"></i> 
                            <?php 
                                $completed = 0;
                                foreach ($exercises_today as $exercise) {
                                    if ($exercise['is_completed']) $completed++;
                                }
                                echo $completed . '/' . count($exercises_today) . ' completed';
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Weekly Progress</h3>
                        <div class="card-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-content">
                        <?php
                            $total_exercises = 0;
                            $total_completed = 0;
                            
                            foreach ($weekly_progress as $day) {
                                $total_exercises += $day['total'];
                                $total_completed += $day['completed'];
                            }
                            
                            $weekly_percentage = $total_exercises > 0 ? round(($total_completed / $total_exercises) * 100) : 0;
                        ?>
                        <div class="stat"><?php echo $weekly_percentage; ?>%</div>
                        <div class="stat-label">Completion Rate</div>
                    </div>
                    <div class="card-footer">
                        <a href="#weekly-progress" class="btn btn-outline btn-sm">View Details</a>
                        <div class="trend <?php echo $weekly_percentage >= 50 ? 'up' : 'down'; ?>">
                            <i class="fas fa-<?php echo $weekly_percentage >= 50 ? 'arrow-up' : 'arrow-down'; ?>"></i> 
                            <?php echo $total_completed; ?>/<?php echo $total_exercises; ?> exercises
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Today's Workout -->
            <div id="today-workout" class="section">
                <div class="section-header">
                    <h2 class="section-title">Today's Workout</h2>
                    <a href="add_exercise.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Exercise
                    </a>
                </div>
                
                <div class="workout-list">
                    <?php if (empty($exercises_today)): ?>
                        <div class="empty-state">
                            <p>No exercises scheduled for today. Add some exercises to get started!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($exercises_today as $exercise): ?>
                            <div class="workout-item">
                                <div class="workout-check">
                                    <input type="checkbox" id="exercise-<?php echo $exercise['id']; ?>" 
                                           <?php echo $exercise['is_completed'] ? 'checked' : ''; ?> 
                                           onchange="updateExerciseStatus(<?php echo $exercise['id']; ?>, this.checked)">
                                    <label for="exercise-<?php echo $exercise['id']; ?>"></label>
                                </div>
                                <div class="workout-info">
                                    <div class="workout-name"><?php echo $exercise['exercise_name']; ?></div>
                                    <div class="workout-details">
                                        <div class="workout-detail">
                                            <i class="fas fa-layer-group"></i> <?php echo $exercise['plan_name']; ?>
                                        </div>
                                        <?php if ($exercise['duration']): ?>
                                            <div class="workout-detail">
                                                <i class="fas fa-clock"></i> <?php echo $exercise['duration']; ?> min
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($exercise['sets_reps']): ?>
                                            <div class="workout-detail">
                                                <i class="fas fa-redo"></i> <?php echo $exercise['sets_reps']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="workout-actions">
                                    <button class="action-btn edit" onclick="editExercise(<?php echo $exercise['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete" onclick="deleteExercise(<?php echo $exercise['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Weekly Progress -->
            <div id="weekly-progress" class="section">
                <div class="section-header">
                    <h2 class="section-title">Weekly Progress</h2>
                </div>
                
                <div class="progress-grid">
                    <?php foreach ($days_of_week as $day): ?>
                        <div class="progress-day">
                            <div class="day-name"><?php echo substr($day, 0, 3); ?></div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="height: <?php echo $weekly_progress[$day]['percentage']; ?>%;"></div>
                            </div>
                            <div class="progress-percentage"><?php echo $weekly_progress[$day]['percentage']; ?>%</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Stopwatch -->
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Workout Timer</h2>
                </div>
                
                <div class="stopwatch">
                    <div class="stopwatch-display" id="stopwatch-display">00:00:00</div>
                    <div class="stopwatch-controls">
                        <button class="stopwatch-btn start-btn" id="start-btn">
                            <i class="fas fa-play"></i>
                        </button>
                        <button class="stopwatch-btn pause-btn" id="pause-btn" disabled>
                            <i class="fas fa-pause"></i>
                        </button>
                        <button class="stopwatch-btn reset-btn" id="reset-btn" disabled>
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chat Bot -->
    
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Exercise Status Update
        function updateExerciseStatus(exerciseId, isCompleted) {
            fetch('update_exercise.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `exercise_id=${exerciseId}&is_completed=${isCompleted ? 1 : 0}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the weekly progress bar
                    const dayName = '<?php echo date('l'); ?>';
                    const dayElement = Array.from(document.querySelectorAll('.day-name')).find(el => el.textContent === dayName.substring(0, 3));
                    if (dayElement) {
                        const progressDay = dayElement.closest('.progress-day');
                        const progressFill = progressDay.querySelector('.progress-fill');
                        const progressPercentage = progressDay.querySelector('.progress-percentage');
                        
                        // Fetch updated percentage
                        fetch('get_day_progress.php?day=' + dayName)
                            .then(response => response.json())
                            .then(data => {
                                progressFill.style.height = data.percentage + '%';
                                progressPercentage.textContent = data.percentage + '%';
                            });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        
        // Edit Exercise
        function editExercise(exerciseId) {
            window.location.href = `edit_exercise.php?id=${exerciseId}`;
        }
        
        // Delete Exercise
        function deleteExercise(exerciseId) {
            if (confirm('Are you sure you want to delete this exercise?')) {
                fetch('delete_exercise.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `exercise_id=${exerciseId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the exercise from the DOM
                        const exerciseElement = document.querySelector(`#exercise-${exerciseId}`).closest('.workout-item');
                        exerciseElement.remove();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
        
        // Stopwatch Functionality
        let stopwatchInterval;
        let stopwatchRunning = false;
        let stopwatchTime = 0;
        
        const stopwatchDisplay = document.getElementById('stopwatch-display');
        const startBtn = document.getElementById('start-btn');
        const pauseBtn = document.getElementById('pause-btn');
        const resetBtn = document.getElementById('reset-btn');
        
        function formatTime(time) {
            const hours = Math.floor(time / 3600).toString().padStart(2, '0');
            const minutes = Math.floor((time % 3600) / 60).toString().padStart(2, '0');
            const seconds = Math.floor(time % 60).toString().padStart(2, '0');
            return `${hours}:${minutes}:${seconds}`;
        }
        
        function updateStopwatch() {
            stopwatchTime++;
            stopwatchDisplay.textContent = formatTime(stopwatchTime);
        }
        
        startBtn.addEventListener('click', function() {
            if (!stopwatchRunning) {
                stopwatchInterval = setInterval(updateStopwatch, 1000);
                stopwatchRunning = true;
                startBtn.disabled = true;
                pauseBtn.disabled = false;
                resetBtn.disabled = false;
            }
        });
        
        pauseBtn.addEventListener('click', function() {
            if (stopwatchRunning) {
                clearInterval(stopwatchInterval);
                stopwatchRunning = false;
                startBtn.disabled = false;
                pauseBtn.disabled = true;
            }
        });
        
        resetBtn.addEventListener('click', function() {
            clearInterval(stopwatchInterval);
            stopwatchRunning = false;
            stopwatchTime = 0;
            stopwatchDisplay.textContent = formatTime(stopwatchTime);
            startBtn.disabled = false;
            pauseBtn.disabled = true;
            resetBtn.disabled = true;
        });
        
        
           
        
      
    </script>
    
    <?php include 'includes/assistant.php'; ?>
    
    <!-- Include Theme Changer -->
    <?php include 'includes/theme-changer.php'; ?>
</body>
</html>