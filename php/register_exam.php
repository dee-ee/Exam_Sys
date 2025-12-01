<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

// Check if exam_id is provided
if (!isset($_GET['exam_id']) && !isset($_POST['exam_id'])) {
    die("No exam selected.");
}

$student_id = $_SESSION['user_id'];
$exam_id = isset($_POST['exam_id']) ? intval($_POST['exam_id']) : intval($_GET['exam_id']);

// Database connection
$dbPath = __DIR__ . '/../Data/data.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if already registered
    $check = $db->prepare("SELECT id FROM registrations WHERE student_id = ? AND exam_id = ?");
    $check->execute([$student_id, $exam_id]);
    
    if ($check->fetch()) {
        echo "<h2>You are already registered for this exam.</h2>";
        echo "<a href='../ExamList.html'>Back to Calendar</a>";
        exit();
    }

    // Only process registration on POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Insert registration
        $stmt = $db->prepare("INSERT INTO registrations (student_id, exam_id) VALUES (?, ?)");
        $stmt->execute([$student_id, $exam_id]);

        // Redirect to confirmation page
        header("Location: ../confirmation.html");
        exit();
    }

    // GET request - show confirmation form
    // Fetch exam details to display
    $examStmt = $db->prepare("SELECT exam_name, exam_date, exam_time, campus, room_number FROM exams WHERE exam_id = ?");
    $examStmt->execute([$exam_id]);
    $exam = $examStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        die("Exam not found.");
    }

} catch (PDOException $e) {
    echo "<h2>Registration Error</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            color: #333;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 15px 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .exam-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .exam-details p {
            margin: 8px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        .btn-confirm {
            background-color: #28a745;
            color: white;
        }
        .btn-confirm:hover {
            background-color: #218838;
        }
        .btn-cancel {
            background-color: #6c757d;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>

<header>
    <h1>Confirm Registration</h1>
</header>

<div class="container">
    <h2>Are you sure you want to register for this exam?</h2>
    
    <div class="exam-details">
        <p><strong>Exam:</strong> <?= htmlspecialchars($exam['exam_name']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($exam['exam_date']) ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars($exam['exam_time']) ?></p>
        <p><strong>Campus:</strong> <?= htmlspecialchars($exam['campus']) ?></p>
        <p><strong>Room:</strong> <?= htmlspecialchars($exam['room_number']) ?></p>
    </div>

    <form method="POST" action="register_exam.php?exam_id=<?= $exam_id ?>">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <button type="submit" class="btn btn-confirm">Yes, Register Me</button>
        <a href="available_exams.php" class="btn btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>