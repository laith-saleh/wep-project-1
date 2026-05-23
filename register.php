<?php
require_once 'functions.php';

if (isLoggedIn()) {
    if (isAdmin()) { header('Location: admin-dashboard.php'); exit; }
    else { header('Location: student-dashboard.php'); exit; }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = getDB();
        $table = $role === 'admin' ? 'admins' : 'students';
        $stmt = $db->prepare("SELECT id FROM $table WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = 'This email is already registered.';
        } else {
            if ($role === 'student') {
                $stmt = $db->prepare("SELECT COUNT(*) FROM students");
                $stmt->execute();
                $count = $stmt->fetchColumn();
                $studentNumber = 'STU' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

                $stmt = $db->prepare("INSERT INTO students (student_number, name, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$studentNumber, $name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            } else {
                $stmt = $db->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            }
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - read Portal</title>
   <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">

    <div class="left-panel">
        <a href="index.php" class="panel-logo">read <span>Portal</span></a>
        <div class="panel-body">
            <h2>Join<br><em>read Portal</em></h2>
            <p>Create your account and start your learning journey today. Choose between student and administrator access.</p>
        </div>
        <div class="panel-features">
            <div class="panel-feature"><span class="dot"></span> Free account registration</div>
            <div class="panel-feature"><span class="dot"></span> Instant access to courses</div>
            <div class="panel-feature"><span class="dot"></span> Manage your learning path</div>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-box">
            <a href="index.php" class="back-link">&larr; Back to Home</a>
            <h1 class="login-title">Sign Up</h1>
            <p class="login-sub">Create your account to get started</p>

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
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" class="form-input" id="name" name="name" placeholder="Your full name" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-input" id="email" name="email" placeholder="you@example.com" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="password-wrap">
                        <input type="password" class="form-input" id="password" name="password" placeholder="At least 6 characters" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">Show</button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-input" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
                </div>

                <button type="submit" class="btn-signin">Create Account</button>
            </form>

            <div class="alt-link">
                Already have an account? <a href="login.php">Sign In</a>
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
