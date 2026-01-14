<?php
require_once 'config/config.php';

echo "<pre>";
echo "Starting Database Seeding...\n";

try {
    // 1. Clear existing data (Optional, but good for clean tests)
    // Be careful with delete order due to foreign keys if they exist
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
    // $pdo->exec("TRUNCATE TABLE evaluation_responses");
    // $pdo->exec("TRUNCATE TABLE evaluations");
    // $pdo->exec("TRUNCATE TABLE evaluation_assignments");
    // $pdo->exec("TRUNCATE TABLE questions");
    // $pdo->exec("TRUNCATE TABLE questionnaires");
    // $pdo->exec("TRUNCATE TABLE students");
    // $pdo->exec("TRUNCATE TABLE teachers");
    // $pdo->exec("TRUNCATE TABLE users");
    // $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 2. Roles (Manual Check - usually IDs are 1: Admin, 2: Teacher, 3: Student)
    // We assume these exist or insert them
    $pdo->exec("INSERT IGNORE INTO roles (role_id, role_name) VALUES (1, 'admin'), (2, 'teacher'), (3, 'student')");

    // 3. Departments
    $pdo->exec("INSERT IGNORE INTO departments (department_id, department_name) VALUES (1, 'Computer Science'), (2, 'ECE')");
    echo "Departments seeded.\n";

    // 4. Academic Years
    $pdo->exec("INSERT IGNORE INTO academic_years (year_id, year_name, department_id) VALUES 
        (1, '1st Year', 1), 
        (2, '2nd Year', 1),
        (3, '3rd Year', 1),
        (4, '4th Year', 1),
        (5, '5th Year', 2)");
    echo "Academic Years seeded.\n";

    // 5. Sections
    $pdo->exec("INSERT IGNORE INTO sections (section_id, section_number, year_id) VALUES 
        (1, 'Sec 1', 1), 
        (2, 'Sec 2', 1),
        (3, 'Sec 3', 2),
        (4, 'Sec 4', 4)");
    echo "Sections seeded.\n";

    // 6. Courses
    $pdo->exec("INSERT IGNORE INTO courses (course_id, course_name, department_id) VALUES 
        (1, 'Introduction to Programming', 1), 
        (2, 'Database Systems', 1),
        (3, 'Computer Networks', 1),
        (4, 'Digital Logic Design', 2)");
    echo "Courses seeded.\n";

    // 7. Sample Admin
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (user_id, username, password, full_name, role_id, status) VALUES 
        (1, 'admin', '$adminPass', 'System Administrator', 1, 'active')");
    echo "Admin user created (User: admin / Pass: admin123).\n";

    // 8. Sample Teacher
    $teacherPass = password_hash('teacher123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT IGNORE INTO users (user_id, username, password, full_name, role_id, status, temp_pass) VALUES 
        (2, 'john_doe', '$teacherPass', 'John Doe', 2, 'active', 'teacher123')");
    // Link to teacher table (4th Year, Sec 4, Intro to Programming)
    $pdo->exec("INSERT IGNORE INTO teachers (teacher_id, user_id, full_name, email, phone, department_id, course_id, year_id, section_id, course_info, year, section) VALUES 
        ('T1001', 2, 'John Doe', 'john@univ.edu', '0912345678', 1, 1, 4, 4, 'Introduction to Programming', '4th Year', 'Sec 4')");
    echo "Teacher created (User: john_doe / Pass: teacher123).\n";

    // 9. Sample Student
    $studentPass = password_hash('student123', PASSWORD_DEFAULT);
    $pdo->query("INSERT IGNORE INTO users (user_id, username, password, full_name, role_id, status) VALUES 
        (3, 'student_test', '$studentPass', 'Test Student', 3, 'active')");
    // Link to student table (4th Year, Sec 4)
    $pdo->exec("INSERT IGNORE INTO students (user_id, full_name, student_id_card, gender, batch, semester, department_id, year_id, section_id) VALUES 
        (3, 'Test Student', 'STU/TEST/01', 'Male', '2023', '1', 1, 4, 4)");
    echo "Student created (User: student_test / Pass: student123).\n";

    // 10. Sample Questionnaire
    $pdo->exec("INSERT IGNORE INTO questionnaires (questionnaire_id, title, description, status) VALUES 
        (1, 'End of Semester Teacher Evaluation', 'Please provide honest feedback about your instructor.', 'active')");
    
    // 11. Sample Questions
    $pdo->exec("INSERT IGNORE INTO questions (questionnaire_id, question_text, question_type) VALUES 
        (1, 'The instructor explains concepts clearly.', 'scale'),
        (1, 'The instructor is punctual and well-prepared.', 'scale'),
        (1, 'The instructor encourages student participation.', 'scale'),
        (1, 'Would you recommend this instructor to other students?', 'boolean'),
        (1, 'Any additional comments?', 'text')");
    echo "Questionnaire and Questions seeded.\n";

    echo "\nSeeding Completed Successfully!\n";
    echo "You can now login as admin/admin123 to assign more evaluations or student_test/student123 to test the evaluation flow.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
echo "</pre>";
