<?php
require_once 'functions.php';
requireLogin();

if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$enrollMsg = '';
$enrollType = '';


if (isset($_GET['enroll'])) {
    $courseId = (int)$_GET['enroll'];

    $stmt = $db->prepare("SELECT id, name, course_code FROM courses WHERE id = ?");
    $stmt->execute([$courseId]);
    $course = $stmt->fetch();

    if (!$course) {
        $enrollMsg = 'Course not found.';
        $enrollType = 'danger';
    } elseif (isRegistered($userId, $courseId)) {
        $enrollMsg = 'You are already registered in this course.';
        $enrollType = 'warning';
    } elseif (isCapacityFull($courseId)) {
        $enrollMsg = 'Sorry, this course has reached full capacity.';
        $enrollType = 'danger';
    } else {
        $unmet = checkPrerequisites($userId, $courseId);
        if (!empty($unmet)) {
            $names = array_map(function($p) { return $p['course_code'] . ' - ' . $p['prereq_name']; }, $unmet);
            $enrollMsg = 'You have not completed the following prerequisites: ' . implode(', ', $names);
            $enrollType = 'danger';
        } else {
            $stmt = $db->prepare("INSERT INTO registrations (student_id, course_id) VALUES (?, ?)");
            $stmt->execute([$userId, $courseId]);
            $enrollMsg = 'Successfully registered in "' . htmlspecialchars($course['name']) . '"!';
            $enrollType = 'success';
        }
    }
}


if (isset($_GET['unenroll'])) {
    $courseId = (int)$_GET['unenroll'];
    $stmt = $db->prepare("DELETE FROM registrations WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    if ($stmt->rowCount() > 0) {
        $enrollMsg = 'Successfully dropped the course.';
        $enrollType = 'success';
    }
}


$stmt = $db->prepare("SELECT course_id FROM registrations WHERE student_id = ?");
$stmt->execute([$userId]);
$myCourseIds = $stmt->fetchAll(PDO::FETCH_COLUMN);


