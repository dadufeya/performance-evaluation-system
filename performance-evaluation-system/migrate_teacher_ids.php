<?php
require_once 'config/config.php';

// 1. Add ID columns to teachers table if they don't exist
try {
    $pdo->exec("ALTER TABLE teachers ADD COLUMN IF NOT EXISTS year_id INT(11) AFTER year");
    $pdo->exec("ALTER TABLE teachers ADD COLUMN IF NOT EXISTS section_id INT(11) AFTER section");
    $pdo->exec("ALTER TABLE teachers ADD COLUMN IF NOT EXISTS course_id INT(11) AFTER course_info");
} catch (Exception $e) {
    echo "Schema Update Error: " . $e->getMessage() . "\n";
}

try {
    $pdo->beginTransaction();

    // 2. Populate course_id based on course_info string matching course_name (or name)
    // Checking both common column names for course name
    try {
        $pdo->exec("UPDATE teachers t JOIN courses c ON t.course_info = c.course_name SET t.course_id = c.course_id");
    } catch (Exception $e) {
        $pdo->exec("UPDATE teachers t JOIN courses c ON t.course_info = c.name SET t.course_id = c.course_id");
    }

    // 3. Populate year_id based on year string matching year_name
    $pdo->exec("UPDATE teachers t JOIN academic_years a ON t.year = a.year_name SET t.year_id = a.year_id");

    // 4. Populate section_id based on section string matching section_number
    $pdo->exec("UPDATE teachers t JOIN sections s ON t.section = s.section_number AND t.year_id = s.year_id SET t.section_id = s.section_id");

    $pdo->commit();
    echo "Teacher table migration completed.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Migration Error: " . $e->getMessage() . "\n";
}
?>
