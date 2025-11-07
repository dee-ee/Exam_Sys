<?php
// Show PHP errors in dev (optional)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'mysql.railway.internal';
$port = '3306';
$user = 'root';
$pass = 'BNTBdONqgsoJiomzKyqmITFpWqEjgIZf';
$name = 'railway';

$host = getenv('MYSQLHOST') ?: $host;
$port = getenv('MYSQLPORT') ?: $port;
$user = getenv('MYSQLUSER') ?: $user;
$pass = getenv('MYSQLPASSWORD') ?: $pass;
$name = getenv('MYSQLDATABASE') ?: $name;

// Fallback: if no Railway vars are present, bail with a clear message
if (!$host || !$user || !$name) {
    die('<h2>Database env vars not found. Are you running on Railway? Set MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE.</h2>');
}

try {
    // Connect to MySQL (UTF-8, exceptions on errors)
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Ensure table exists (MySQL syntax, not SQLite)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name  VARCHAR(100) NOT NULL,
            email      VARCHAR(191) NOT NULL,
            student_id VARCHAR(100) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_students_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Grab and validate inputs
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sid   = trim($_POST['student_id'] ?? '');
        $rawPw = $_POST['password'] ?? '';

        if ($first === '' || $last === '' || $email === '' || $sid === '' || $rawPw === '') {
            echo "<h2>All fields are required.</h2>";
        } else {
            $hashed = password_hash($rawPw, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO students (first_name, last_name, email, student_id, `password`)
                VALUES (:first, :last, :email, :sid, :pw)
            ");
            $stmt->bindValue(':first', $first);
            $stmt->bindValue(':last',  $last);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':sid',   $sid);
            $stmt->bindValue(':pw',    $hashed);

            try {
                $stmt->execute();
                header('Location: view.php'); // adjust as needed
                exit;
            } catch (PDOException $e) {
                // 23000 = integrity constraint violation (e.g., duplicate email)
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
    echo "<h2>Database error: " . htmlspecialchars($e->getMessage()) . "</h2>";
}
