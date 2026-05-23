<?php
require_once 'functions.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT c.*, r.registration_date,
        (SELECT COUNT(*) FROM registrations WHERE course_id = c.id) AS enrolled_count
    FROM registrations r
    JOIN courses c ON r.course_id = c.id
    WHERE r.student_id = ?
    ORDER BY r.registration_date DESC
");
$stmt->execute([$userId]);
$registeredCourses = $stmt->fetchAll();


$stmt = $db->prepare("
    SELECT c.*, cc.grade
    FROM completed_courses cc
    JOIN courses c ON cc.course_id = c.id
    WHERE cc.student_id = ?
    ORDER BY c.name
");
$stmt->execute([$userId]);
$completedCourses = $stmt->fetchAll();

$stmt = $db->prepare("SELECT COUNT(*) FROM courses");
$stmt->execute();
$allCourseCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE student_id = ?");
$stmt->execute([$userId]);
$myRegCount = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - read Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a href="index.php" class="sidebar-logo">read <span>Portal</span></a>
            <nav class="sidebar-nav">
                <a href="student-dashboard.php" class="active"> Dashboard</a>
                <a href="student-courses.php"> Browse Courses</a>
            </nav>
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </aside>

        <div class="main-area">
            <div class="topbar">
                <h2>My Dashboard</h2>
                <div class="topbar-right">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="badge badge-blue">Student</span>
                    <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
                </div>
            </div>

            <div class="content-body">
                <div class="stat-grid">
                    <div class="dash-card">
                        <div class="dash-card-title">Registered Courses</div>
                        <div class="dash-card-value"><?= $myRegCount ?></div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-title">Completed Courses</div>
                        <div class="dash-card-value"><?= count($completedCourses) ?></div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-title">Available Courses</div>
                        <div class="dash-card-value"><?= $allCourseCount ?></div>
                    </div>
                </div>

                <h3 style="font-family:'Playfair Display',serif;color:var(--navy);margin-bottom:16px;margin-top:32px;font-size:1.2rem;">
                    My Registered Courses
                </h3>

                <?php if (empty($registeredCourses)): ?>
                    <div class="empty-state">
                        <h3>No courses yet</h3>
                        <p>You haven't registered in any courses yet. <a href="student-courses.php" style="color:var(--gold);font-weight:600;">Browse courses</a> to get started.</p>
                    </div>
                <?php else: ?>
                    <div class="course-grid">
                        <?php foreach ($registeredCourses as $course):
                            $emptyChairs = $course['capacity'] - $course['enrolled_count'];
                        ?>
                            <div class="course-card">
                                <?php if (!empty($course['photo'])): ?>
                                    <img class="course-card-img" src="<?= htmlspecialchars($course['photo']) ?>" alt="">
                                <?php else: ?>
                                    <div class="course-card-img" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:.8rem;">No Image</div>
                                <?php endif; ?>
                                <div class="course-card-body">
                                    <h4><?= htmlspecialchars($course['name']) ?></h4>
                                    <div class="teacher"> <?= htmlspecialchars($course['teacher']) ?></div>
                                    <p class="desc"><?= htmlspecialchars($course['description']) ?></p>
                                    <div class="course-meta">
                                        <span class="cat-badge"><?= htmlspecialchars($course['category']) ?></span>
                                        <span><?= $emptyChairs ?> / <?= $course['capacity'] ?> seats</span>
                                        <span class="badge badge-green">Registered</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($completedCourses)): ?>
                    <h3 style="font-family:'Playfair Display',serif;color:var(--navy);margin-bottom:16px;margin-top:32px;font-size:1.2rem;">
                        Completed Courses
                    </h3>
                    <div class="course-grid">
                        <?php foreach ($completedCourses as $course): ?>
                            <div class="course-card">
                                <div class="course-card-body">
                                    <h4><?= htmlspecialchars($course['name']) ?></h4>
                                    <div class="course-meta">
                                        <span class="cat-badge"><?= htmlspecialchars($course['category']) ?></span>
                                        <span class="badge badge-green">Completed</span>
                                        <?php if ($course['grade']): ?>
                                            <span class="badge badge-gold">Grade: <?= htmlspecialchars($course['grade']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
