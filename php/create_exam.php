<?php
//---------------------------------------------------------
// CREATE EXAM PAGE (STAFF ONLY)
//
// PURPOSE:
//   This page allows STAFF users to create new exam
//   sessions that students can later register for.
//
// FLOW:
//   Login as STAFF -> Visit this page -> Fill out form
//   -> Submit -> INSERT into exams table
//---------------------------------------------------------

session_start();

//---------------------------------------------------------
// SECURITY CHECK
// Ensures ONLY staff can access this page.
// If user is NOT logged in or role != staff -> redirect
//---------------------------------------------------------

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../index.html");
    exit();
}

//---------------------------------------------------------
// PHP ERROR DISPLAY — for development only
//---------------------------------------------------------

error_reporting(E_ALL);
ini_set('display_errors', 1);

//---------------------------------------------------------
// DATABASE CONNECTION
//---------------------------------------------------------

$dbPath = __DIR__ . '/../Data/data.sqlite';

try {
    // Connect to SQLite database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e) {
    // Stop page if database cannot connect
    die("DB connection failed: " . $e->getMessage());
}

//---------------------------------------------------------
// FORM HANDLING
//---------------------------------------------------------

$message = "";

//---------------------------------------------------------
// Check if THIS PAGE was submitted using POST
//---------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //-----------------------------------------------------
    // Get each form field
    //-----------------------------------------------------

    $exam_name  = trim($_POST['exam_name'] ?? "");
    $campus     = trim($_POST['campus'] ?? "");
    $room       = trim($_POST['room_number'] ?? "");
    $date       = trim($_POST['exam_date'] ?? "");
    $time       = trim($_POST['exam_time'] ?? "");
    $capacity   = intval($_POST['capacity'] ?? 20);

    //-----------------------------------------------------
    // Validate required fields
    //-----------------------------------------------------

    if (!$exam_name || !$campus || !$room || !$date || !$time) {

        $message = "All fields are required.";

    } else {

        //-------------------------------------------------
        // INSERT exam session into database
        //-------------------------------------------------

        $stmt = $db->prepare("
            INSERT INTO exams
                (staff_id, exam_name, campus, room_number,
                 exam_date, exam_time, capacity)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        // Execute query with staff's database ID stored in session
        $stmt->execute([
            $_SESSION['user_id'],  // faculty user who created exam
            $exam_name,
            $campus,
            $room,
            $date,
            $time,
            $capacity
        ]);

        //-------------------------------------------------
        // Feedback message for staff
        //-------------------------------------------------

        $message = "✅ Exam session created successfully!";
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Exam (Staff)</title>
</head>

<body>

<h1>Staff Exam Creation</h1>

<!-- Show confirmation or error messages -->
<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<!-- STAFF CREATE EXAM FORM -->
<form method="POST">

    <input
        type="text"
        name="exam_name"
        placeholder="Exam Name"
        required><br><br>

    <input
        type="text"
        name="campus"
        placeholder="Campus"
        required><br><br>

    <input
        type="text"
        name="room_number"
        placeholder="Room Number"
        required><br><br>

    <input
        type="date"
        name="exam_date"
        required><br><br>

    <input
        type="time"
        name="exam_time"
        required><br><br>

    <!-- Capacity input min & max enforced -->
    <input
        type="number"
        name="capacity"
        value="20"
        min="1"
        max="20"><br><br>

    <button type="submit">
        Create Exam
    </button>

</form>

</body>
</html>

