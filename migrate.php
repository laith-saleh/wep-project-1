<?php

try {
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("USE student_course_db");

    echo "=== Starting migration ===\n\n";

  
    $pdo->exec("DROP TABLE IF EXISTS students");
    $pdo->exec("CREATE TABLE students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_number VARCHAR(50) UNIQUE,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created 'students' table.\n";

  
    $pdo->exec("DROP TABLE IF EXISTS admins");
    $pdo->exec("CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created 'admins' table.\n";

    $pdo->exec("DROP TABLE IF EXISTS course_prerequisites");
    $pdo->exec("CREATE TABLE course_prerequisites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT NOT NULL,
        prerequisite_course_id INT NOT NULL,
        UNIQUE KEY unique_prereq (course_id, prerequisite_course_id),
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (prerequisite_course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created 'course_prerequisites' table.\n";

    $pdo->exec("DROP TABLE IF EXISTS registrations");
    $pdo->exec("CREATE TABLE registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        registration_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_registration (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created 'registrations' table.\n";

  
    $pdo->exec("DROP TABLE IF EXISTS completed_courses");
    $pdo->exec("CREATE TABLE completed_courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        grade VARCHAR(10) DEFAULT NULL,
        UNIQUE KEY unique_completion (student_id, course_id),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created 'completed_courses' table.\n";

   
    try {
        $pdo->exec("ALTER TABLE courses ADD COLUMN course_code VARCHAR(20) UNIQUE AFTER id");
        echo "Added 'course_code' to courses.\n";
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "'course_code' already exists.\n";
        } else {
            throw $e;
        }
    }
    try {
        $pdo->exec("ALTER TABLE courses ADD COLUMN credit_hours INT DEFAULT 3 AFTER capacity");
        echo "Added 'credit_hours' to courses.\n";
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            echo "'credit_hours' already exists.\n";
        } else {
            throw $e;
        }
    }


    $users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $studentCount = 0;
    $adminCount = 0;

    foreach ($users as $user) {
        if ($user['role'] === 'student') {
            $studentCount++;
            $num = str_pad($studentCount, 4, '0', STR_PAD_LEFT);
            $studentNumber = 'STU' . $num;

            $stmt = $pdo->prepare("INSERT IGNORE INTO students (student_number, name, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$studentNumber, $user['name'], $user['email'], $user['password']]);
        } elseif ($user['role'] === 'admin') {
            $adminCount++;
            $stmt = $pdo->prepare("INSERT IGNORE INTO admins (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$user['name'], $user['email'], $user['password']]);
        }
    }
    echo "Migrated $studentCount students, $adminCount admins.\n";

   
    $enrollments = $pdo->query("
        SELECT e.user_id, e.course_id, e.enrolled_at, u.role
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    $migratedRegs = 0;

    foreach ($enrollments as $enr) {
        if ($enr['role'] !== 'student') continue;

        $stmt = $pdo->prepare("SELECT id FROM students WHERE email = (SELECT email FROM users WHERE id = ?)");
        $stmt->execute([$enr['user_id']]);
        $studentId = $stmt->fetchColumn();

        if ($studentId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO registrations (student_id, course_id, registration_date) VALUES (?, ?, ?)");
            $stmt->execute([$studentId, $enr['course_id'], $enr['enrolled_at']]);
            $migratedRegs++;
        }
    }
    echo "Migrated $migratedRegs registrations.\n";


    $courses = $pdo->query("SELECT id, name FROM courses WHERE course_code IS NULL")->fetchAll(PDO::FETCH_ASSOC);
    $prefixes = ['CS', 'DS', 'EN', 'MA', 'PH', 'CH', 'BI', 'BU', 'AR'];
    $i = 0;
    foreach ($courses as $course) {
        $p = $prefixes[$i % count($prefixes)];
        $num = str_pad(($i + 1) * 100, 3, '0', STR_PAD_LEFT);
        $code = $p . $num;
        $stmt = $pdo->prepare("UPDATE courses SET course_code = ? WHERE id = ?");
        $stmt->execute([$code, $course['id']]);
        $i++;
        echo "  Generated course_code $code for '{$course['name']}'\n";
    }

  
    $fks = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'student_course_db'
          AND TABLE_NAME = 'courses'
          AND REFERENCED_TABLE_NAME = 'users'
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($fks as $fk) {
        $pdo->exec("ALTER TABLE courses DROP FOREIGN KEY `$fk`");
        echo "Dropped foreign key '$fk' on courses.\n";
    }
  
    try {
        $pdo->exec("ALTER TABLE courses MODIFY created_by INT DEFAULT NULL");
    } catch (Exception $e) {
        echo "Note: could not modify created_by: " . $e->getMessage() . "\n";
    }

    echo "\n=== Migration complete! ===\n";

} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
    exit(1);
}
