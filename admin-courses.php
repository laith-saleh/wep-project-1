<?php
require_once 'functions.php';
requireAdmin();

$db = getDB();
$msg = '';
$msgType = '';


if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->execute([$delId]);
    if ($stmt->rowCount() > 0) {
        $msg = 'Course deleted successfully.';
        $msgType = 'success';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['course_id'] ?? 0);
    $courseCode = trim($_POST['course_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $teacher = trim($_POST['teacher'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);
    $creditHours = (int)($_POST['credit_hours'] ?? 3);
    $category = trim($_POST['category'] ?? '');
    $prereqIds = $_POST['prerequisites'] ?? [];

    if (empty($courseCode) || empty($name) || empty($description) || empty($teacher) || $capacity <= 0 || empty($category)) {
        $msg = 'Please fill all required fields correctly.';
        $msgType = 'danger';
    } else {
        $photoPath = $_POST['existing_photo'] ?? '';

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($_FILES['photo']['type'], $allowed)) {
                $msg = 'Photo must be JPG, PNG, GIF, or WebP.';
                $msgType = 'danger';
            } else {
                $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $newName = uniqid('course_') . '.' . $ext;
                $dest = __DIR__ . '/uploads/' . $newName;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                    if (!empty($photoPath) && file_exists(__DIR__ . '/' . $photoPath)) {
                        unlink(__DIR__ . '/' . $photoPath);
                    }
                    $photoPath = 'uploads/' . $newName;
                }
            }
        }

        if (empty($msg)) {
            if ($id > 0) {
                $stmt = $db->prepare("UPDATE courses SET course_code=?, name=?, description=?, teacher=?, capacity=?, credit_hours=?, category=?, photo=? WHERE id=?");
                $stmt->execute([$courseCode, $name, $description, $teacher, $capacity, $creditHours, $category, $photoPath, $id]);
                $msg = 'Course updated successfully.';
                $msgType = 'success';
            } else {
                $stmt = $db->prepare("INSERT INTO courses (course_code, name, description, teacher, capacity, credit_hours, category, photo, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$courseCode, $name, $description, $teacher, $capacity, $creditHours, $category, $photoPath, $_SESSION['user_id']]);
                $id = $db->lastInsertId();
                $msg = 'Course added successfully.';
                $msgType = 'success';
            }


            if ($id > 0) {
                $stmt = $db->prepare("DELETE FROM course_prerequisites WHERE course_id = ?");
                $stmt->execute([$id]);
                if (!empty($prereqIds)) {
                    $stmt = $db->prepare("INSERT INTO course_prerequisites (course_id, prerequisite_course_id) VALUES (?, ?)");
                    foreach ($prereqIds as $pid) {
                        $pid = (int)$pid;
                        if ($pid !== $id) {
                            $stmt->execute([$id, $pid]);
                        }
                    }
                }
            }
        }
    }
}


