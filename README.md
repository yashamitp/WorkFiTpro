# WorkFit - AI-Powered Workout Planner

WorkFit is a comprehensive fitness application that helps users plan and track their workouts, monitor their diet, and achieve their fitness goals with the assistance of AI.

## Features

- **User Authentication**: Secure login/signup system with Google authentication via Firebase
- **Dashboard**: Overview of workout plans, today's exercises, and weekly progress
- **Workout Plans**: Create and manage custom workout plans
- **Exercise Management**: Add, edit, and track exercises with completion status
- **Diet Tracker**: Log and monitor food intake with calorie and protein tracking
- **BMI Calculator**: Calculate and track BMI with personalized recommendations
- **Exercise Guide**: Browse exercises by muscle group with detailed instructions
- **Nutrition Finder**: Search for foods and view nutritional information
- **AI Chatbot**: Get fitness advice and answers to questions using Gemini AI
- **Progress Tracking**: Monitor workout completion and fitness achievements

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL (via phpMyAdmin)
- **Authentication**: Firebase Authentication
- **APIs**: 
  - ExerciseDB API (RapidAPI) for exercise information
  - FoodData Central API for nutrition information
  - Gemini API for AI chatbot functionality

## Installation and Setup

### Prerequisites

- Web server with PHP support (e.g., Apache, Nginx)
- MySQL database
- phpMyAdmin
- Firebase account (for Google authentication)
- API keys for ExerciseDB, FoodData Central, and Gemini

### Setup Instructions

1. **Database Setup**:
   - Create a MySQL database named `workfit_db`
   - The application will automatically create the necessary tables on first run

2. **Configuration**:
   - Update the database connection details in `config.php`
   - Add your API keys:
     - Update Firebase configuration in `login.php` and `signup.php`
     - Add your RapidAPI key in `exercise_guide.php`
     - Add your FoodData Central API key in `nutrition_finder.php`
     - Add your Gemini API key in `chat_bot.php`

3. **Deployment**:
   - Upload all files to your web server
   - Ensure the web server has write permissions for the application directory

4. **Access the Application**:
   - Navigate to the application URL in your web browser
   - Create an account or log in with Google

## File Structure

- `index.php` - Landing page
- `login.php` - User login page
- `signup.php` - User registration page
- `firebase_auth.php` - Firebase authentication handler
- `config.php` - Database configuration and utility functions
- `dashboard.php` - Main dashboard after login
- `workout_plans.php` - Workout plan management
- `add_exercise.php` - Add exercises to plans
- `update_exercise.php` - Update exercise status
- `delete_exercise.php` - Delete exercises
- `diet_tracker.php` - Food intake tracking
- `bmi_calculator.php` - BMI calculation and tracking
- `exercise_guide.php` - Exercise library and instructions
- `nutrition_finder.php` - Food nutrition search
- `chat_bot.php` - AI assistant functionality
- `logout.php` - User logout handler

## Customization

- **Styling**: Modify the inline CSS in each file to customize the appearance
- **Features**: Add or remove features by modifying the relevant PHP files
- **Database**: Extend the database schema in `config.php` for additional functionality

## Security Considerations

- API keys should be stored securely and not exposed in client-side code
- Implement proper input validation and sanitization for all user inputs
- Use HTTPS to secure data transmission
- Regularly update dependencies and apply security patches

## License

This project is available for personal and commercial use.

## Support

For questions or support, please contact the developer.

---

Enjoy using WorkFit for your fitness journey!