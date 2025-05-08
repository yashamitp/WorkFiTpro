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

// Get user's workout plans
$stmt = $conn->prepare("SELECT * FROM workout_plans WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$workout_plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if user has any workout plans
if (count($workout_plans) === 0) {
    // Redirect to workout plans page with a message
    $_SESSION['message'] = "You need to create a workout plan first before adding exercises.";
    header("Location: workout_plans.php");
    exit;
}

$error = '';
$success = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $plan_id = $_POST['plan_id'];
    $exercise_name = trim($_POST['exercise_name']);
    $duration = !empty($_POST['duration']) ? $_POST['duration'] : null;
    $sets_reps = !empty($_POST['sets_reps']) ? $_POST['sets_reps'] : null;
    $day_of_week = isset($_POST['day_of_week']) ? $_POST['day_of_week'] : '';
    
    // Validate input
    if (empty($exercise_name) || empty($day_of_week)) {
        $error = "Exercise name and day of the week are required";
    } else {
        // Verify that the plan belongs to the user
        $stmt = $conn->prepare("SELECT id FROM workout_plans WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $plan_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid workout plan";
        } else {
            // Insert the exercise
            $stmt = $conn->prepare("
                INSERT INTO exercises (plan_id, exercise_name, duration, sets_reps, day_of_week) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isiss", $plan_id, $exercise_name, $duration, $sets_reps, $day_of_week);
            
            if ($stmt->execute()) {
                $success = "Exercise added successfully!";
                
                // If this was submitted from exercise_guide.php, redirect to view the plan
                if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'exercise_guide.php') !== false) {
                    $_SESSION['message'] = "Exercise added successfully!";
                    header("Location: view_plan.php?id=" . $plan_id);
                    exit;
                }
                
                // Clear form data
                $plan_id = '';
                $exercise_name = '';
                $duration = '';
                $sets_reps = '';
                $day_of_week = '';
            } else {
                $error = "Failed to add exercise. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Exercise - WorkFit</title>
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
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(76, 201, 240, 0.1);
        }
        
        /* Form Card */
        .form-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .form-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
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
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }
        
        .error-message {
            background: rgba(247, 37, 133, 0.2);
            color: var(--secondary);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background: rgba(74, 222, 128, 0.2);
            color: var(--success);
            padding: 12px 15px;
            border-radius: 10px;
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
            
            .form-row {
                flex-direction: column;
                gap: 0;
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
                <h1 class="page-title">Add Exercise</h1>
                <a href="workout_plans.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Back to Plans
                </a>
            </div>
            
            <div class="form-card">
                <h2 class="form-title">Exercise Details</h2>
                
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-group">
                        <label for="plan_id">Workout Plan</label>
                        <select id="plan_id" name="plan_id" class="form-control" required>
                            <option value="">Select a plan</option>
                            <?php foreach ($workout_plans as $plan): ?>
                                <option value="<?php echo $plan['id']; ?>" <?php echo isset($plan_id) && $plan_id == $plan['id'] ? 'selected' : ''; ?>>
                                    <?php echo $plan['plan_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="exercise_name">Exercise Name</label>
                        <input type="text" id="exercise_name" name="exercise_name" class="form-control" value="<?php echo isset($exercise_name) ? $exercise_name : ''; ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="duration">Duration (minutes)</label>
                            <input type="number" id="duration" name="duration" class="form-control" value="<?php echo isset($duration) ? $duration : ''; ?>" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="sets_reps">Sets/Reps (e.g., 3x12)</label>
                            <input type="text" id="sets_reps" name="sets_reps" class="form-control" value="<?php echo isset($sets_reps) ? $sets_reps : ''; ?>" placeholder="e.g., 3x12">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="day_of_week">Day of the Week</label>
                        <select id="day_of_week" name="day_of_week" class="form-control" required>
                            <option value="">Select a day</option>
                            <option value="Monday" <?php echo isset($day_of_week) && $day_of_week == 'Monday' ? 'selected' : ''; ?>>Monday</option>
                            <option value="Tuesday" <?php echo isset($day_of_week) && $day_of_week == 'Tuesday' ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="Wednesday" <?php echo isset($day_of_week) && $day_of_week == 'Wednesday' ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="Thursday" <?php echo isset($day_of_week) && $day_of_week == 'Thursday' ? 'selected' : ''; ?>>Thursday</option>
                            <option value="Friday" <?php echo isset($day_of_week) && $day_of_week == 'Friday' ? 'selected' : ''; ?>>Friday</option>
                            <option value="Saturday" <?php echo isset($day_of_week) && $day_of_week == 'Saturday' ? 'selected' : ''; ?>>Saturday</option>
                            <option value="Sunday" <?php echo isset($day_of_week) && $day_of_week == 'Sunday' ? 'selected' : ''; ?>>Sunday</option>
                        </select>
                    </div>
                    
                    <div class="form-footer">
                        <a href="workout_plans.php" class="btn btn-outline">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Exercise</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Check if there's an exercise from the Exercise Guide
        document.addEventListener('DOMContentLoaded', function() {
            // Check for full exercise data
            const selectedExercise = sessionStorage.getItem('selected_exercise');
            if (selectedExercise) {
                const exercise = JSON.parse(selectedExercise);
                document.getElementById('exercise_name').value = exercise.name;
                // Clear the session storage
                sessionStorage.removeItem('selected_exercise');
            }
            
            // Check for just the exercise name (from modal)
            const selectedExerciseName = sessionStorage.getItem('selected_exercise_name');
            if (selectedExerciseName) {
                document.getElementById('exercise_name').value = selectedExerciseName;
                // Clear the session storage
                sessionStorage.removeItem('selected_exercise_name');
            }
        });
    </script>
</body>
</html>