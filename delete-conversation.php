<?php
session_start();
require_once 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$id_user         = $_SESSION['user_id'];
$id_conversation = (int)($_POST['id_conversation'] ?? 0);

if (!$id_conversation) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

// Vérifier que l'utilisateur fait partie de la conversation
$stmt = $pdo->prepare('
    SELECT id_conversation FROM conversations
    WHERE id_conversation = ? AND (id_user1 = ? OR id_user2 = ?)
');
$stmt->execute([$id_conversation, $id_user, $id_user]);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit;
}

// Supprimer les messages puis la conversation
$pdo->prepare('DELETE FROM messages WHERE id_conversation = ?')->execute([$id_conversation]);
$pdo->prepare('DELETE FROM conversations WHERE id_conversation = ?')->execute([$id_conversation]);

echo json_encode(['success' => true]);
