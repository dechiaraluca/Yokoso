<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$id_annonce = (int)($_POST['id'] ?? 0);
$user_id    = $_SESSION['user_id'];

if ($id_annonce === 0) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    // Vérifier que l'annonce appartient à l'utilisateur et récupérer l'état actuel
    $stmt = $pdo->prepare('SELECT disponible FROM annonces WHERE id_annonce = ? AND id_proprietaire = ? LIMIT 1');
    $stmt->execute([$id_annonce, $user_id]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$annonce) {
        echo json_encode(['success' => false]);
        exit;
    }

    $nouveau = $annonce['disponible'] ? 0 : 1;
    $pdo->prepare('UPDATE annonces SET disponible = ? WHERE id_annonce = ?')
        ->execute([$nouveau, $id_annonce]);

    echo json_encode(['success' => true, 'disponible' => $nouveau]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
