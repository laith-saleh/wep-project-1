<?php
require_once 'functions.php';

if (isLoggedIn()) {
    if (isAdmin()) { header('Location: admin-dashboard.php'); exit; }
    else { header('Location: student-dashboard.php'); exit; }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password.';
    } else {
        $db = getDB();
        $table = $role === 'admin' ? 'admins' : 'students';
        $stmt = $db->prepare("SELECT * FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $role;

            if ($role === 'admin') {
                header('Location: admin-dashboard.php');
            } else {
                header('Location: student-dashboard.php');
            }
            exit;
        }

        $error = 'Invalid email, password, or role selection.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - read Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div class="left-panel">
        <a href="index.php" class="panel-logo">read <span>Portal</span></a>
        <div class="panel-body">
            <h2>Welcome<br><em>Back</em></h2>
            <p>Sign in to access your courses, track your progress, and manage your learning journey.</p>
        </div>
        <div class="panel-features">
            <div class="panel-feature"><span class="dot"></span> Browse and enroll in courses</div>
            <div class="panel-feature"><span class="dot"></span> Track your registrations</div>
            <div class="panel-feature"><span class="dot"></span> Manage your learning</div>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-box">
            <a href="index.php" class="back-link">&larr; Back to Home</a>
            <h1 class="login-title">Sign In</h1>
            <p class="login-sub">Access your account to continue</p>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Account created successfully! Sign in below.</div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="role-tabs">
                    <button type="button" class="role-tab active" id="tabStudent" onclick="setRole('student')">Student</button>
                    <button type="button" class="role-tab" id="tabAdmin" onclick="setRole('admin')">Administrator</button>
                </div>

                <input type="hidden" name="role" id="userType" value="student">

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-input" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-wrap">
                        <input type="password" class="form-input" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn-signin" id="submitBtn">Sign In</button>
            </form>

            <div class="alt-link">
                Don't have an account? <a href="register.php">Sign Up</a>
            </div>
        </div>
    </div>

    <script>
        const params = new URLSearchParams(location.search);
        if (params.get('type') === 'admin') setRole('admin');

        function setRole(role) {
            document.getElementById('userType').value = role;
            document.getElementById('tabStudent').classList.toggle('active', role === 'student');
            document.getElementById('tabAdmin').classList.toggle('active', role === 'admin');
        }

        function togglePassword() {
            const inp = document.getElementById('password');
            inp.type = inp.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
