<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Protect page - only staff allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
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

// Get staff user's ID from session
$userId = $_SESSION['user_id'];

// Handle cancel/remove exams
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_exams'])) {
    $selectedExams = $_POST['exam_ids'] ?? [];
    
    if (!empty($selectedExams)) {
        $placeholders = implode(',', array_fill(0, count($selectedExams), '?'));
        $stmt = $db->prepare("
            UPDATE exams 
            SET teacher_id = NULL 
            WHERE exam_id IN ($placeholders) AND teacher_id = ?
        ");
        $params = array_merge($selectedExams, [$userId]);
        $stmt->execute($params);
    }
    
    // Redirect to refresh the page
    header("Location: StaffDashboard.php");
    exit();
}

// Fetch exams where teacher_id matches logged-in staff
$stmt = $db->prepare("
    SELECT exam_id, exam_name, campus, room_number, exam_date, exam_time, capacity
    FROM exams
    WHERE teacher_id = ?
");
$stmt->execute([$userId]);
$exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get staff name
$stmtUser = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$fullName = $user['first_name'] . ' ' . $user['last_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Staff Dashboard</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
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

        /* Checkbox styling */
        .exam-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        /* Clickable row */
        .clickable-row {
            cursor: pointer;
        }

        .clickable-row.selected {
            background: #ffebee;
        }

        /* Cancel button */
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
    </style>
</head>
<body>
    <header>
        <img src="../images/csnlogo.jpg" alt="Logo">
    </header>

    <div class="dashboard-container">
        <div class="welcome-card">
            <h1>Welcome, <?= htmlspecialchars($fullName) ?>!</h1>
            <a href="../index.html" class="logout-btn">Log Out</a>
        </div>

        <div class="exams-card">
            <h2>Your Exams</h2>

            <?php if (count($exams) > 0): ?>
                <form method="POST" action="StaffDashboard.php">
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
                                <th>Capacity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exams as $exam): ?>
                                <tr class="clickable-row">
                                    <td>
                                        <input type="checkbox" 
                                               name="exam_ids[]" 
                                               value="<?= $exam['exam_id'] ?>" 
                                               class="exam-checkbox row-checkbox">
                                    </td>
                                    <td><strong><?= htmlspecialchars($exam['exam_name']) ?></strong></td>
                                    <td><span class="exam-badge"><?= htmlspecialchars($exam['campus']) ?></span></td>
                                    <td><?= htmlspecialchars($exam['room_number']) ?></td>
                                    <td><?= htmlspecialchars($exam['exam_date']) ?></td>
                                    <td><?= htmlspecialchars($exam['exam_time']) ?></td>
                                    <td><?= htmlspecialchars($exam['capacity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="button-container">
                        <button type="submit" name="cancel_exams" class="cancel-btn" id="cancelBtn" disabled>
                            Cancel Selected Exams
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <p class="no-exams">You have no exams assigned.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Make entire row clickable
        document.querySelectorAll('.clickable-row').forEach(row => {
            row.addEventListener('click', function(e) {
                // Don't toggle if clicking directly on checkbox
                if (e.target.type === 'checkbox') return;
                
                const checkbox = this.querySelector('.row-checkbox');
                checkbox.checked = !checkbox.checked;
                this.classList.toggle('selected', checkbox.checked);
                updateCancelButton();
            });
        });

        // Update row style when checkbox is clicked directly
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                this.closest('tr').classList.toggle('selected', this.checked);
                updateCancelButton();
            });
        });

        // Select All functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                checkbox.closest('tr').classList.toggle('selected', this.checked);
            });
            updateCancelButton();
        });

        // Enable/disable cancel button based on selection
        function updateCancelButton() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            document.getElementById('cancelBtn').disabled = checkedBoxes.length === 0;
        }
    </script>

</body>
</html>