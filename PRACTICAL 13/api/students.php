<?php
// =============================================
// StudentHub ITUE203 — Practical 13
// api/students.php — single JSON API endpoint
// ?action=list|add|update|delete
//   GET  list   (optional ?q= search)
//   POST add | update | delete  (JSON body)
// ALWAYS returns valid JSON with {success, message, data}
// and correct HTTP status codes.
// SECURITY: prepared statements only; server-side validation
// runs BEFORE any DB write; output is JSON-encoded (no raw HTML).
// =============================================

header('Content-Type: application/json; charset=utf-8');

// ---------- centralized error handling ----------
// Every error path goes through json_error() so the frontend
// ALWAYS receives parseable JSON (never a PHP warning/HTML page).
function json_response($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}
function json_error($message, $code = 400, $data = null) {
    json_response(false, $message, $data, $code);
}

// Catch anything unexpected (DB down, bad SQL) as JSON 500.
set_exception_handler(function ($e) {
    error_log('P13 API exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.', 'data' => null]);
    exit;
});
set_error_handler(function ($no, $str) {
    // Convert PHP warnings/notices into JSON 500 instead of HTML output.
    error_log("P13 API php error [$no]: $str");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.', 'data' => null]);
    exit;
});

require_once __DIR__ . '/../db.php'; // provides $conn (MySQLi)

// ---------- resolve action + input ----------
$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : '';
$rawBody = file_get_contents('php://input');
$body = [];
if ($rawBody !== '' && $rawBody !== false) {
    $decoded = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_error('Invalid JSON body.', 400);
    }
    $body = is_array($decoded) ? $decoded : [];
    // Allow {"action":"add", ...} as an alternative to ?action=add
    if ($action === '' && isset($body['action'])) {
        $action = strtolower(trim((string)$body['action']));
    }
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$allowed = ['list', 'add', 'update', 'delete'];
if (!in_array($action, $allowed, true)) {
    json_error('Unknown action. Use ?action=list|add|update|delete.', 404);
}

