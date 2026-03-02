<?php
session_start();
require_once 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$id_annonce = (int)($_POST['id_annonce'] ?? 0);
$id_user    = $_SESSION['user_id'];

if (!$id_annonce) {
    echo json_encode(['success' => false, 'message' => 'Annonce invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM favoris WHERE id_user = ? AND id_annonce = ?');
    $stmt->execute([$id_user, $id_annonce]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $pdo->prepare('DELETE FROM favoris WHERE id_user = ? AND id_annonce = ?')->execute([$id_user, $id_annonce]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        $pdo->prepare('INSERT INTO favoris (id_user, id_annonce) VALUES (?, ?)')->execute([$id_user, $id_annonce]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
