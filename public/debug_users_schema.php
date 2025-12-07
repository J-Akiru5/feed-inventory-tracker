<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $pdo = get_pdo();
    $stmt = $pdo->query("SHOW COLUMNS FROM users");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'ok', 'columns' => $cols], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_PRETTY_PRINT);
}

// For safety, remove this file after use.
