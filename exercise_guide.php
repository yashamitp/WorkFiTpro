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
$exercises = null;
$error = '';
$selected_muscle = '';

// Process search form
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['muscle'])) {
    $selected_muscle = trim($_GET['muscle']);
    
    if (empty($selected_muscle)) {
        $error = "Please select a muscle group";
    } else {
        // RapidAPI key (in a real application, store this securely)
        $api_key = "40e819cafcmshbb90df3af6c5dabp168983jsn8cbaba0e7c36";
        
        // API endpoint
        $url = "https://exercisedb.p.rapidapi.com/exercises/bodyPart/" . urlencode($selected_muscle) . "?limit=10&offset=0";
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-RapidAPI-Key: " . $api_key,
            "X-RapidAPI-Host: exercisedb.p.rapidapi.com"
        ]);
        
        // Execute cURL session and get the response
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $error = "API request failed: " . curl_error($ch);
        } else {
            // Close cURL session
            curl_close($ch);
            
            // Decode JSON response
            $exercises = json_decode($response, true);
            
            // Check if exercises were found
            if (!$exercises || count($exercises) === 0) {
                $error = "No exercises found for the selected muscle group. Please try a different selection.";
                $exercises = null;
            }
        }
    }
}

// List of body parts (as per the ExerciseDB API)
$muscle_groups = [
    'back', 'cardio', 'chest', 'lower arms', 'lower legs', 
    'neck', 'shoulders', 'upper arms', 'upper legs', 'waist'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exercise Guide - WorkFit</title>
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
            --dropdown:#4d9393;
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
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .page-description {
            color: var(--text-muted);
            line-height: 1.6;
        }
        
        /* Search Form */
        .search-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
        }
        
        .search-select {
            flex: 1;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            color: var(--dropdown);
            font-size: 16px;
            transition: all 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23ffffff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .search-select:focus {
            outline: none;
            background-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(76, 201, 240, 0.3);
        }
        
        .btn {
            padding: 12px 20px;
            border-radius: 10px;
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
        
        /* Results */
        .results-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .results-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .results-title i {
            margin-right: 10px;
            color: var(--primary);
        }
        
        .exercise-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .exercise-card {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .exercise-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .exercise-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .exercise-content {
            padding: 20px;
        }
        
        .exercise-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .exercise-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .exercise-detail {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 12px;
            display: flex;
            align-items: center;
        }
        
        .exercise-detail i {
            margin-right: 5px;
            font-size: 10px;
        }
        
        .exercise-instructions {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            max-height: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .exercise-instructions::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background: linear-gradient(to bottom, transparent, rgba(26, 26, 46, 0.9));
        }
        
        .exercise-actions {
            margin-top: 15px;
            display: flex;
            justify-content: space-between;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background: rgba(76, 201, 240, 0.1);
        }
        
        /* Error Message */
        .error-message {
            background: rgba(247, 37, 133, 0.2);
            color: var(--secondary);
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        /* Modal */
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
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
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
            font-size: 24px;
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger);
        }
        
        .modal-body {
            margin-bottom: 20px;
        }
        
        .exercise-images {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            overflow-x: auto;
            padding-bottom: 10px;
        }
        
        .exercise-images img {
            width: 300px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .exercise-video {
            margin-bottom: 20px;
        }
        
        .video-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--primary);
        }
        
        .video-wrapper {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .exercise-info {
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 10px;
        }
        
        .info-label {
            width: 120px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .info-value {
            flex: 1;
        }
        
        .exercise-steps {
            margin-bottom: 20px;
        }
        
        .steps-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .steps-list {
            list-style-position: inside;
            padding-left: 10px;
        }
        
        .steps-list li {
            margin-bottom: 10px;
            line-height: 1.6;
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
            
            .search-form {
                flex-direction: column;
            }
            
            .exercise-grid {
                grid-template-columns: 1fr;
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
                <a href="exercise_guide.php" class="nav-link active">
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
                <h1 class="page-title">Exercise Guide</h1>
                <p class="page-description">Explore exercises by muscle group to learn proper form and technique.</p>
            </div>
            
            <div class="search-card">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="get" class="search-form">
                    <select name="muscle" class="search-select" required>
                        <option value="">Select a muscle group</option>
                        <?php foreach ($muscle_groups as $muscle): ?>
                            <option value="<?php echo $muscle; ?>" <?php echo $selected_muscle === $muscle ? 'selected' : ''; ?>>
                                <?php echo ucfirst($muscle); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Find Exercises
                    </button>
                </form>
            </div>
            
            <?php if ($exercises && count($exercises) > 0): ?>
                <div class="results-card">
                    <h2 class="results-title">
                        <i class="fas fa-dumbbell"></i> Exercises for <?php echo ucfirst($selected_muscle); ?>
                    </h2>
                    
                    <div class="exercise-grid">
                        <?php foreach (array_slice($exercises, 0, 12) as $exercise): ?>
                            <div class="exercise-card">
                                <img src="<?php echo $exercise['gifUrl']; ?>" alt="<?php echo $exercise['name']; ?>" class="exercise-image">
                                <div class="exercise-content">
                                    <h3 class="exercise-name"><?php echo $exercise['name']; ?></h3>
                                    
                                    <div class="exercise-details">
                                        <div class="exercise-detail">
                                            <i class="fas fa-bullseye"></i> <?php echo ucfirst($exercise['target']); ?>
                                        </div>
                                        <div class="exercise-detail">
                                            <i class="fas fa-dumbbell"></i> <?php echo ucfirst($exercise['equipment']); ?>
                                        </div>
                                        <div class="exercise-detail">
                                            <i class="fas fa-layer-group"></i> <?php echo ucfirst($exercise['bodyPart']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="exercise-actions">
                                        <button type="button" class="btn btn-primary btn-sm view-exercise" 
                                                data-exercise="<?php echo htmlspecialchars(json_encode($exercise)); ?>">
                                            <i class="fas fa-eye"></i> View Details
                                        </button>
                                        <button type="button" class="btn btn-outline btn-sm add-to-plan">
                                            <i class="fas fa-plus"></i> Add to Plan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Exercise Details Modal -->
    <div class="modal" id="exercise-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="exercise-title">Exercise Details</h2>
                <button class="modal-close" id="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <div class="exercise-images">
                    <img id="exercise-gif" src="" alt="Exercise demonstration">
                </div>
                
                <!-- Video section (will be shown for certain exercises) -->
                <div class="exercise-video" id="exercise-video-container" style="display: none; margin-top: 20px;">
                    <h3 class="video-title">Video Demonstration:</h3>
                    <div class="video-wrapper" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; margin-top: 10px;">
                        <iframe id="exercise-video-iframe" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>
                
                <div class="exercise-info">
                    <div class="info-item">
                        <div class="info-label">Target Muscle:</div>
                        <div class="info-value" id="exercise-target"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Equipment:</div>
                        <div class="info-value" id="exercise-equipment"></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Body Part:</div>
                        <div class="info-value" id="exercise-bodypart"></div>
                    </div>
                </div>
                
                <div class="exercise-steps">
                    <h3 class="steps-title">How to perform:</h3>
                    <ol class="steps-list" id="exercise-instructions">
                        <!-- Instructions will be inserted here -->
                    </ol>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="close-btn">Close</button>
                <button type="button" class="btn btn-primary" id="add-exercise-btn">
                    <i class="fas fa-plus"></i> Add to Workout Plan
                </button>
            </div>
        </div>
    </div>
    
    <!-- Add to Plan Modal -->
    <div class="modal" id="add-plan-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Add to Workout Plan</h2>
                <button class="modal-close" id="plan-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="add-to-plan-form" action="add_exercise.php" method="post">
                    <input type="hidden" id="modal-exercise-name" name="exercise_name">
                    
                    <div class="form-group">
                        <label for="modal-plan-id">Select Workout Plan</label>
                        <select id="modal-plan-id" name="plan_id" class="form-control" required>
                            <option value="">Select a plan</option>
                            <!-- Plans will be loaded via AJAX -->
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal-duration">Duration (minutes)</label>
                            <input type="number" id="modal-duration" name="duration" class="form-control" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="modal-sets-reps">Sets/Reps (e.g., 3x12)</label>
                            <input type="text" id="modal-sets-reps" name="sets_reps" class="form-control" placeholder="e.g., 3x12">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal-day-of-week">Day of the Week</label>
                        <select id="modal-day-of-week" name="day_of_week" class="form-control" required>
                            <option value="">Select a day</option>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" id="cancel-add-plan">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirm-add-plan">
                    <i class="fas fa-plus"></i> Add Exercise
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Modal Functionality
        const modal = document.getElementById('exercise-modal');
        const modalClose = document.getElementById('modal-close');
        const closeBtn = document.getElementById('close-btn');
        const exerciseTitle = document.getElementById('exercise-title');
        const exerciseGif = document.getElementById('exercise-gif');
        const exerciseTarget = document.getElementById('exercise-target');
        const exerciseEquipment = document.getElementById('exercise-equipment');
        const exerciseBodypart = document.getElementById('exercise-bodypart');
        const exerciseInstructions = document.getElementById('exercise-instructions');
        const exerciseVideoContainer = document.getElementById('exercise-video-container');
        const exerciseVideoIframe = document.getElementById('exercise-video-iframe');
        
        // Add event listeners to all "View Details" buttons
        document.querySelectorAll('.view-exercise').forEach(button => {
            button.addEventListener('click', function() {
                const exercise = JSON.parse(this.getAttribute('data-exercise'));
                
                // Populate modal with exercise data
                exerciseTitle.textContent = exercise.name;
                exerciseGif.src = exercise.gifUrl;
                exerciseGif.alt = exercise.name;
                exerciseTarget.textContent = ucfirst(exercise.target);
                exerciseEquipment.textContent = ucfirst(exercise.equipment);
                exerciseBodypart.textContent = ucfirst(exercise.bodyPart);
                
                // Clear previous instructions
                exerciseInstructions.innerHTML = '';
                
                // Add generic instructions (since the API doesn't provide detailed steps)
                const instructions = getGenericInstructions(exercise);
                instructions.forEach(instruction => {
                    const li = document.createElement('li');
                    li.textContent = instruction;
                    exerciseInstructions.appendChild(li);
                });
                
                // Handle video display
                if (exercise.bodyPart === 'upper arms') {
                    // Show video container
                    exerciseVideoContainer.style.display = 'block';
                    
                    // Get appropriate video based on exercise name and equipment
                    const videoUrl = getExerciseVideoUrl(exercise.name, exercise.equipment);
                    exerciseVideoIframe.src = videoUrl;
                } else {
                    // Hide video container for other body parts
                    exerciseVideoContainer.style.display = 'none';
                    exerciseVideoIframe.src = '';
                }
                
                // Show modal
                modal.classList.add('active');
            });
        });
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        modalClose.addEventListener('click', closeModal);
        closeBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
        
        // Add to Plan Modal
        const addPlanModal = document.getElementById('add-plan-modal');
        const planModalClose = document.getElementById('plan-modal-close');
        const cancelAddPlan = document.getElementById('cancel-add-plan');
        const confirmAddPlan = document.getElementById('confirm-add-plan');
        const modalExerciseName = document.getElementById('modal-exercise-name');
        const modalPlanId = document.getElementById('modal-plan-id');
        
        // Function to fetch workout plans
        async function fetchWorkoutPlans() {
            try {
                const response = await fetch('get_workout_plans.php');
                if (!response.ok) {
                    throw new Error('Failed to fetch workout plans');
                }
                const plans = await response.json();
                
                // Clear existing options
                modalPlanId.innerHTML = '<option value="">Select a plan</option>';
                
                // Add plans to select
                plans.forEach(plan => {
                    const option = document.createElement('option');
                    option.value = plan.id;
                    option.textContent = plan.plan_name;
                    modalPlanId.appendChild(option);
                });
                
                return plans.length > 0;
            } catch (error) {
                console.error('Error fetching workout plans:', error);
                return false;
            }
        }
        
        // Function to open add to plan modal
        async function openAddPlanModal(exerciseName) {
            modalExerciseName.value = exerciseName;
            
            // Fetch workout plans
            const hasPlans = await fetchWorkoutPlans();
            
            if (!hasPlans) {
                alert('You need to create a workout plan first. Redirecting to workout plans page...');
                window.location.href = 'workout_plans.php';
                return;
            }
            
            // Show modal
            addPlanModal.classList.add('active');
        }
        
        function closeAddPlanModal() {
            addPlanModal.classList.remove('active');
        }
        
        // Add to Plan button functionality
        document.querySelectorAll('.add-to-plan').forEach(button => {
            button.addEventListener('click', function() {
                // Get the exercise data from the parent card
                const exerciseCard = this.closest('.exercise-card');
                const viewDetailsBtn = exerciseCard.querySelector('.view-exercise');
                const exercise = JSON.parse(viewDetailsBtn.getAttribute('data-exercise'));
                
                // Open add to plan modal
                openAddPlanModal(exercise.name);
            });
        });
        
        // Add Exercise button in modal
        document.getElementById('add-exercise-btn').addEventListener('click', function() {
            // Get the exercise data from the modal
            const exerciseTitle = document.getElementById('exercise-title').textContent;
            
            // Close exercise details modal
            closeModal();
            
            // Open add to plan modal
            openAddPlanModal(exerciseTitle);
        });
        
        // Close add plan modal
        planModalClose.addEventListener('click', closeAddPlanModal);
        cancelAddPlan.addEventListener('click', closeAddPlanModal);
        
        // Confirm add to plan
        confirmAddPlan.addEventListener('click', function() {
            // Submit the form
            document.getElementById('add-to-plan-form').submit();
        });
        
        // Helper function to capitalize first letter
        function ucfirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        
        // Helper function to generate generic instructions based on exercise type
        function getGenericInstructions(exercise) {
            const instructions = [];
            
            // Basic setup instruction
            instructions.push(`Set up the ${exercise.equipment} for the exercise.`);
            
            // Body position instruction
            if (exercise.bodyPart === 'upper legs' || exercise.bodyPart === 'lower legs') {
                instructions.push('Position your feet shoulder-width apart and maintain proper posture.');
            } else if (exercise.bodyPart === 'chest' || exercise.bodyPart === 'back') {
                instructions.push('Position yourself with a stable base and engage your core muscles.');
            } else if (exercise.bodyPart === 'shoulders' || exercise.bodyPart === 'upper arms') {
                instructions.push('Stand or sit with a straight back and shoulders pulled back.');
            }
            
            // Movement instruction
            instructions.push(`Perform the movement slowly and with control, focusing on the ${exercise.target} muscle.`);
            
            // Breathing instruction
            instructions.push('Remember to breathe properly: exhale during exertion and inhale during the return phase.');
            
            // Rep instruction
            instructions.push('Complete the desired number of repetitions with proper form before resting.');
            
            return instructions;
        }
        
        // Helper function to get video URL for upper arms exercises
        function getExerciseVideoUrl(exerciseName, equipment) {
            // Map of exercise names to YouTube video IDs
            const videoMap = {
                // Bicep exercises
                'barbell curl': 'kwG2ipFRgfo',
                'dumbbell alternate bicep curl': 'sAq_ocpRh_I',
                'dumbbell bicep curl': 'ykJmrZ5v0Oo',
                'hammer curl': 'TwD-YGVP4Bk',
                'cable bicep curl': 'NFzTWp2qpiE',
                'concentration curl': 'Jvj2wV0vOYU',
                'preacher curl': 'fIWP-FRFNU0',
                
                // Tricep exercises
                'triceps dip': 'wjUmnZH528Y',
                'close-grip bench press': 'nEF0bv2FW94',
                'triceps pushdown': 'HIKQVeM-Qvo',
                'overhead triceps extension': '9w-1W1xwpXE',
                'skull crusher': '1BDdYcKQp1w',
                'diamond push up': 'J0DnG1_S92I',
                'bench dip': 'c3ZGl4pAwZ4',
                
                // Generic upper arm exercises by equipment
                'barbell': 'iDuKFfr8RJI',
                'dumbbell': 'XdWS4XdK_1k',
                'cable': 'fPEcpFVkrNQ',
                'body weight': 'dhWjU4lYGHI',
                'leverage machine': 'WJm9zA2NY8E',
                'band': 'U8Li-DUMjwY'
            };
            
            // Convert exercise name to lowercase for matching
            const exerciseNameLower = exerciseName.toLowerCase();
            
            // Try to find a specific video for the exercise
            if (videoMap[exerciseNameLower]) {
                return `https://www.youtube.com/embed/${videoMap[exerciseNameLower]}`;
            }
            
            // If no specific video is found, try to find one based on equipment
            if (videoMap[equipment.toLowerCase()]) {
                return `https://www.youtube.com/embed/${videoMap[equipment.toLowerCase()]}`;
            }
            
            // Default video for upper arms exercises
            return 'https://www.youtube.com/embed/XdWS4XdK_1k';
        }
    </script>
    
    <?php include 'includes/assistant.php'; ?>

</body>
</html>