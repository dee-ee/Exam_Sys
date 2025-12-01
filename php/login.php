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

    // Query to fetch the user based on email
    $stmt = $db->prepare("
        SELECT id, first_name, email, password, role
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the user exists and if the password matches
    if ($user && $password === $user['password']) {  // Direct comparison for plain text password

        // Set session variables for logged in user, stores user info that logged in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['role']    = $user['role'];

        // Redirect based on user role (student or staff)
        if ($user['role'] === 'student') {
            header("Location: ../ExamList.html");  // Redirect to student exam list
        } else {
            header("Location: ../StaffDashboard.html");  // Redirect to staff dashboard
        }

        exit();  // Stop further script execution after redirect

    } else {

        // If login fails, redirect back to login page with error
        header("Location: ../index.html?login=error");
        exit();  // Stop further script execution
    }
}
?>
