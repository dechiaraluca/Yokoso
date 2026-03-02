<?php
session_start();
require_once 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $pdo->prepare('UPDATE notifications SET lu = 1 WHERE id_user = ? AND lu = 0')
        ->execute([$_SESSION['user_id']]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
