<?php
// Enable error reporting (helpful while testing)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use an absolute path so relative folders never bite you
$dbPath = __DIR__ . '/../Data/data.sqlite';
echo "<p>DB path: $dbPath</p>";

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../Data/data.sqlite');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure table exists
    $db->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name TEXT NOT NULL,
            last_name  TEXT NOT NULL,
            email      TEXT UNIQUE NOT NULL,
            student_id TEXT NOT NULL,
            password   TEXT NOT NULL
        );
    ");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Make sure your HTML form uses these exact names
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sid   = trim($_POST['student_id'] ?? '');
        $pass  = $_POST['password'] ?? '';

        if ($first === '' || $last === '' || $email === '' || $sid === '' || $pass === '') {
            echo "<h2>All fields are required.</h2>";
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO students (first_name, last_name, email, student_id, password)
                VALUES (:first, :last, :email, :sid, :password)
            ");
            $stmt->bindValue(':first', $first);
            $stmt->bindValue(':last',  $last);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':sid',   $sid);
            $stmt->bindValue(':password', $hashed);

            try {
                $stmt->execute();
                // Redirect to the list page after success (optional)
                header('Location: view.php');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    echo "<h2>Error: That email is already registered.</h2>";
                } else {
                    echo "<h2>Insert error: " . htmlspecialchars($e->getMessage()) . "</h2>";
                }
            }
        }
    } else {
        echo "<p>Please submit the form.</p>";
    }
} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
}
