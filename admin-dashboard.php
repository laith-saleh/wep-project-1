<?php
require_once 'functions.php';
requireAdmin();

$db = getDB();

$stmt = $db->prepare("SELECT COUNT(*) FROM courses");
$stmt->execute();
$courseCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM students");
$stmt->execute();
$studentCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM admins");
$stmt->execute();
$adminCount = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM registrations");
$stmt->execute();
$totalEnrollments = $stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT c.*,
        (SELECT COUNT(*) FROM registrations WHERE course_id = c.id) AS enrolled_count
    FROM courses c
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - read Portal</title>
   <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a href="index.php" class="sidebar-logo">read <span>Portal</span></a>
            <nav class="sidebar-nav">
                <a href="admin-dashboard.php" class="active"> Dashboard</a>
                <a href="admin-courses.php"> Courses</a>
                <a href="admin-users.php"> Users</a>
            </nav>
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </aside>

        <div class="main-area">
            <div class="topbar">
                <h2>Admin Dashboard</h2>
                <div class="topbar-right">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="badge badge-gold">Admin</span>
                    <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
                </div>
            </div>

            <div class="content-body">
                <div class="stat-grid">
                    <div class="dash-card">
                        <div class="dash-card-title">Total Courses</div>
                        <div class="dash-card-value"><?= $courseCount ?></div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-title">Students</div>
                        <div class="dash-card-value"><?= $studentCount ?></div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-title">Admins</div>
                        <div class="dash-card-value"><?= $adminCount ?></div>
                    </div>
                    <div class="dash-card">
                        <div class="dash-card-title">Registrations</div>
                        <div class="dash-card-value"><?= $totalEnrollments ?></div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Teacher</th>
                                <th>Category</th>
                                <th>Enrolled</th>
                                <th>Capacity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr><td colspan="6" style="text-align:center;color:#9ca3af;">No courses yet. <a href="admin-courses.php" style="color:var(--gold);">Add your first course</a>.</td></tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course):
                                    $emptyChairs = $course['capacity'] - $course['enrolled_count'];
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($course['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($course['teacher']) ?></td>
                                        <td><span class="badge badge-gold"><?= htmlspecialchars($course['category']) ?></span></td>
                                        <td><?= $course['enrolled_count'] ?></td>
                                        <td><?= $course['capacity'] ?></td>
                                        <td>
                                            <?php if ($emptyChairs <= 0): ?>
                                                <span class="badge badge-red">Full</span>
                                            <?php elseif ($emptyChairs <= 5): ?>
                                                <span class="badge badge-gold"><?= $emptyChairs ?> left</span>
                                            <?php else: ?>
                                                <span class="badge badge-green">Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
