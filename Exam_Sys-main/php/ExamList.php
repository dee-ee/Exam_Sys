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

// Get student user's ID from session
$userId = $_SESSION['user_id'];

// Handle cancel/remove registrations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registrations'])) {
    $selectedRegistrations = $_POST['registration_ids'] ?? [];
    
    if (!empty($selectedRegistrations)) {
        $placeholders = implode(',', array_fill(0, count($selectedRegistrations), '?'));
        $stmt = $db->prepare("
            DELETE FROM registrations 
            WHERE registration_id IN ($placeholders) AND student_id = ?
        ");
        $params = array_merge($selectedRegistrations, [$userId]);
        $stmt->execute($params);
    }
    
    header("Location: ExamList.php");
    exit();
}

// Fetch registered exams for this student
$stmt = $db->prepare("
    SELECT r.registration_id, e.exam_id, e.exam_name, e.campus, e.room_number, e.exam_date, e.exam_time
    FROM registrations r
    JOIN exams e ON r.exam_id = e.exam_id
    WHERE r.student_id = ?
");
$stmt->execute([$userId]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get student name
$stmtUser = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$fullName = $user['first_name'] . ' ' . $user['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Exams</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <script src="../js/modernizr.custom.40753.js"></script>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .dashboard-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
        }

        .welcome-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .welcome-card h1 {
            margin: 0 0 15px 0;
            color: #333;
        }

        .logout-btn {
            display: inline-block;
            padding: 10px 25px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .exams-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .exams-card h2 {
            margin: 0 0 20px 0;
            color: #333;
            border-bottom: 2px solid #2196F3;
            padding-bottom: 10px;
        }

        .exams-table {
            width: 100%;
            border-collapse: collapse;
        }

        .exams-table th {
            background: #2196F3;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
        }

        .exams-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #eee;
            color: #333;
        }

        .exams-table tr:hover {
            background: #f5f5f5;
        }

        .exams-table tr:last-child td {
            border-bottom: none;
        }

        .no-exams {
            text-align: center;
            color: #666;
            padding: 40px;
            font-size: 18px;
        }

        .exam-badge {
            display: inline-block;
            padding: 5px 10px;
            background: #e3f2fd;
            color: #1976D2;
            border-radius: 15px;
            font-size: 14px;
        }

        .exam-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .clickable-row {
            cursor: pointer;
        }

        .clickable-row.selected {
            background: #ffebee;
        }

        .cancel-btn {
            display: inline-block;
            padding: 12px 30px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }

        .cancel-btn:hover {
            background: #c0392b;
        }

        .cancel-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .button-container {
            text-align: right;
            margin-top: 20px;
        }

        .select-all-container {
            margin-bottom: 15px;
        }

        .select-all-container label {
            cursor: pointer;
            font-weight: 500;
            color: #333;
        }

        /* Calendar Styles */
        .calendar-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto 30px auto;
            overflow: hidden;
        }

        .calendar-card h2 {
            margin: 0;
            padding: 20px 30px;
            color: #333;
            border-bottom: 2px solid #2196F3;
        }

        .calendar {
            padding: 20px;
        }

        .calendar table {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar th {
            background: #2196F3;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
        }

        .calendar td {
            border: 1px solid #eee;
            padding: 10px;
            text-align: center;
            height: 60px;
            vertical-align: top;
        }

        .calendar td:hover {
            background: #e3f2fd;
        }

        .calendar td span {
            display: block;
            width: 100%;
            height: 100%;
        }

        .calendar td a {
            color: #1976D2;
            text-decoration: none;
            font-weight: bold;
        }

        .calendar td a:hover {
            text-decoration: underline;
        }

        .calendar-month {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <header>
        <img src="../images/csnlogo.jpg" alt="Logo">
    </header>

    <!-- Welcome Section -->
    <div class="dashboard-container">
        <div class="welcome-card">
            <h1>Welcome, <?= htmlspecialchars($fullName) ?>!</h1>
            <a href="../index.html" class="logout-btn">Log Out</a>
        </div>

        <!-- Registered Exams Section -->
        <div class="exams-card">
            <h2>Your Registered Exams</h2>

            <?php if (count($registrations) > 0): ?>
                <form method="POST" action="ExamList.php">
                    <div class="select-all-container">
                        <label>
                            <input type="checkbox" id="selectAll" class="exam-checkbox"> 
                            Select All
                        </label>
                    </div>

                    <table class="exams-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Exam Name</th>
                                <th>Campus</th>
                                <th>Room</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr class="clickable-row">
                                    <td>
                                        <input type="checkbox" 
                                               name="registration_ids[]" 
                                               value="<?= $reg['registration_id'] ?>" 
                                               class="exam-checkbox row-checkbox">
                                    </td>
                                    <td><strong><?= htmlspecialchars($reg['exam_name']) ?></strong></td>
                                    <td><span class="exam-badge"><?= htmlspecialchars($reg['campus']) ?></span></td>
                                    <td><?= htmlspecialchars($reg['room_number']) ?></td>
                                    <td><?= htmlspecialchars($reg['exam_date']) ?></td>
                                    <td><?= htmlspecialchars($reg['exam_time']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="button-container">
                        <button type="submit" name="cancel_registrations" class="cancel-btn" id="cancelBtn" disabled>
                            Cancel Selected Registrations
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p class="no-exams">You have no registered exams. Click a date below to browse available exams.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Calendar Section -->
    <div class="calendar-card">
        <h2>Browse Exams by Date</h2>
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
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                    <tr><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // =====================
        // Clickable Rows Logic
        // =====================
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.type === 'checkbox') return;
                
                const checkbox = this.querySelector('.row-checkbox');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
                updateCancelButton();
            });
        });

        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                this.closest('tr').classList.toggle('selected', this.checked);
                updateCancelButton();
            });
        });

        const selectAllBox = document.getElementById('selectAll');
        if (selectAllBox) {
            selectAllBox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.row-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                    checkbox.closest('tr').classList.toggle('selected', this.checked);
                });
                updateCancelButton();
            });
        }

        function updateCancelButton() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const cancelBtn = document.getElementById('cancelBtn');
            if (cancelBtn) {
                cancelBtn.disabled = checkedBoxes.length === 0;
            }
        }

        // =====================
        // Calendar Logic - December 2025
        // =====================
        const tbody = document.querySelector('.calendardates');
        
        // Fixed to December 2025
        const year = 2025;
        const month = 11; // December (0-indexed)

        const F_Day = new Date(year, month, 1).getDay();
        const Datefull = new Date(year, month + 1, 0).getDate();

        const cells = tbody.querySelectorAll('td');
        cells.forEach(cell => cell.innerHTML = '');

        for (let day = 1; day <= Datefull; day++) {
            const index = F_Day + (day - 1);
            const td = cells[index];
            if (td) {
                const span = document.createElement('span');
                const link = document.createElement('a');
                
                // Format date as YYYY-MM-DD for the URL
                const dateStr = `${year}-${String(month + 1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
                
                // Pass date as URL parameter
                link.href = `available_exams.php?date=${dateStr}`;
                link.textContent = day;
                span.appendChild(link);
                span.dataset.date = dateStr;
                td.appendChild(span);
            }
        }
    </script>

</body>
</html>