$stmt = $db->prepare("
    SELECT c.*,
        (SELECT COUNT(*) FROM registrations WHERE course_id = c.id) AS enrolled_count
    FROM courses c
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();


$stmt = $db->prepare("SELECT id, course_code, name FROM courses ORDER BY course_code");
$stmt->execute();
$allCourses = $stmt->fetchAll();

$editCourse = null;
$editPrereqs = [];
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$editId]);
    $editCourse = $stmt->fetch();

    if ($editCourse) {
        $stmt = $db->prepare("SELECT prerequisite_course_id FROM course_prerequisites WHERE course_id = ?");
        $stmt->execute([$editId]);
        $editPrereqs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - read Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <a href="index.php" class="sidebar-logo">read <span>Portal</span></a>
            <nav class="sidebar-nav">
                <a href="admin-dashboard.php">Dashboard</a>
                <a href="admin-courses.php" class="active"> Courses</a>
                <a href="admin-users.php"> Users</a>
            </nav>
            <div class="sidebar-user">
                <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                <?= htmlspecialchars($_SESSION['user_email']) ?>
            </div>
        </aside>

        <div class="main-area">
            <div class="topbar">
                <h2><?= $editCourse ? 'Edit Course' : 'Manage Courses' ?></h2>
                <div class="topbar-right">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <span class="badge badge-gold">Admin</span>
                    <a href="logout.php" class="btn btn-sm btn-outline">Logout</a>
                </div>
            </div>

            <div class="content-body">
                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="dash-form">
                    <h3><?= $editCourse ? 'Edit Course' : 'Add New Course' ?></h3>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <?php if ($editCourse): ?>
                            <input type="hidden" name="course_id" value="<?= $editCourse['id'] ?>">
                            <input type="hidden" name="existing_photo" value="<?= htmlspecialchars($editCourse['photo']) ?>">
                        <?php endif; ?>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Course Code</label>
                                <input type="text" class="dash-input" name="course_code" value="<?= $editCourse ? htmlspecialchars($editCourse['course_code']) : '' ?>" required placeholder="e.g. CS101">
                            </div>
                            <div>
                                <label class="form-label">Course Name</label>
                                <input type="text" class="dash-input" name="name" value="<?= $editCourse ? htmlspecialchars($editCourse['name']) : '' ?>" required>
                            </div>
                        </div>

                        <div class="form-full">
                            <label class="form-label">Description</label>
                            <textarea class="dash-textarea" name="description" required><?= $editCourse ? htmlspecialchars($editCourse['description']) : '' ?></textarea>
                        </div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Teacher Name</label>
                                <input type="text" class="dash-input" name="teacher" value="<?= $editCourse ? htmlspecialchars($editCourse['teacher']) : '' ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Capacity (Total Seats)</label>
                                <input type="number" class="dash-input" name="capacity" min="1" value="<?= $editCourse ? $editCourse['capacity'] : '30' ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Credit Hours</label>
                                <input type="number" class="dash-input" name="credit_hours" min="1" max="6" value="<?= $editCourse ? ($editCourse['credit_hours'] ?: 3) : '3' ?>" required>
                            </div>
                            <div>
                                <label class="form-label">Category</label>
                                <input type="text" class="dash-input" name="category" list="catList" value="<?= $editCourse ? htmlspecialchars($editCourse['category']) : '' ?>" required>
                                <datalist id="catList">
                                    <option value="Programming">
                                    <option value="Design">
                                    <option value="Business">
                                    <option value="Language">
                                    <option value="Science">
                                    <option value="Mathematics">
                                    <option value="Engineering">
                                    <option value="Arts">
                                </datalist>
                            </div>
                        </div>

                        <div class="form-row">
                            <div>
                                <label class="form-label">Photo (optional)</label>
                                <input type="file" class="dash-input" name="photo" accept="image/*">
                                <?php if ($editCourse && !empty($editCourse['photo'])): ?>
                                    <div style="margin-top:6px;font-size:.8rem;color:#6b7280;">
                                        Current: <a href="<?= htmlspecialchars($editCourse['photo']) ?>" target="_blank">View</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="form-label">Prerequisites (optional)</label>
                                <select class="dash-input" name="prerequisites[]" multiple style="min-height:100px;">
                                    <?php foreach ($allCourses as $ac):
                                        $selected = in_array($ac['id'], $editPrereqs);
                                        $disabled = $editCourse && $ac['id'] == $editCourse['id'];
                                    ?>
                                        <option value="<?= $ac['id'] ?>" <?= $selected ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                            <?= htmlspecialchars($ac['course_code'] . ' - ' . $ac['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                            </div>
                        </div>

                        <div style="display:flex;gap:12px;margin-top:8px;">
                            <button type="submit" class="btn btn-gold"><?= $editCourse ? 'Update Course' : 'Add Course' ?></button>
                            <?php if ($editCourse): ?>
                                <a href="admin-courses.php" class="btn btn-outline">Cancel</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Teacher</th>
                                <th>Category</th>
                                <th>Credits</th>
                                <th>Prerequisites</th>
                                <th>Enrolled</th>
                                <th>Capacity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($courses)): ?>
                                <tr><td colspan="10" style="text-align:center;color:#9ca3af;">No courses yet. Add one above.</td></tr>
                            <?php else: ?>
                                <?php foreach ($courses as $course):
                                    $prereqs = getPrerequisites($course['id']);
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($course['course_code']) ?></strong></td>
                                        <td>
                                            <?php if (!empty($course['photo'])): ?>
                                                <img class="course-img" src="<?= htmlspecialchars($course['photo']) ?>" alt="">
                                            <?php else: ?>
                                                <div class="course-img" style="display:flex;align-items:center;justify-content:center;color:#b0b0b0;font-size:.6rem;">N/A</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?= htmlspecialchars($course['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($course['teacher']) ?></td>
                                        <td><span class="badge badge-gold"><?= htmlspecialchars($course['category']) ?></span></td>
                                        <td><?= $course['credit_hours'] ?: '-' ?></td>
                                        <td>
                                            <?php if (empty($prereqs)): ?>
                                                <span style="color:#9ca3af;">None</span>
                                            <?php else: ?>
                                                <?php foreach ($prereqs as $pr): ?>
                                                    <span class="badge badge-blue"><?= htmlspecialchars($pr['course_code']) ?></span>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $course['enrolled_count'] ?></td>
                                        <td><?= $course['capacity'] ?></td>
                                        <td style="display:flex;gap:6px;">
                                            <a href="?edit=<?= $course['id'] ?>" class="btn btn-sm btn-navy">Edit</a>
                                            <a href="?delete=<?= $course['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course? This will also remove all registrations.')">Delete</a>
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
