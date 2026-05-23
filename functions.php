<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'student_course_db');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    return $pdo;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $db = getDB();
    $table = $_SESSION['user_type'] === 'admin' ? 'admins' : 'students';
    $stmt = $db->prepare("SELECT id, name, email FROM $table WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function isRegistered($studentId, $courseId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM registrations WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$studentId, $courseId]);
    return $stmt->fetch() ? true : false;
}

function isCapacityFull($courseId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT capacity, (SELECT COUNT(*) FROM registrations WHERE course_id = ?) AS enrolled FROM courses WHERE id = ?");
    $stmt->execute([$courseId, $courseId]);
    $course = $stmt->fetch();
    return $course && $course['enrolled'] >= $course['capacity'];
}

function checkPrerequisites($studentId, $courseId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT cp.id, cp.prerequisite_course_id, c.name AS prereq_name, c.course_code,
            (SELECT id FROM completed_courses WHERE student_id = ? AND course_id = cp.prerequisite_course_id) AS completed
        FROM course_prerequisites cp
        JOIN courses c ON cp.prerequisite_course_id = c.id
        WHERE cp.course_id = ?
    ");
    $stmt->execute([$studentId, $courseId]);
    $prereqs = $stmt->fetchAll();

    $unmet = [];
    foreach ($prereqs as $p) {
        if (!$p['completed']) {
            $unmet[] = $p;
        }
    }
    return $unmet;
}

function getPrerequisites($courseId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.id, c.course_code, c.name
        FROM course_prerequisites cp
        JOIN courses c ON cp.prerequisite_course_id = c.id
        WHERE cp.course_id = ?
    ");
    $stmt->execute([$courseId]);
    return $stmt->fetchAll();
}
