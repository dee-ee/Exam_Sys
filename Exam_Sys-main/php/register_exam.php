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

    // Check how many registrations the student already has
    $countStmt = $db->prepare("SELECT COUNT(*) FROM registrations WHERE student_id = ?");
    $countStmt->execute([$student_id]);
    $registrationCount = $countStmt->fetchColumn();

    // Limit to 3 registrations per student
    if ($registrationCount >= 3) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Registration Limit Reached</title>
            <link rel="stylesheet" href="../css/styles.css">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #2196F3;
                    margin: 0;
                    padding: 0;
                    color: #333;
                }
                header {
                    background-color: #1976D2;
                    color: white;
                    padding: 15px 0;
                    text-align: center;
                }
                .container {
                    max-width: 600px;
                    margin: 40px auto;
                    padding: 30px;
                    background-color: #fff;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .error-icon {
                    font-size: 60px;
                    color: #e74c3c;
                    margin-bottom: 15px;
                }
                h2 {
                    color: #e74c3c;
                    margin-bottom: 15px;
                }
                p {
                    color: #666;
                    margin-bottom: 20px;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 25px;
                    margin: 10px 5px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    text-decoration: none;
                    font-size: 16px;
                    font-weight: bold;
                }
                .btn-back {
                    background-color: #2196F3;
                    color: white;
                }
                .btn-back:hover {
                    background-color: #1976D2;
                }
            </style>
        </head>
        <body>
            <header>
                <img src="../images/csnlogo.jpg" alt="Logo" style="height: 60px;">
            </header>
            <div class="container">
                <div class="error-icon">⚠</div>
                <h2>Registration Limit Reached</h2>
                <p>You can only register for a maximum of <strong>3 exams</strong>.</p>
                <p>Please cancel an existing registration before signing up for a new exam.</p>
                <a href="ExamList.php" class="btn btn-back">← Back to My Exams</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    // Get exam details and check capacity
    $examStmt = $db->prepare("
        SELECT e.exam_name, e.exam_date, e.exam_time, e.campus, e.room_number, e.capacity,
               (SELECT COUNT(*) FROM registrations r WHERE r.exam_id = e.exam_id) as registered_count
        FROM exams e
        WHERE e.exam_id = ?
    ");
    $examStmt->execute([$exam_id]);
    $exam = $examStmt->fetch(PDO::FETCH_ASSOC);

    if (!$exam) {
        die("Exam not found.");
    }

    $spotsLeft = $exam['capacity'] - $exam['registered_count'];

    // Check if exam is full
    if ($spotsLeft <= 0) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Exam Full</title>
            <link rel="stylesheet" href="../css/styles.css">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #2196F3;
                    margin: 0;
                    padding: 0;
                    color: #333;
                }
                header {
                    background-color: #1976D2;
                    color: white;
                    padding: 15px 0;
                    text-align: center;
                }
                .container {
                    max-width: 600px;
                    margin: 40px auto;
                    padding: 30px;
                    background-color: #fff;
                    border-radius: 10px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    text-align: center;
                }
                .error-icon {
                    font-size: 60px;
                    color: #e74c3c;
                    margin-bottom: 15px;
                }
                h2 {
                    color: #e74c3c;
                    margin-bottom: 15px;
                }
                p {
                    color: #666;
                    margin-bottom: 20px;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 25px;
                    margin: 10px 5px;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    text-decoration: none;
                    font-size: 16px;
                    font-weight: bold;
                }
                .btn-back {
                    background-color: #2196F3;
                    color: white;
                }
                .btn-back:hover {
                    background-color: #1976D2;
                }
            </style>
        </head>
        <body>
            <header>
                <img src="../images/csnlogo.jpg" alt="Logo" style="height: 60px;">
            </header>
            <div class="container">
                <div class="error-icon">⚠</div>
                <h2>Exam Full</h2>
                <p>Sorry, <strong><?= htmlspecialchars($exam['exam_name']) ?></strong> has reached its maximum capacity of <strong><?= $exam['capacity'] ?> students</strong>.</p>
                <p>Please choose a different exam.</p>
                <a href="available_exams.php" class="btn btn-back">← Back to Available Exams</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }

    // Check if already registered for this specific exam
    $check = $db->prepare("SELECT registration_id FROM registrations WHERE student_id = ? AND exam_id = ?");
    $check->execute([$student_id, $exam_id]);
    
    if ($check->fetch()) {
        echo "<h2>You are already registered for this exam.</h2>";
        echo "<a href='ExamList.php'>Back to My Exams</a>";
        exit();
    }

    // Only process registration on POST request
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Double-check the student limit again before inserting
        $countStmt->execute([$student_id]);
        $registrationCount = $countStmt->fetchColumn();
        
        if ($registrationCount >= 3) {
            header("Location: register_exam.php?exam_id=" . $exam_id);
            exit();
        }

        // Double-check the exam capacity again before inserting
        $examStmt->execute([$exam_id]);
        $exam = $examStmt->fetch(PDO::FETCH_ASSOC);
        $spotsLeft = $exam['capacity'] - $exam['registered_count'];
        
        if ($spotsLeft <= 0) {
            header("Location: register_exam.php?exam_id=" . $exam_id);
            exit();
        }

        // Insert registration
        $stmt = $db->prepare("INSERT INTO registrations (student_id, exam_id) VALUES (?, ?)");
        $stmt->execute([$student_id, $exam_id]);

        // Redirect to confirmation page
        header("Location: confirmation.php");
        exit();
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
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2196F3;
            margin: 0;
            padding: 0;
            color: #333;
        }
        header {
            background-color: #1976D2;
            color: white;
            padding: 15px 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .container h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
        }
        .exam-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .exam-details p {
            margin: 10px 0;
            display: flex;
        }
        .exam-details strong {
            width: 100px;
            flex-shrink: 0;
        }
        .registration-count {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        .spots-left {
            background: #d4edda;
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 10px 15px;
            margin-bottom: 20px;
            color: #155724;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            margin: 10px 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
        }
        .btn-confirm {
            background-color: #4CAF50;
            color: white;
        }
        .btn-confirm:hover {
            background-color: #388E3C;
        }
        .btn-cancel {
            background-color: #e74c3c;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #c0392b;
        }
        .btn-group {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<header>
    <img src="../images/csnlogo.jpg" alt="Logo" style="height: 60px;">
</header>

<div class="container">
    <h2>Confirm Registration</h2>
    
    <div class="registration-count">
        You have <strong><?= $registrationCount ?> of 3</strong> exam registrations used.
    </div>
    
    <div class="spots-left">
        <strong><?= $spotsLeft ?> of <?= $exam['capacity'] ?></strong> spots available for this exam.
    </div>
    
    <p>Are you sure you want to register for this exam?</p>
    
    <div class="exam-details">
        <p><strong>Exam:</strong> <?= htmlspecialchars($exam['exam_name']) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($exam['exam_date']) ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars($exam['exam_time']) ?></p>
        <p><strong>Campus:</strong> <?= htmlspecialchars($exam['campus']) ?></p>
        <p><strong>Room:</strong> <?= htmlspecialchars($exam['room_number']) ?></p>
    </div>

    <div class="btn-group">
        <form method="POST" action="register_exam.php?exam_id=<?= $exam_id ?>" style="display: inline;">
            <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
            <button type="submit" class="btn btn-confirm">Yes, Register Me</button>
        </form>
        <a href="available_exams.php" class="btn btn-cancel">Cancel</a>
    </div>
</div>

</body>
</html>