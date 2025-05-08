<?php
require_once 'config.php';

// Check if it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the message from POST data or from the raw input
    $input = file_get_contents('php://input');
    parse_str($input, $post_vars);
    
    $message = '';
    if (isset($_POST['message'])) {
        $message = trim($_POST['message']);
    } elseif (isset($post_vars['message'])) {
        $message = trim($post_vars['message']);
    }
    
    // If no message was found, return an error
    if (empty($message)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'response' => 'No message received'
        ]);
        exit;
    }
    
    // Process the message and generate a response
    $response = generateResponse($message);
    
    // Log conversation if user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        
        // Log user message
        $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, is_bot, created_at) VALUES (?, ?, 0, NOW())");
        $stmt->bind_param("is", $user_id, $message);
        $stmt->execute();
        
        // Log bot response
        $stmt = $conn->prepare("INSERT INTO chat_logs (user_id, message, is_bot, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->bind_param("is", $user_id, $response);
        $stmt->execute();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);
    exit;
}

/**
 * Generate a response based on the user's message
 * 
 * @param string $message The user's message
 * @return string The bot's response
 */
function generateResponse($message) {
    // Convert message to lowercase for easier matching
    $message_lower = strtolower($message);
    
    // Check for greetings
    if (preg_match('/(hello|hi|hey|greetings|howdy)/i', $message_lower)) {
        return "Hello! How can I help you with your fitness journey today?";
    }
    
    // Check for workout-related questions
    if (strpos($message_lower, 'workout') !== false || 
        strpos($message_lower, 'exercise') !== false || 
        strpos($message_lower, 'training') !== false) {
        
        if (strpos($message_lower, 'beginner') !== false) {
            return "For beginners, I recommend starting with 2-3 workouts per week focusing on full-body exercises. Start with bodyweight exercises like squats, push-ups, and planks. Gradually increase intensity as you build strength and confidence.";
        }
        
        if (strpos($message_lower, 'routine') !== false || strpos($message_lower, 'plan') !== false) {
            return "A balanced workout routine should include:\n\n1. Strength training (2-3 days per week)\n2. Cardiovascular exercise (2-3 days per week)\n3. Flexibility and mobility work (1-2 days per week)\n4. Adequate rest days for recovery\n\nYou can create a custom workout plan in the 'Workout Plans' section of WorkFit.";
        }
        
        if (strpos($message_lower, 'muscle') !== false || strpos($message_lower, 'build') !== false) {
            return "To build muscle effectively:\n\n1. Focus on progressive overload (gradually increasing weight/reps)\n2. Ensure adequate protein intake (1.6-2.2g per kg of bodyweight)\n3. Allow 48 hours of recovery for worked muscle groups\n4. Get 7-9 hours of quality sleep\n5. Stay consistent with your training";
        }
        
        // Default workout response
        return "Regular exercise is crucial for overall health. Aim for at least 150 minutes of moderate-intensity exercise per week, including both cardio and strength training. What specific aspect of working out would you like to know more about?";
    }
    
    // Check for nutrition-related questions
    if (strpos($message_lower, 'diet') !== false || 
        strpos($message_lower, 'nutrition') !== false || 
        strpos($message_lower, 'food') !== false || 
        strpos($message_lower, 'eat') !== false) {
        
        if (strpos($message_lower, 'protein') !== false) {
            return "Protein is essential for muscle repair and growth. The general recommendation is 1.6-2.2g of protein per kg of bodyweight for active individuals. Good sources include lean meats, fish, eggs, dairy, legumes, and plant-based options like tofu and tempeh.";
        }
        
        if (strpos($message_lower, 'calorie') !== false) {
            return "To calculate your approximate daily calorie needs:\n\n1. Find your Basal Metabolic Rate (BMR) using the formula: BMR = 10 × weight(kg) + 6.25 × height(cm) - 5 × age(y) + 5 (men) or - 161 (women)\n2. Multiply by an activity factor:\n   - Sedentary: 1.2\n   - Lightly active: 1.375\n   - Moderately active: 1.55\n   - Very active: 1.725\n\nYou can track your calories in the 'Diet Tracker' section.";
        }
        
        if (strpos($message_lower, 'meal') !== false) {
            return "A balanced meal should include:\n\n1. Protein (meat, fish, eggs, legumes)\n2. Complex carbohydrates (whole grains, starchy vegetables)\n3. Healthy fats (avocado, nuts, olive oil)\n4. Fruits and vegetables\n\nPre-workout meals should be rich in carbs with moderate protein, while post-workout meals should emphasize protein and carbs for recovery.";
        }
        
        // Default nutrition response
        return "Good nutrition is the foundation of fitness success. Focus on whole foods, adequate protein intake, and proper hydration. What specific nutrition topic would you like to learn more about?";
    }
    
    // Check for weight loss questions
    if (strpos($message_lower, 'weight loss') !== false || 
        strpos($message_lower, 'lose weight') !== false || 
        strpos($message_lower, 'fat loss') !== false) {
        
        return "Sustainable weight loss comes from a combination of:\n\n1. Caloric deficit (consuming fewer calories than you burn)\n2. Regular exercise (both cardio and strength training)\n3. Adequate protein intake to preserve muscle mass\n4. Proper hydration and sleep\n5. Consistency over time\n\nAim for a moderate deficit of 500 calories per day for about 1 pound of weight loss per week. Track your progress in the 'Progress' section.";
    }
    
    // Check for BMI questions
    if (strpos($message_lower, 'bmi') !== false) {
        return "BMI (Body Mass Index) is a screening tool that categorizes weight status:\n\n- Below 18.5: Underweight\n- 18.5-24.9: Normal weight\n- 25-29.9: Overweight\n- 30 and above: Obesity\n\nWhile useful for population studies, BMI has limitations as it doesn't account for muscle mass, body composition, or fat distribution. Use our 'BMI Calculator' for a quick assessment, but consider it alongside other health metrics.";
    }
    
    // Check for progress tracking questions
    if (strpos($message_lower, 'progress') !== false || 
        strpos($message_lower, 'track') !== false || 
        strpos($message_lower, 'improve') !== false) {
        
        return "Tracking your fitness progress is essential for staying motivated and making adjustments. Consider tracking:\n\n1. Workout performance (weights, reps, distance, time)\n2. Body measurements (weight, circumferences)\n3. Progress photos\n4. Energy levels and recovery\n5. Nutrition consistency\n\nConsistently review your data to identify patterns and make informed adjustments to your routine. Use the 'Progress' section to record and visualize your journey.";
    }
    
    // Check for motivation questions
    if (strpos($message_lower, 'motivat') !== false || 
        strpos($message_lower, 'stuck') !== false || 
        strpos($message_lower, 'plateau') !== false) {
        
        return "To stay motivated and break through plateaus:\n\n1. Set specific, measurable goals with deadlines\n2. Track your progress to see how far you've come\n3. Mix up your routine every 4-6 weeks\n4. Find a workout buddy or community for accountability\n5. Celebrate small wins along the way\n6. Remember your 'why' - the deeper reason behind your fitness journey\n\nConsistency beats perfection every time!";
    }
    
    // Check for thank you messages
    if (preg_match('/(thank|thanks)/i', $message_lower)) {
        return "You're welcome! I'm here to help you on your fitness journey. Is there anything else you'd like to know?";
    }
    
    // Default response for unrecognized queries
    return "I'm here to help with your fitness and nutrition questions. You can ask me about workout routines, nutrition advice, weight loss strategies, or how to track your progress effectively. What would you like to know more about?";
}
?>