// ---------- validation (same rules as Practical 11) ----------
// Runs BEFORE any INSERT/UPDATE. Returns array of error strings.
function validate_student($d, $isUpdate = false) {
    $errors = [];
    $g = function ($k) use ($d) { return isset($d[$k]) ? trim((string)$d[$k]) : ''; };

    if ($isUpdate) {
        if ($g('id') === '' || !ctype_digit($g('id')) || (int)$g('id') < 1) {
            $errors[] = 'Valid id is required for update.';
        }
    }
    $name = $g('full_name');
    if ($name === '' || mb_strlen($name) < 3)            $errors[] = 'Full name must be at least 3 characters.';
    elseif (mb_strlen($name) > 100)                      $errors[] = 'Full name is too long (max 100).';

    if (!filter_var($g('email'), FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (!preg_match('/^[0-9]{10}$/', $g('phone')))        $errors[] = 'Phone must be exactly 10 digits.';
    if (!preg_match('/^STU[0-9]{3,10}$/i', $g('student_id'))) $errors[] = 'Student ID must look like STU001.';
    if ($g('course') === '')                             $errors[] = 'Course is required.';

    $year = $g('year');
    if (!ctype_digit($year) || (int)$year < 1 || (int)$year > 4) $errors[] = 'Year must be 1, 2, 3 or 4.';

    if (!in_array($g('gender'), ['Male', 'Female', 'Other'], true))     $errors[] = 'Gender must be Male, Female or Other.';
    if (!in_array($g('status'), ['active', 'inactive'], true))           $errors[] = 'Status must be active or inactive.';

    if ($isUpdate && $g('id') === '') $errors[] = 'Valid id is required for update.';
    return $errors;
}

try {
    // ================= LIST (GET) =================
    if ($action === 'list') {
        if ($method !== 'GET') json_error('Method not allowed. Use GET for list.', 405);
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        if ($q !== '') {
            // SECURITY: prepared statement — search term never concatenated.
            $like = '%' . $q . '%';
            $stmt = $conn->prepare(
                'SELECT id, full_name, email, phone, student_id, course, year, gender, status, created_at
                 FROM students
                 WHERE full_name LIKE ? OR email LIKE ? OR student_id LIKE ? OR course LIKE ?
                 ORDER BY id DESC LIMIT 100'
            );
            $stmt->bind_param('ssss', $like, $like, $like, $like);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $res = $conn->query(
                'SELECT id, full_name, email, phone, student_id, course, year, gender, status, created_at
                 FROM students ORDER BY id DESC LIMIT 100'
            );
            $rows = $res->fetch_all(MYSQLI_ASSOC);
        }
        json_response(true, 'Students fetched.', $rows, 200);
    }

    // ================= ADD (POST JSON) =================
    if ($action === 'add') {
        if ($method !== 'POST') json_error('Method not allowed. Use POST for add.', 405);
        $errors = validate_student($body, false);
        if ($errors) json_error(implode(' ', $errors), 400);
        // SECURITY: prepared statement prevents SQL injection.
        $stmt = $conn->prepare(
            'INSERT INTO students (full_name, email, phone, student_id, course, year, gender, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $year = (int)trim((string)$body['year']);
        $stmt->bind_param(
            'sssssiss',
            $body['full_name'], $body['email'], $body['phone'], $body['student_id'],
            $body['course'], $year, $body['gender'], $body['status']
        );
        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) json_error('Email or Student ID already exists.', 400);
            throw $e;
        }
        $newId = $stmt->insert_id;
        $stmt->close();
        $row = $conn->query('SELECT * FROM students WHERE id = ' . (int)$newId)->fetch_assoc();
        json_response(true, 'Student added.', $row, 201); // 201 Created
    }

    // ================= UPDATE (POST JSON) =================
    if ($action === 'update') {
        if ($method !== 'POST') json_error('Method not allowed. Use POST for update.', 405);
        $errors = validate_student($body, true);
        if ($errors) json_error(implode(' ', $errors), 400);
        $id = (int)$body['id'];
        // 404 if the row does not exist (checked BEFORE update).
        $chk = $conn->prepare('SELECT id FROM students WHERE id = ?');
        $chk->bind_param('i', $id);
        $chk->execute();
        $exists = $chk->get_result()->fetch_assoc();
        $chk->close();
        if (!$exists) json_error('Student not found.', 404);

        $stmt = $conn->prepare(
            'UPDATE students SET full_name=?, email=?, phone=?, student_id=?, course=?, year=?, gender=?, status=?
             WHERE id=?'
        );
        $year = (int)trim((string)$body['year']);
        $stmt->bind_param(
            'sssssissi',
            $body['full_name'], $body['email'], $body['phone'], $body['student_id'],
            $body['course'], $year, $body['gender'], $body['status'], $id
        );
        try {
            $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === 1062) json_error('Email or Student ID already exists.', 400);
            throw $e;
        }
        $stmt->close();
        $row = $conn->query('SELECT * FROM students WHERE id = ' . $id)->fetch_assoc();
        json_response(true, 'Student updated.', $row, 200);
    }

    // ================= DELETE (POST JSON) =================
    if ($action === 'delete') {
        if ($method !== 'POST') json_error('Method not allowed. Use POST for delete.', 405);
        $id = isset($body['id']) ? trim((string)$body['id']) : '';
        if ($id === '' || !ctype_digit($id)) json_error('Valid id is required for delete.', 400);
        $idInt = (int)$id;
        $stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
        $stmt->bind_param('i', $idInt);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        if ($affected === 0) json_error('Student not found.', 404);
        json_response(true, 'Student deleted.', ['id' => $idInt], 200);
    }

    json_error('Unknown action.', 404);
} catch (mysqli_sql_exception $e) {
    // Centralized DB error path: log technical detail, send friendly JSON.
    error_log('P13 API DB error: ' . $e->getMessage());
    json_error('Database error. Please try again later.', 500);
}
