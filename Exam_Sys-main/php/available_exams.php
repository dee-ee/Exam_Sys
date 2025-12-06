<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Protect page - only students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.html");
    exit();
}

// Correct path to the SQLite database
$dbPath = __DIR__ . '/../Data/data.sqlite';

// Check if the database file exists
if (!file_exists($dbPath)) {
    die("Database file not found: $dbPath");
}

// Establish the SQLite database connection
try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON;");
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Get the date from URL parameter
$selectedDate = $_GET['date'] ?? null;

// Fetch exams - filter by date if provided, and only show exams that aren't full
if ($selectedDate) {
    $stmt = $db->prepare("
        SELECT e.exam_id, e.exam_name, e.exam_date, e.exam_time, e.campus, e.room_number, e.capacity,
               (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) as registered_count
        FROM exams e
        WHERE e.exam_date = ?
        HAVING registered_count < e.capacity
        ORDER BY e.exam_time ASC
    ");
    $stmt->execute([$selectedDate]);
} else {
    $stmt = $db->query("
        SELECT e.exam_id, e.exam_name, e.exam_date, e.exam_time, e.campus, e.room_number, e.capacity,
               (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) as registered_count
        FROM exams e
        HAVING registered_count < e.capacity
        ORDER BY e.exam_date ASC, e.exam_time ASC
    ");
}

$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format the date for display
$displayDate = $selectedDate ? date('F j, Y', strtotime($selectedDate)) : 'All Dates';

// Include the HTML template - go UP one directory to find it
include __DIR__ . '/../available_exams.html';
?>