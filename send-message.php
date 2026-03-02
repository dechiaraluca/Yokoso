<?php
session_start();
require_once 'includes/config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$id_conversation = (int)($_POST['id_conversation'] ?? 0);
$contenu         = trim($_POST['contenu'] ?? '');
$id_expediteur   = $_SESSION['user_id'];

if (!$id_conversation || $contenu === '') {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

// Vérifier que l'utilisateur fait partie de la conversation
$stmt = $pdo->prepare('SELECT * FROM conversations WHERE id_conversation = ? AND (id_user1 = ? OR id_user2 = ?)');
$stmt->execute([$id_conversation, $id_expediteur, $id_expediteur]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conv) {
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

try {
    $pdo->prepare('INSERT INTO messages (id_conversation, id_expediteur, contenu) VALUES (?, ?, ?)')
        ->execute([$id_conversation, $id_expediteur, $contenu]);

    $pdo->prepare('UPDATE conversations SET date_dernier_message = NOW() WHERE id_conversation = ?')
        ->execute([$id_conversation]);

    echo json_encode([
        'success'  => true,
        'message'  => htmlspecialchars($contenu),
        'time'     => (new DateTime())->format('H:i'),
        'mine'     => true
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
