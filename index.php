<?php require_once 'functions.php';
$db = getDB();

$stmt = $db->prepare("SELECT COUNT(*) FROM courses");
$stmt->execute();
$courseCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM students");
$stmt->execute();
$studentCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM registrations");
$stmt->execute();
$enrollCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>read Portal - Student Course Registration</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="site-nav" id="siteNav">
        <a href="index.php" class="nav-logo">read <span>Portal</span></a>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="login.php" class="btn-nav-login">Sign In</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <div class="hero-badge">Student Course Registration</div>
            <h1>Your <em>Learning Journey</em><br>Starts Here</h1>
            <p>Browse, register, and manage your courses with ease. Our platform gives you everything you need to stay on track.</p>
            <div class="hero-actions">
                <a href="register.php?type=student" class="btn-primary-gold">Get Started</a>
                <a href="#features" class="btn-outline-white">Learn More</a>
            </div>
        </div>
        <div class="hero-stats">
            <div class="stat-card"><div class="num"><?= $courseCount ?>+</div><div class="label">Courses</div></div>
            <div class="stat-card"><div class="num"><?= $studentCount ?>+</div><div class="label">Active Students</div></div>
            <div class="stat-card"><div class="num"><?= $enrollCount ?>+</div><div class="label">Enrollments</div></div>
        </div>
    </section>

    <section class="features" id="features">
        <div class="section-label">Features</div>
        <h2 class="section-title">Everything You Need</h2>
        <p class="section-sub">Our platform provides a complete course management experience for both students and administrators.</p>
        <div class="features-grid">
            <div class="feature-card fade-up">
                <div class="feature-icon"></div>
                <h4>Course Browsing</h4>
                <p>Browse courses by category with detailed information about content, instructors, and available seats.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon"></div>
                <h4>Easy Enrollment</h4>
                <p>Enroll in courses with one click. Track your registrations and manage your schedule.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon"></div>
                <h4>Capacity Control</h4>
                <p>Each course has a seat limit. See real-time availability and secure your spot before it fills up.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon"></div>
                <h4>Admin Management</h4>
                <p>Administrators can add, edit, and manage courses with an intuitive interface.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon"></div>
                <h4>Live Dashboard</h4>
                <p>View real-time statistics on courses, students, and enrollments at a glance.</p>
            </div>
            <div class="feature-card fade-up">
                <div class="feature-icon"></div>
                <h4>Secure & Safe</h4>
                <p>Your data is protected with secure authentication and password hashing.</p>
            </div>
        </div>
    </section>

    <section class="how-it-works" id="how">
        <div class="section-label">How It Works</div>
        <h2 class="section-title">Get Started in 4 Steps</h2>
        <p class="section-sub">Getting started with read Portal is simple and fast.</p>
        <div class="steps">
            <div class="step fade-up">
                <div class="step-num">1</div>
                <h5>Sign Up</h5>
                <p>Create your student account with your name, email, and password.</p>
            </div>
            <div class="step fade-up">
                <div class="step-num">2</div>
                <h5>Browse Courses</h5>
                <p>Explore available courses organized by category.</p>
            </div>
            <div class="step fade-up">
                <div class="step-num">3</div>
                <h5>Enroll</h5>
                <p>Select your courses and secure your seat instantly.</p>
            </div>
            <div class="step fade-up">
                <div class="step-num">4</div>
                <h5>Track Progress</h5>
                <p>Monitor your enrolled courses from your personal dashboard.</p>
            </div>
        </div>
    </section>

    <section class="cta-section fade-up">
        <h2>Ready to Start Your Journey?</h2>
        <p>Join read Portal today and take control of your learning experience.</p>
        <div class="cta-buttons">
            <a href="register.php?type=student" class="btn-primary-gold">Student Sign Up</a>
            <a href="register.php?type=admin" class="btn-outline-white">Admin Sign Up</a>
        </div>
    </section>

    <footer>
        <span>&copy; <?= date('Y') ?> read Portal. All rights reserved.</span>
        <span>Powered by <a href="#">read Portal</a></span>
    </footer>

    <script src="script.js"></script>
</body>
</html>
