<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------
// DB CONNECTION ONLY
// -----------------------------

$dbPath = __DIR__ . '/../Data/data.sqlite';

try {
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// -----------------------------
// LOGIN HANDLER
// -----------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Query to fetch the user based on email (include nshe_id for students)
    $stmt = $db->prepare("
        SELECT id, first_name, email, password, role, nshe_id
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user exists
    if ($user) {
        // For students: password must match their NSHE ID
        // For staff: password matches the stored password field
        $passwordMatch = false;
        
        if ($user['role'] === 'student') {
            // Students use their NSHE ID as password
            $passwordMatch = ($password === $user['nshe_id']);
        } else {
            // Staff use their stored password
            $passwordMatch = ($password === $user['password']);
        }

        if ($passwordMatch) {
            // Set session variables for logged in user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // Redirect based on user role (student or staff)
            if ($user['role'] === 'student') {
                header("Location: ExamList.php");  // Redirect to student exam list
            } else {
                header("Location: StaffDashboard.php");  // Redirect to staff dashboard
            }

            exit();  // Stop further script execution after redirect
        }
    }

    // If login fails, redirect back to login page with error
    header("Location: ../index.html?login=error");
    exit();  // Stop further script execution
}
?>