$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $db->prepare("
        SELECT c.*,
            (SELECT COUNT(*) FROM registrations WHERE course_id = c.id) AS enrolled_count
        FROM courses c
        WHERE c.course_code LIKE ? OR c.name LIKE ?
        ORDER BY c.course_code
    ");
    $likeSearch = '%' . $search . '%';
    $stmt->execute([$likeSearch, $likeSearch]);
} else {
    $stmt = $db->prepare("
        SELECT c.*,
            (SELECT COUNT(*) FROM registrations WHERE course_id = c.id) AS enrolled_count
        FROM courses c
        ORDER BY c.course_code
    ");
    $stmt->execute();
}
$courses = $stmt->fetchAll();


$categories = [];
foreach ($courses as $course) {
    $cat = $course['category'] ?: 'Uncategorized';
    if (!isset($categories[$cat])) $categories[$cat] = [];
    $course['empty_chairs'] = $course['capacity'] - $course['enrolled_count'];
    $course['is_enrolled'] = in_array($course['id'], $myCourseIds);
    $course['prerequisites'] = getPrerequisites($course['id']);
    $course['prerequisites_met'] = true;
    foreach ($course['prerequisites'] as $prereq) {
        $stmt = $db->prepare("SELECT id FROM completed_courses WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$userId, $prereq['id']]);
        $prereq['completed'] = $stmt->fetch() ? true : false;
        if (!$prereq['completed']) {
            $course['prerequisites_met'] = false;
        }
    }
    
    $course['prereq_details'] = [];
    $prereqList = getPrerequisites($course['id']);
    foreach ($prereqList as $pr) {
        $stmt = $db->prepare("SELECT id FROM completed_courses WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$userId, $pr['id']]);
        $pr['completed'] = $stmt->fetch() ? true : false;
        $course['prereq_details'][] = $pr;
    }

    $categories[$cat][] = $course;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Courses - read Portal</title>
   <link rel="stylesheet" href="style.css">
   <style>
        .search-bar {
            display: flex; gap: 12px; margin-bottom: 24px;
        }
        .search-bar input {
            flex: 1; padding: 12px 16px; border: 1.5px solid #e5e3df;
            border-radius: 8px; font-family: 'DM Sans', sans-serif;
            font-size: .95rem; color: var(--navy); background: #fff;
            outline: none;
        }
        .search-bar input:focus { border-color: var(--navy); }
        .prereq-list {
            font-size: .82rem; margin-bottom: 12px;
        }
        .prereq-list .prereq-item {
            display: flex; align-items: center; gap: 6px; padding: 2px 0;
        }
        .prereq-list .prereq-met { color: #166534; }
        .prereq-list .prereq-unmet { color: #991b1b; }
        .course-code-badge {
            display: inline-block; background: var(--navy); color: #fff;
            padding: 2px 10px; border-radius: 4px; font-size: .75rem;
            font-weight: 600; letter-spacing: .5px; margin-bottom: 6px;
        }
        .credits-badge {
            font-size: .78rem; color: #6b7280; margin-bottom: 8px;
        }
        .no-results {
            text-align: center; padding: 60px 20px; color: #9ca3af;
        }
        .no-results h3 { font-family: 'Playfair Display', serif; color: var(--navy); margin-bottom: 8px; }
   </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a href="index.php" class="sidebar-logo">read <span>Portal</span></a>
            <nav class="sidebar-nav">
                <a href="student-dashboard.php"> Dashboard</a>
                <a href="student-courses.php" class="active"> Browse Courses</a>
            </nav>
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </aside>

        <div class="main-area">
            <div class="topbar">
                <h2>Browse Courses</h2>
                <div class="topbar-right">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="badge badge-blue">Student</span>
                    <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
                </div>
            </div>

            <div class="content-body">
                <?php if ($enrollMsg): ?>
                    <div class="alert alert-<?= $enrollType ?>"><?= htmlspecialchars($enrollMsg) ?></div>
                <?php endif; ?>

             
                <form method="GET" action="" class="search-bar">
                    <input type="text" name="search" placeholder="Search by course code or title..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-gold">Search</button>
                    <?php if ($search !== ''): ?>
                        <a href="student-courses.php" class="btn btn-outline">Clear</a>
                    <?php endif; ?>
                </form>

                <?php if (empty($courses)): ?>
                    <div class="no-results">
                        <h3><?= $search !== '' ? 'No courses match your search' : 'No courses available' ?></h3>
                        <p><?= $search !== '' ? 'Try a different search term.' : 'There are no courses available at the moment.' ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $catName => $catCourses): ?>
                        <div class="category-section">
                            <h3><?= htmlspecialchars($catName) ?></h3>
                            <div class="course-grid">
                                <?php foreach ($catCourses as $course): ?>
                                    <div class="course-card">
                                        <?php if (!empty($course['photo'])): ?>
                                            <img class="course-card-img" src="<?= htmlspecialchars($course['photo']) ?>" alt="">
                                        <?php else: ?>
                                            <div class="course-card-img" style="display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:.8rem;">No Image</div>
                                        <?php endif; ?>
                                        <div class="course-card-body">
                                            <div class="course-code-badge"><?= htmlspecialchars($course['course_code']) ?></div>
                                            <h4><?= htmlspecialchars($course['name']) ?></h4>
                                            <div class="teacher"><?= htmlspecialchars($course['teacher']) ?></div>
                                            <div class="credits-badge"><?= $course['credit_hours'] ?> Credit Hour<?= $course['credit_hours'] != 1 ? 's' : '' ?></div>
                                            <p class="desc"><?= htmlspecialchars($course['description']) ?></p>

                                            
                                            <?php if (!empty($course['prereq_details'])): ?>
                                                <div class="prereq-list">
                                                    <strong style="font-size:.78rem;color:var(--navy);">Prerequisites:</strong>
                                                    <?php foreach ($course['prereq_details'] as $pr): ?>
                                                        <div class="prereq-item <?= $pr['completed'] ? 'prereq-met' : 'prereq-unmet' ?>">
                                                            <?php if ($pr['completed']): ?>
                                                                <span>✅</span>
                                                            <?php else: ?>
                                                                <span>❌</span>
                                                            <?php endif; ?>
                                                            <span><?= htmlspecialchars($pr['course_code']) ?> - <?= htmlspecialchars($pr['name']) ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="course-meta">
                                                <span class="cat-badge"><?= htmlspecialchars($course['category']) ?></span>
                                                <span><?= $course['empty_chairs'] ?> / <?= $course['capacity'] ?> seats</span>
                                            </div>
                                            <div style="display:flex;gap:8px;">
                                                <?php if ($course['is_enrolled']): ?>
                                                    <span class="badge badge-green" style="padding:8px 16px;font-size:.85rem;">Registered</span>
                                                    <a href="?unenroll=<?= $course['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Drop this course?')">Drop</a>
                                                <?php elseif ($course['empty_chairs'] <= 0): ?>
                                                    <span class="badge badge-red" style="padding:8px 16px;font-size:.85rem;">Full</span>
                                                <?php elseif (!$course['prerequisites_met']): ?>
                                                    <span class="badge badge-red" style="padding:8px 16px;font-size:.85rem;">Prerequisites Not Met</span>
                                                <?php else: ?>
                                                    <a href="?enroll=<?= $course['id'] ?>" class="btn btn-sm btn-gold">Register</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
