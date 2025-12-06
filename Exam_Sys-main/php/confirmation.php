<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Protect page - must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

// DB Connection
$dbPath = __DIR__ . '/../Data/data.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

$userId = $_SESSION['user_id'];

// Get the most recent registration for this student
$stmt = $db->prepare("
    SELECT r.registration_id, r.booked_at, 
           e.exam_name, e.exam_date, e.exam_time, e.campus, e.room_number,
           u.first_name as teacher_first, u.last_name as teacher_last
    FROM registrations r
    JOIN exams e ON r.exam_id = e.exam_id
    LEFT JOIN users u ON e.teacher_id = u.id
    WHERE r.student_id = ?
    ORDER BY r.booked_at DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$registration = $stmt->fetch(PDO::FETCH_ASSOC);

// Get student name
$stmtUser = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$student = $stmtUser->fetch(PDO::FETCH_ASSOC);
$studentName = $student['first_name'] . ' ' . $student['last_name'];

// Teacher name
$teacherName = ($registration && $registration['teacher_first']) ? $registration['teacher_first'] . ' ' . $registration['teacher_last'] : 'TBD';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Registration Confirmed</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <script src="../js/modernizr.custom.40753.js"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 20px;
        }

        .success-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .success-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 15px;
        }

        .success-card h1 {
            margin: 0 0 10px 0;
            color: #4CAF50;
        }

        .success-card p {
            color: #666;
            margin: 0;
        }

        .details-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .details-card h2 {
            margin: 0 0 20px 0;
            color: #333;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
        }

        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: bold;
            color: #333;
            width: 140px;
            flex-shrink: 0;
        }

        .detail-value {
            color: #555;
        }

        .exam-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #e3f2fd;
            color: #1976D2;
            border-radius: 15px;
            font-size: 14px;
        }

        .btn-group {
            text-align: center;
            margin-top: 20px;
        }

        .home-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }

        .home-btn:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <header>
        <img src="../images/csnlogo.jpg" alt="Logo">
    </header>

    <div class="confirmation-container">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1>Registration Confirmed!</h1>
            <p>You have successfully registered for the exam.</p>
        </div>

        <?php if ($registration): ?>
        <div class="details-card">
            <h2>Registration Details</h2>

            <div class="detail-row">
                <span class="detail-label">Student Name</span>
                <span class="detail-value"><?= htmlspecialchars($studentName) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Exam Name</span>
                <span class="detail-value"><strong><?= htmlspecialchars($registration['exam_name']) ?></strong></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value"><?= htmlspecialchars($registration['exam_date']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Time</span>
                <span class="detail-value"><?= htmlspecialchars($registration['exam_time']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Campus</span>
                <span class="detail-value"><span class="exam-badge"><?= htmlspecialchars($registration['campus']) ?></span></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Room</span>
                <span class="detail-value"><?= htmlspecialchars($registration['room_number']) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Proctor</span>
                <span class="detail-value"><?= htmlspecialchars($teacherName) ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Booked At</span>
                <span class="detail-value"><?= htmlspecialchars($registration['booked_at']) ?></span>
            </div>
        </div>
        <?php else: ?>
        <div class="details-card">
            <p style="text-align: center; color: #666;">No registration found.</p>
        </div>
        <?php endif; ?>

        <div class="btn-group">
            <a href="ExamList.php" class="home-btn">← Return to My Exams</a>
        </div>
    </div>

</body>
</html>