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

        // ROLE ASSIGNMENT BASED ON EMAIL DOMAIN
        if (str_ends_with($email, '@student.csn.edu')) {
            // Student email domain
            $role = 'student';

            // Ensure NSHE ID is provided for students
            if ($nshe === '') {
                exit("<h2>NSHE ID is required for student accounts.</h2>");
            }
            
            // For students, password will be their NSHE ID (set server-side)
            $pass = $nshe;

        } elseif (str_ends_with($email, '@csn.edu')) {
            // Staff email domain
            $role = 'staff';
            $nshe = null;   // Staff don't need NSHE ID
            
            // BASIC VALIDATION: Staff must provide password
            if (!$pass) {
                exit("<h2>Password is required for staff accounts.</h2>");
            }
        } else {
            // Invalid email domain
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Invalid Email Domain</title>
                <link rel="stylesheet" href="../css/styles.css">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background: #3093f7;
                        margin: 0;
                        padding: 0;
                        color: #333;
                    }
                    header {
                        width: 100%;
                        background-color: #ffffff; 
                        padding: 10px 0;
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
                    p, li {
                        color: #666;
                        margin-bottom: 10px;
                        font-size: 16px;
                    }
                    ul {
                        list-style: none;
                        padding: 0;
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
                <img src="../images/csnlogo.jpg" alt="Logo">
            </header>

            <div class="container">
                <div class="error-icon">⚠</div>
                <h2>Invalid Email Domain</h2>
                <p>Signup is restricted to official CSN accounts only:</p>

                <ul>
                    <li><strong>@student.csn.edu</strong> — Students</li>
                    <li><strong>@csn.edu</strong> — Staff</li>
                </ul>

                <p>Please use your official CSN email address to continue.</p>

                <a href="../AccountRegister.html" class="btn btn-back">← Back to Registration</a>
            </div>

            </body>
            </html>
            <?php
            exit();
        }

        // BASIC VALIDATION
        if (!$first || !$last || !$email) {
            exit("<h2>First name, last name, and email are required.</h2>");
        }

        // INSERT NEW USER INTO DATABASE
        $stmt = $db->prepare("
            INSERT INTO users
            (first_name, last_name, email, password, role, nshe_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $first,
            $last,
            $email,
            $pass,  // For students: NSHE ID; For staff: provided password
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
