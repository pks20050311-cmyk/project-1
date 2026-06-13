<?php
// ============================================================
//  db_connect.php  —  Database connection for InfinityFree
// ============================================================
//
//  HOW TO FILL THESE IN (read the deployment guide):
//
//  1. Log in to InfinityFree → Control Panel → MySQL Databases
//  2. Create a new database — note the auto-generated name
//     (looks like:  epiz_12345678_todoapp)
//  3. Create a database user and assign it to the database
//  4. Copy the host shown on the MySQL Databases page
//     (looks like:  sql123.infinityfree.com)
//  5. Paste everything below ↓
//
// ============================================================

define('DB_HOST', 'sql310.infinityfree.com');   // ← replace with your actual MySQL host
define('DB_NAME', 'if0_41890357_to_do_list');       // ← replace with your actual database name
define('DB_USER', 'if0_41890357');               // ← replace with your actual database username
define('DB_PASS', '14695717qwe');        // ← replace with your actual database password
define('DB_CHARSET', 'utf8mb4');

// ── Connection (singleton) ──────────────────────────────────
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose the raw error message to the browser in production
            error_log('DB connection error: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['error' => 'Database connection failed. Check db_connect.php credentials.']));
        }
    }

    return $pdo;
}
