<?php
// --------------------------------------------------
// CSN EXAM SYSTEM - DATABASE SETUP SCRIPT
// --------------------------------------------------
//
// PURPOSE:
//   This script creates the two core database tables
//   needed for the student exam registration system:
//
//      1. exams         -> stores all exam sessions
//      2. registrations -> tracks student bookings
//
// IMPORTANT:
//   - Run this file ONCE by opening it in a browser.
//   - After setup is complete, you can ignore or delete it.
// --------------------------------------------------

// Enable full error reporting so mistakes are visible
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --------------------------------------------------
// DATABASE CONNECTION
// --------------------------------------------------

// Build absolute path to SQLite database file
$dbPath = __DIR__ . '/../Data/data.sqlite';

try {

    // Create PDO connection to SQLite database
    $db = new PDO("sqlite:$dbPath");

    // Tell PDO to throw exceptions for all DB errors
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enable enforcement of foreign keys (OFF by default in SQLite)
    $db->exec("PRAGMA foreign_keys = ON;");

    // --------------------------------------------------
    // EXAMS TABLE
    // --------------------------------------------------
    //
    // Creates the exams table to store exam session details
    // Teacher names are initially empty and will be updated later.
    $db->exec(" 
        CREATE TABLE IF NOT EXISTS exams (
            exam_id INTEGER PRIMARY KEY AUTOINCREMENT,
            exam_name TEXT NOT NULL,        -- Name of the exam (e.g., 'Math 101 Midterm')
            campus TEXT,                    -- Campus location (e.g., 'Charleston')
            room_number TEXT,               -- Room number for the exam (e.g., 'B101')
            exam_date TEXT,                 -- Date of the exam (e.g., '2025-12-10')
            exam_time TEXT,                 -- Time of the exam (e.g., '09:00 AM')
            capacity INTEGER DEFAULT 20,    -- Maximum capacity for students (default 20)
            teacher_id INTEGER,             -- Foreign key to users (teacher)
            FOREIGN KEY (teacher_id) REFERENCES users(id)
        );
    ");

    // --------------------------------------------------
    // REGISTRATIONS TABLE
    // --------------------------------------------------
    //
    // Creates the registrations table to track student bookings for exams
    $db->exec(" 
        CREATE TABLE IF NOT EXISTS registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,         -- Unique registration ID
            student_id INTEGER NOT NULL,                  -- Foreign key to users (student)
            exam_id INTEGER NOT NULL,                     -- Foreign key to exams (exam session)
            booked_at TEXT DEFAULT (datetime('now')),     -- Timestamp of booking
            grade TEXT,                                   -- Optional grade for post-exam
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,  -- Student is a foreign key
            FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,     -- Exam is a foreign key
            UNIQUE (student_id, exam_id)                  -- Prevents duplicate bookings for the same exam
        );
    ");

    // --------------------------------------------------
    // OPTIONAL: SEED SAMPLE EXAMS (Only insert if the exams table is empty)
    // --------------------------------------------------
    $count = $db->query("SELECT COUNT(*) FROM exams")->fetchColumn();

    // Only insert sample data if no exams exist in the table
    if ($count == 0) {
        // Insert sample exam sessions with teacher_id set to NULL (to be updated later)
        $db->exec("
            INSERT INTO exams (exam_name, campus, room_number, exam_date, exam_time, capacity, teacher_id)
            VALUES
                ('Math 120 Midterm', 'Charleston', 'B101', '2025-12-10', '09:00 AM', 20, NULL),
                ('ENG 101 Final', 'Charleston', 'C202', '2025-12-11', '01:00 PM', 20, NULL),
                ('CS 135 Quiz 3', 'North Las Vegas', 'D303', '2025-12-12', '11:00 AM', 20, NULL);
        ");

        echo "<p>Sample exam sessions inserted.</p>";
    }

    // --------------------------------------------------
    // SUCCESS MESSAGE
    // --------------------------------------------------
    echo "<h2>✅ Exams and registrations tables are ready.</h2>";
    echo "<p>You can now manually add exam sessions using an admin interface or by directly inserting data into the exams table.</p>";

}
// --------------------------------------------------
// REQUIRED CATCH BLOCK
// --------------------------------------------------
catch (PDOException $e) {

    echo "<h2>Database Setup Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";

}
// --------------------------------------------------
?>