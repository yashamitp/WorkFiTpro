<?php
// Check if the database exists first
include_once 'check_database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WorkFit - Your AI Workout Planner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #fff;
            min-height: 100vh;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            background: rgba(26, 26, 46, 0.8);
            backdrop-filter: blur(10px);
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #4cc9f0;
        }
        
        .logo span {
            color: #f72585;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #4cc9f0;
        }
        
        .auth-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 50px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-login {
            background: transparent;
            border: 2px solid #4cc9f0;
            color: #4cc9f0;
        }
        
        .btn-login:hover {
            background: #4cc9f0;
            color: #1a1a2e;
        }
        
        .btn-signup {
            background: #f72585;
            color: #fff;
        }
        
        .btn-signup:hover {
            background: #b5179e;
        }
        
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 80px;
        }
        
        .hero-content {
            width: 50%;
            animation: fadeIn 1s ease-in-out;
        }
        
        .hero-image {
            width: 50%;
            display: flex;
            justify-content: center;
            animation: slideIn 1s ease-in-out;
        }
        
        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            font-size: 48px;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        h1 span {
            color: #4cc9f0;
        }
        
        .hero-text {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
            color: #ccc;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #4cc9f0, #4361ee);
            color: #fff;
            padding: 15px 30px;
            font-size: 18px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(45deg, #4361ee, #4cc9f0);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(76, 201, 240, 0.3);
        }
        
        .features {
            padding: 100px 0;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-title h2 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .section-title p {
            color: #ccc;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }
        
        .feature-icon {
            font-size: 40px;
            color: #4cc9f0;
            margin-bottom: 20px;
        }
        
        .feature-title {
            font-size: 22px;
            margin-bottom: 15px;
        }
        
        .feature-text {
            color: #ccc;
            line-height: 1.6;
        }
        
        .testimonials {
            padding: 100px 0;
            background: rgba(22, 33, 62, 0.5);
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }
        
        .testimonial-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 30px;
            position: relative;
        }
        
        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
            color: #ccc;
            line-height: 1.6;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
        }
        
        .author-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .author-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .author-info h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .author-info p {
            color: #4cc9f0;
            font-size: 14px;
        }
        
        .quote-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 60px;
            color: rgba(76, 201, 240, 0.1);
        }
        
        footer {
            background: #1a1a2e;
            padding: 50px 0 20px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #4cc9f0;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: #4cc9f0;
        }
        
        .social-links {
            display: flex;
            gap: 15px;
        }
        
        .social-links a {
            color: #ccc;
            font-size: 20px;
            transition: color 0.3s;
        }
        
        .social-links a:hover {
            color: #4cc9f0;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #ccc;
            font-size: 14px;
        }
        
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
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding-top: 100px;
            }
            
            .hero-content, .hero-image {
                width: 100%;
            }
            
            .hero-content {
                margin-bottom: 40px;
            }
            
            h1 {
                font-size: 36px;
            }
            
            .cta-buttons {
                justify-content: center;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">Work<span>Fit</span></div>
                <div class="nav-links">
                    <a href="#features">Features</a>
                    <a href="#testimonials">Testimonials</a>
                    <a href="#about">About</a>
                    <a href="#contact">Contact</a>
                </div>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-login">Login</a>
                    <a href="signup.php" class="btn btn-signup">Sign Up</a>
                </div>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container" style="display: flex; align-items: center;">
            <div class="hero-content">
                <h1>Transform Your Fitness Journey with <span>WorkFit</span></h1>
                <p class="hero-text">WorkFit combines cutting-edge AI technology with personalized workout planning to help you achieve your fitness goals faster and smarter.</p>
                <div class="cta-buttons">
                    <a href="signup.php" class="btn btn-primary">Get Started</a>
                    <a href="#features" class="btn btn-login">Learn More</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80" alt="Fitness Tracking">
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="container">
            <div class="section-title">
                <h2>Powerful Features</h2>
                <p>Discover the tools that will revolutionize your fitness routine</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="feature-title">Smart Workout Planning</h3>
                    <p class="feature-text">Create customized workout plans with our AI assistant that adapts to your progress and goals.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="feature-title">Nutrition Tracking</h3>
                    <p class="feature-text">Log your meals and track nutritional information to complement your fitness journey.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Progress Monitoring</h3>
                    <p class="feature-text">Visualize your fitness journey with detailed progress reports and achievement tracking.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3 class="feature-title">BMI Calculator</h3>
                    <p class="feature-text">Track your Body Mass Index and receive personalized recommendations.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <h3 class="feature-title">Exercise Guide</h3>
                    <p class="feature-text">Access a comprehensive library of exercises with proper form instructions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">AI Assistant</h3>
                    <p class="feature-text">Get instant answers to your fitness questions with our AI-powered chatbot.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-title">
                <h2>What Others Say</h2>
                <p>Hear from our community of fitness enthusiasts</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-right"></i>
                    </div>
                    <p class="testimonial-text">WorkFit has completely transformed my approach to fitness. The AI recommendations are spot-on, and I've seen more progress in 3 months than I did in a year on my own.</p>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="https://randomuser.me/api/portraits/women/32.jpg" alt="Sarah J.">
                        </div>
                        <div class="author-info">
                            <h4>Sarah Johnson</h4>
                            <p>Fitness Enthusiast</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-right"></i>
                    </div>
                    <p class="testimonial-text">As a busy professional, I never had time to plan my workouts properly. WorkFit's AI planner has made it so easy to stay consistent with my fitness goals.</p>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="https://randomuser.me/api/portraits/men/45.jpg" alt="Michael T.">
                        </div>
                        <div class="author-info">
                            <h4>Michael Thompson</h4>
                            <p>Software Engineer</p>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="quote-icon">
                        <i class="fas fa-quote-right"></i>
                    </div>
                    <p class="testimonial-text">The nutrition tracking feature has been a game-changer for me. I finally understand my eating habits and have made meaningful changes to support my workouts.</p>
                    <div class="testimonial-author">
                        <div class="author-image">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Emily R.">
                        </div>
                        <div class="author-info">
                            <h4>Emily Rodriguez</h4>
                            <p>Nutrition Coach</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <div class="logo">Work<span>Fit</span></div>
                    <p style="color: #ccc; margin-top: 20px;">Your AI-powered fitness companion for achieving your health and wellness goals.</p>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Legal</h3>
                    <ul class="footer-links">
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Connect With Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 WorkFit. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>