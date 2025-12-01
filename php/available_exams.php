<?php
// available_exams.php

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

// Fetch all exams from the database
$stmt = $db->query("SELECT exam_id, exam_name, exam_date, exam_time, campus, room_number FROM exams ORDER BY exam_date ASC");

$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include the HTML template - go UP one directory to find it
include __DIR__ . '/../available_exams.html';
?>