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

// Process form submission for adding a new plan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_plan'])) {
    $plan_name = trim($_POST['plan_name']);
    
    if (!empty($plan_name)) {
        $stmt = $conn->prepare("INSERT INTO workout_plans (user_id, plan_name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $plan_name);
        
        if ($stmt->execute()) {
            // Redirect to avoid form resubmission
            header("Location: workout_plans.php");
            exit;
        }
    }
}

// Process form submission for deleting a plan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_plan'])) {
    $plan_id = $_POST['plan_id'];
    
    $stmt = $conn->prepare("DELETE FROM workout_plans WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $plan_id, $user_id);
    
    if ($stmt->execute()) {
        // Redirect to avoid form resubmission
        header("Location: workout_plans.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workout Plans - WorkFit</title>
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
        
        /* Plans Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .plan-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .plan-title {
            font-size: 20px;
            font-weight: 600;
        }
        
        .plan-date {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .plan-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .plan-stat {
            flex: 1;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .plan-footer {
            display: flex;
            justify-content: space-between;
        }
        
        .plan-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        /* Add Plan Modal */
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
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 0;
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .empty-icon {
            font-size: 60px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }
        
        .empty-text {
            font-size: 18px;
            color: var(--text-muted);
            margin-bottom: 30px;
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
                <h1 class="page-title">Workout Plans</h1>
                <button class="btn btn-primary" id="add-plan-btn">
                    <i class="fas fa-plus"></i> Add New Plan
                </button>
            </div>
            
            <?php if (empty($workout_plans)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <p class="empty-text">You don't have any workout plans yet. Create your first plan to get started!</p>
                    <button class="btn btn-primary" id="empty-add-plan-btn">
                        <i class="fas fa-plus"></i> Create Workout Plan
                    </button>
                </div>
            <?php else: ?>
                <div class="plans-grid">
                    <?php foreach ($workout_plans as $plan): ?>
                        <?php
                            // Get exercises count for this plan
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM exercises WHERE plan_id = ?");
                            $stmt->bind_param("i", $plan['id']);
                            $stmt->execute();
                            $exercises_count = $stmt->get_result()->fetch_assoc()['total'];
                            
                            // Get completed exercises count
                            $stmt = $conn->prepare("SELECT COUNT(*) as completed FROM exercises WHERE plan_id = ? AND is_completed = 1");
                            $stmt->bind_param("i", $plan['id']);
                            $stmt->execute();
                            $completed_count = $stmt->get_result()->fetch_assoc()['completed'];
                            
                            // Calculate completion percentage
                            $completion_percentage = $exercises_count > 0 ? round(($completed_count / $exercises_count) * 100) : 0;
                        ?>
                        <div class="plan-card">
                            <div class="plan-header">
                                <h3 class="plan-title"><?php echo $plan['plan_name']; ?></h3>
                                <div class="plan-date"><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></div>
                            </div>
                            
                            <div class="plan-stats">
                                <div class="plan-stat">
                                    <div class="stat-value"><?php echo $exercises_count; ?></div>
                                    <div class="stat-label">Exercises</div>
                                </div>
                                <div class="plan-stat">
                                    <div class="stat-value"><?php echo $completed_count; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                                <div class="plan-stat">
                                    <div class="stat-value"><?php echo $completion_percentage; ?>%</div>
                                    <div class="stat-label">Progress</div>
                                </div>
                            </div>
                            
                            <div class="plan-footer">
                                <a href="view_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <div class="plan-actions">
                                    <a href="edit_plan.php?id=<?php echo $plan['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                        <button type="submit" name="delete_plan" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add Plan Modal -->
    <div class="modal" id="add-plan-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Create New Workout Plan</h2>
                <button class="modal-close" id="modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="plan_name">Plan Name</label>
                    <input type="text" id="plan_name" name="plan_name" class="form-control" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" id="cancel-btn">Cancel</button>
                    <button type="submit" name="add_plan" class="btn btn-primary">Create Plan</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Mobile Sidebar Toggle
        document.querySelector('.mobile-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });
        
        // Modal Functionality
        const modal = document.getElementById('add-plan-modal');
        const addPlanBtn = document.getElementById('add-plan-btn');
        const emptyAddPlanBtn = document.getElementById('empty-add-plan-btn');
        const modalClose = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('cancel-btn');
        
        function openModal() {
            modal.classList.add('active');
        }
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        if (addPlanBtn) {
            addPlanBtn.addEventListener('click', openModal);
        }
        
        if (emptyAddPlanBtn) {
            emptyAddPlanBtn.addEventListener('click', openModal);
        }
        
        modalClose.addEventListener('click', closeModal);
        cancelBtn.addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    </script>
    
    <?php include 'includes/assistant.php'; ?>
    
    <!-- Include Theme Changer -->
    <?php include 'includes/theme-changer.php'; ?>
</body>
</html>