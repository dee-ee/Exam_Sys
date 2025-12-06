<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Protect page - only students allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
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

// Get student ID
$userId = $_SESSION['user_id'];

// Handle cancellations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registrations'])) {
    $selectedRegistrations = $_POST['registration_ids'] ?? [];

    if (!empty($selectedRegistrations)) {
        $placeholders = implode(',', array_fill(0, count($selectedRegistrations), '?'));
        $stmt = $db->prepare("
            DELETE FROM registrations 
            WHERE registration_id IN ($placeholders) AND student_id = ?
        ");
        $stmt->execute([...$selectedRegistrations, $userId]);
    }

    header("Location: ExamList.php");
    exit();
}

// Fetch student registrations
$stmt = $db->prepare("
    SELECT r.registration_id, e.exam_id, e.exam_name,
           e.campus, e.room_number, e.exam_date, e.exam_time
    FROM registrations r
    JOIN exams e ON r.exam_id = e.exam_id
    WHERE r.student_id = ?
");
$stmt->execute([$userId]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Student name
$stmtUser = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$fullName = $user['first_name'] . ' ' . $user['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Exams</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../css/styles.css">

<style>

/* =====================
   Layout Containers
===================== */

.dashboard-container {
    max-width: 900px;
    margin: 30px auto;
    padding: 20px;
}

.welcome-card,
.exams-card,
.calendar-card {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

/* =====================
   Calendar Fixes
===================== */

.calendar-card {
    max-width: 100%;
    overflow-x: auto;     /* ✅ prevent column clipping */
}

.calendar table {
    width: 100%;
    table-layout: fixed; /* ✅ equal column widths */
}

.calendar th,
.calendar td {
    min-width: 90px;     /* ✅ keep columns visible */
}

/* =====================
   Calendar Styles
===================== */

.calendar-month {
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 15px;
}

.calendar th {
    background: #2196F3;
    color: white;
    padding: 15px;
}

.calendar td {
    border: 1px solid #eee;
    padding: 10px;
    height: 60px;
    text-align: center;
}

.calendar td a {
    color: #1976D2;
    font-weight: bold;
    text-decoration: none;
}

.calendar td:hover {
    background: #e3f2fd;
}

/* =====================
   Table Styles
===================== */

.exams-table {
    width: 100%;
    border-collapse: collapse;
}

.exams-table th {
    background: #2196F3;
    color: white;
    padding: 15px;
}

.exams-table td {
    padding: 15px;
    border-bottom: 1px solid #eee;
}

.clickable-row:hover {
    background: #f5f5f5;
}

.exam-badge {
    background: #e3f2fd;
    color: #1976D2;
    padding: 5px 10px;
    border-radius: 15px;
}

/* Buttons */
.cancel-btn {
    margin-top: 15px;
    padding: 12px 30px;
    background: #e74c3c;
    border: none;
    color: white;
    font-weight: bold;
    border-radius: 5px;
    cursor: pointer;
}

</style>
</head>

<body>

<div class="dashboard-container">

    <!-- Welcome -->
    <div class="welcome-card">
        <h1>Welcome, <?= htmlspecialchars($fullName) ?>!</h1>
        <a href="../index.html">Log Out</a>
    </div>

    <!-- Exams -->
    <div class="exams-card">
        <h2>Your Registered Exams</h2>

        <?php if ($registrations): ?>
        <form method="POST">

            <table class="exams-table">
                <thead>
                    <tr>
                        <th>Select</th>
                        <th>Exam</th>
                        <th>Campus</th>
                        <th>Room</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                    <tr class="clickable-row">
                        <td><input type="checkbox" name="registration_ids[]" value="<?= $reg['registration_id'] ?>"></td>
                        <td><strong><?= htmlspecialchars($reg['exam_name']) ?></strong></td>
                        <td><span class="exam-badge"><?= htmlspecialchars($reg['campus']) ?></span></td>
                        <td><?= htmlspecialchars($reg['room_number']) ?></td>
                        <td><?= htmlspecialchars($reg['exam_date']) ?></td>
                        <td><?= htmlspecialchars($reg['exam_time']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button name="cancel_registrations" class="cancel-btn">Cancel Selected</button>
        </form>

        <?php else: ?>
            <p>No registered exams yet.</p>
        <?php endif; ?>
    </div>

</div>

<!-- Calendar -->

<div class="calendar-card">
    <div class="calendar">
        <div class="calendar-month">December 2025</div>
        <table>
            <thead>
                <tr>
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody class="calendardates">
                <?php for ($r = 0; $r < 6; $r++): ?>
                <tr>
                    <?php for ($c = 0; $c < 7; $c++): ?>
                        <td></td>
                    <?php endfor; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Calendar JS

const tbody = document.querySelector('.calendardates');

const year = 2025;
const month = 11; // December

const startDay = new Date(year, month, 1).getDay();
const totalDays = new Date(year, month + 1, 0).getDate();

const cells = tbody.querySelectorAll('td');
cells.forEach(c => c.innerHTML = '');

for (let d = 1; d <= totalDays; d++) {
    const cellIndex = startDay + (d - 1);
    const td = cells[cellIndex];

    const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;

    const link = document.createElement('a');
    link.href = `available_exams.php?date=${dateStr}`;
    link.textContent = d;

    td.appendChild(link);
}
</script>

</body>
</html>
