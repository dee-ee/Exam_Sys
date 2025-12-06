<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Protect page - only students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.html");
    exit();
}

// DB path
$dbPath = __DIR__ . '/../Data/data.sqlite';
if (!file_exists($dbPath)) {
    die("Database file not found: $dbPath");
}

// Connect
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Date filter
$selectedDate = $_GET['date'] ?? null;

// ---- TIME SORT EXPRESSION (AM/PM safe) ----
$timeSort = "
(
    CASE
        WHEN substr(e.exam_time, -2) = 'PM'
             AND substr(e.exam_time, 1, 2) != '12'
        THEN CAST(substr(e.exam_time, 1, 2) AS INTEGER) + 12

        WHEN substr(e.exam_time, -2) = 'AM'
             AND substr(e.exam_time, 1, 2) = '12'
        THEN 0

        ELSE CAST(substr(e.exam_time, 1, 2) AS INTEGER)
    END * 60
    +
    CAST(substr(e.exam_time, 4, 2) AS INTEGER)
)
";

if ($selectedDate) {

    // Single day → sort by TIME
    $stmt = $db->prepare("
        SELECT
            e.exam_id, e.exam_name, e.exam_date, e.exam_time,
            e.campus, e.room_number, e.capacity,
            (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) AS registered_count
        FROM exams e
        WHERE e.exam_date = ?
          AND (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) < e.capacity
        ORDER BY $timeSort ASC
    ");
    $stmt->execute([$selectedDate]);

} else {

    // All days → sort by DATE then TIME
    $stmt = $db->query("
        SELECT
            e.exam_id, e.exam_name, e.exam_date, e.exam_time,
            e.campus, e.room_number, e.capacity,
            (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) AS registered_count
        FROM exams e
        WHERE (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) < e.capacity
        ORDER BY
            e.exam_date ASC,
            $timeSort ASC
    ");
}

$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display heading
$displayDate = $selectedDate
    ? date('F j, Y', strtotime($selectedDate))
    : 'All Dates';

// Render HTML
include __DIR__ . '/../available_exams.html';
?>
