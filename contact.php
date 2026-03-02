<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_destinataire = (int)($_GET['user']    ?? 0);
$id_annonce      = (int)($_GET['annonce'] ?? 0) ?: null;
$id_expediteur   = $_SESSION['user_id'];

if (!$id_destinataire || $id_destinataire === $id_expediteur) {
    header('Location: home.php');
    exit;
}

// Chercher une conversation existante entre ces deux utilisateurs (peu importe l'annonce)
$stmt = $pdo->prepare('
    SELECT id_conversation FROM conversations
    WHERE (id_user1 = ? AND id_user2 = ?) OR (id_user1 = ? AND id_user2 = ?)
    ORDER BY id_conversation ASC
    LIMIT 1
');
$stmt->execute([$id_expediteur, $id_destinataire, $id_destinataire, $id_expediteur]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if ($conv) {
    header('Location: conversation.php?id=' . $conv['id_conversation']);
} else {
    $pdo->prepare('INSERT INTO conversations (id_annonce, id_user1, id_user2) VALUES (?, ?, ?)')
        ->execute([$id_annonce, $id_expediteur, $id_destinataire]);
    header('Location: conversation.php?id=' . $pdo->lastInsertId());
}
exit;
