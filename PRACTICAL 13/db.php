<?php
// =============================================
// StudentHub ITUE203 — Practical 13
// db.php — shared MySQLi connection
// XAMPP defaults: host localhost, user root, empty password
// Security: no credentials echoed; errors are friendly,
// technical details go to the PHP error log only.
// =============================================

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'studenthub_p13';

try {
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4'); // SECURITY: correct charset blocks some escaping bypasses
} catch (mysqli_sql_exception $e) {
    // SECURITY: never expose host/user/stack trace to the browser.
    error_log('P13 DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Check XAMPP MySQL and import database.sql.',
        'data'    => null
    ]);
    exit;
}
