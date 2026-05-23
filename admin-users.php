<?php
require_once 'functions.php';
requireAdmin();

$db = getDB();

$stmt = $db->prepare("SELECT * FROM students ORDER BY created_at DESC");
$stmt->execute();
$students = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM admins ORDER BY created_at DESC");
$stmt->execute();
$admins = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - read Portal</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .user-tabs {
            display: flex; gap: 0; margin-bottom: 24px;
            background: #ededea; border-radius: 10px; padding: 4px;
            display: inline-flex;
        }
        .user-tab {
            padding: 10px 24px; border: none; background: transparent;
            border-radius: 8px; font-family: 'DM Sans', sans-serif;
            font-size: .9rem; font-weight: 500; color: #6b7280; cursor: pointer;
            transition: all .25s;
        }
        .user-tab.active {
            background: #fff; color: var(--navy);
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .user-section { display: none; }
        .user-section.active { display: block; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a href="index.php" class="sidebar-logo">read <span>Portal</span></a>
            <nav class="sidebar-nav">
                <a href="admin-dashboard.php"> Dashboard</a>
                <a href="admin-courses.php"> Courses</a>
                <a href="admin-users.php" class="active"> Users</a>
            </nav>
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </aside>

        <div class="main-area">
            <div class="topbar">
                <h2>Manage Users</h2>
                <div class="topbar-right">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="badge badge-gold">Admin</span>
                    <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
                </div>
            </div>

            <div class="content-body">
                <div class="user-tabs">
                    <button class="user-tab active" onclick="showTab('students')">Students (<?= count($students) ?>)</button>
                    <button class="user-tab" onclick="showTab('admins')">Admins (<?= count($admins) ?>)</button>
                </div>

                <div id="section-students" class="user-section active">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student #</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="4" style="text-align:center;color:#9ca3af;">No students registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s):
                                        $stmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE student_id = ?");
                                        $stmt->execute([$s['id']]);
                                        $regCount = $stmt->fetchColumn();
                                    ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($s['student_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($s['name']) ?></td>
                                            <td><?= htmlspecialchars($s['email']) ?></td>
                                            <td><?= $regCount ?> course<?= $regCount != 1 ? 's' : '' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="section-admins" class="user-section">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($admins)): ?>
                                    <tr><td colspan="2" style="text-align:center;color:#9ca3af;">No admins registered yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($admins as $a): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($a['email']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.user-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.user-section').forEach(s => s.classList.remove('active'));
            document.querySelector('.user-tab' + (tab === 'students' ? ':first-child' : ':last-child')).classList.add('active');
            document.getElementById('section-' + tab).classList.add('active');
        }
    </script>
</body>
</html>
