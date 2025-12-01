<?php
//---------------------------------------------------------
// BOOK EXAM PAGE (STUDENTS ONLY)
//
// PURPOSE:
//   Handles inserting student exam bookings.
//
// INPUT:
//   exam_id posted from ExamList.php form
//
// RULES ENFORCED:
//   ✅ Max 3 total bookings per student
//   ✅ No duplicate bookings
//   ✅ Capacity check per exam
//---------------------------------------------------------

session_start();

//---------------------------------------------------------
// SECURITY CHECK — STUDENT ONLY
//---------------------------------------------------------

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.html");
    exit();
}

//---------------------------------------------------------
// SHOW ERRORS DURING DEV
//---------------------------------------------------------

error_reporting(E_ALL);
ini_set('display_errors', 1);

//---------------------------------------------------------
// DATABASE CONNECTION
//---------------------------------------------------------

$dbPath = __DIR__ . '/../Data/data.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    die("DB error: " . $e->getMessage());
}

//---------------------------------------------------------
// GET SUBMITTED DATA
//---------------------------------------------------------

$exam_id = intval($_POST['exam_id'] ?? 0);
$student_id = $_SESSION['user_id'];


//---------------------------------------------------------
// BASIC VALIDATION
//---------------------------------------------------------

if ($exam_id <= 0) {
    exit("❌ No exam was selected.");
}


//---------------------------------------------------------
// RULE #1 — MAX 3 BOOKINGS TOTAL
//---------------------------------------------------------

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM registrations
    WHERE student_id = ?
");

$stmt->execute([$student_id]);

$totalBookings = $stmt->fetchColumn();

if ($totalBookings >= 3) {
    exit("❌ Booking limit reached (3 exams max).");
}


//---------------------------------------------------------
// RULE #2 — PREVENT DUPLICATE BOOKINGS
//---------------------------------------------------------

$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM registrations
    WHERE student_id = ?
      AND exam_id = ?
");

$stmt->execute([$student_id, $exam_id]);

if ($stmt->fetchColumn() > 0) {
    exit("❌ You already booked this exam.");
}


//---------------------------------------------------------
// RULE #3 — SEAT CAPACITY CHECK
//---------------------------------------------------------

// Count current bookings for this exam
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM registrations
    WHERE exam_id = ?
");

$stmt->execute([$exam_id]);

$bookedSeats = $stmt->fetchColumn();


// Retrieve capacity limit from exams table
$stmt = $db->prepare("
    SELECT capacity
    FROM exams
    WHERE id = ?
");

$stmt->execute([$exam_id]);

$capacity = $stmt->fetchColumn();


// Compare values
if ($bookedSeats >= $capacity) {
    exit("❌ This exam session is already full.");
}


//---------------------------------------------------------
// FINAL STEP — INSERT REGISTRATION
//---------------------------------------------------------

$stmt = $db->prepare("
    INSERT INTO registrations
        (student_id, exam_id)
    VALUES (?, ?)
");

// Create booking
$stmt->execute([$student_id, $exam_id]);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Complete</title>
</head>

<body>

<h1>✅ Exam Registration Confirmed</h1>

<p>
    Your reservation has been saved.
</p>

<a href="../ExamList.php">
    Return to Exam List
</a>

</body>
</html>
