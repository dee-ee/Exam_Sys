<?php
// -----------------------------
// CSN EXAM SYSTEM - REGISTER
// -----------------------------
// Purpose: Handle the registration of both students and staff (proctors).
// Role is determined by email domain. Students need NSHE ID. Staff do not.

// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB CONNECTION
$dbPath = __DIR__ . '/../Data/data.sqlite';

try {
    // Create a new PDO instance to connect to the SQLite database
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec("PRAGMA foreign_keys = ON;");

    // CREATE USERS TABLE IF IT DOESN'T EXIST
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,  -- Store password as plain text (for testing purposes)
            role TEXT NOT NULL CHECK(role IN ('student', 'staff')),  -- Staff or Student
            nshe_id TEXT  -- NSHE ID is only required for students
        )
    ");

    // HANDLE FORM SUBMIT
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Sanitize and retrieve POST data
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $nshe  = trim($_POST['nshe_id'] ?? '');  // May be empty for staff

        // BASIC VALIDATION
        if (!$first || !$last || !$email || !$pass) {
            exit("<h2>All fields except NSHE ID are required.</h2>");
        }

        // ROLE ASSIGNMENT BASED ON EMAIL DOMAIN
        if (str_ends_with($email, '@student.csn.edu')) {
            // Student email domain
            $role = 'student';

            // Ensure NSHE ID is provided for students
            if ($nshe === '') {
                exit("<h2>NSHE ID is required for student accounts.</h2>");
            }

        } elseif (str_ends_with($email, '@csn.edu')) {
            // Staff email domain
            $role = 'staff';
            $nshe = null;   // Staff don't need NSHE ID

        } else {
            // Invalid email domain
            exit("
                <h2>Signup restricted to official CSN accounts:</h2>
                <ul>
                    <li>@student.csn.edu — Students</li>
                    <li>@csn.edu — Staff</li>
                </ul>
            ");
        }

        // INSERT NEW USER INTO DATABASE (Store password as plain text)
        $stmt = $db->prepare("
            INSERT INTO users
            (first_name, last_name, email, password, role, nshe_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $first,
            $last,
            $email,
            $pass,  // Storing password as plain text
            $role,
            $nshe
        ]);

        // SUCCESSFUL REGISTRATION -> REDIRECT TO LOGIN PAGE
        header("Location: ../index.html");
        exit();
    }

    // If the form is not submitted, show a message.
    else {
        echo "<p>Please submit the registration form.</p>";
    }

} catch (PDOException $e) {

    // Error handling for database issues
    if ($e->getCode() === "23000") {
        echo "<h2>This email is already registered.</h2>";
    } else {
        echo "<h2>Database error:</h2>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }

}
?>
