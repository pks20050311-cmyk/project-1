<?php
// ============================================================
//  tasks.php  –  JSON API for task CRUD (called via AJAX)
// ============================================================
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorised']);
    exit;
}

require_once 'db_connect.php';
$db      = get_db();
$user_id = (int) $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? '';

// ── Helper ──────────────────────────────────────────────────
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function valid_status(string $s): bool {
    return in_array($s, ['In Progress', 'Done', 'Cancelled'], true);
}

// ── READ (list all tasks for this user) ──────────────────────
if ($method === 'GET' && $action === 'list') {
    $stmt = $db->prepare(
        'SELECT id, title, description, due_date, status, created_at
         FROM tasks
         WHERE user_id = ?
         ORDER BY
           CASE status WHEN "In Progress" THEN 0 WHEN "Done" THEN 1 ELSE 2 END,
           due_date IS NULL,
           due_date ASC,
           created_at DESC'
    );
    $stmt->execute([$user_id]);
    json_out(['tasks' => $stmt->fetchAll()]);
}

// ── READ (notifications – due within 3 days or overdue) ──────
if ($method === 'GET' && $action === 'notifications') {
    $stmt = $db->prepare(
        "SELECT id, title, due_date,
                DATEDIFF(due_date, CURDATE()) AS days_left
         FROM tasks
         WHERE user_id = ?
           AND status = 'In Progress'
           AND due_date IS NOT NULL
           AND due_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
         ORDER BY due_date ASC"
    );
    $stmt->execute([$user_id]);
    json_out(['notifications' => $stmt->fetchAll()]);
}

// ── CREATE ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'create') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $title  = trim($body['title']       ?? '');
    $desc   = trim($body['description'] ?? '');
    $due    = trim($body['due_date']     ?? '');
    $status = trim($body['status']       ?? 'In Progress');

    if ($title === '') json_out(['error' => 'Title is required.'], 422);
    if (!valid_status($status)) json_out(['error' => 'Invalid status.'], 422);

    // Validate date
    $due_val = null;
    if ($due !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $due);
        if (!$d) json_out(['error' => 'Invalid date format.'], 422);
        $due_val = $due;
    }

    $ins = $db->prepare(
        'INSERT INTO tasks (user_id, title, description, due_date, status)
         VALUES (?, ?, ?, ?, ?)'
    );
    $ins->execute([$user_id, $title, $desc ?: null, $due_val, $status]);
    $new_id = (int) $db->lastInsertId();

    $sel = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $sel->execute([$new_id]);
    json_out(['task' => $sel->fetch()], 201);
}

// ── UPDATE ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'update') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $id     = (int)   ($body['id']          ?? 0);
    $title  = trim($body['title']            ?? '');
    $desc   = trim($body['description']      ?? '');
    $due    = trim($body['due_date']          ?? '');
    $status = trim($body['status']            ?? '');

    if ($id <= 0)       json_out(['error' => 'Invalid task ID.'], 422);
    if ($title === '')  json_out(['error' => 'Title is required.'], 422);
    if (!valid_status($status)) json_out(['error' => 'Invalid status.'], 422);

    // Ownership check
    $own = $db->prepare('SELECT id FROM tasks WHERE id = ? AND user_id = ?');
    $own->execute([$id, $user_id]);
    if (!$own->fetch()) json_out(['error' => 'Task not found.'], 404);

    $due_val = null;
    if ($due !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $due);
        if (!$d) json_out(['error' => 'Invalid date format.'], 422);
        $due_val = $due;
    }

    $upd = $db->prepare(
        'UPDATE tasks SET title=?, description=?, due_date=?, status=? WHERE id=? AND user_id=?'
    );
    $upd->execute([$title, $desc ?: null, $due_val, $status, $id, $user_id]);

    $sel = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $sel->execute([$id]);
    json_out(['task' => $sel->fetch()]);
}

// ── DELETE ────────────────────────────────────────────────────
if ($method === 'POST' && $action === 'delete') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int) ($body['id'] ?? 0);

    if ($id <= 0) json_out(['error' => 'Invalid task ID.'], 422);

    $del = $db->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
    $del->execute([$id, $user_id]);

    if ($del->rowCount() === 0) json_out(['error' => 'Task not found.'], 404);
    json_out(['deleted' => true]);
}

// ── Fallthrough ───────────────────────────────────────────────
json_out(['error' => 'Unknown action.'], 400);
