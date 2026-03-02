<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

$id_annonce  = (int)($_POST['id_annonce'] ?? 0);
$note        = (int)($_POST['note'] ?? 0);
$commentaire = trim($_POST['commentaire'] ?? '');
$id_auteur   = $_SESSION['user_id'];

// Validations basiques
if ($id_annonce === 0 || $note < 1 || $note > 5) {
    header('Location: annonce.php?id=' . $id_annonce . '&avis_error=1');
    exit;
}

// Vérifier que l'utilisateur a bien séjourné dans ce logement (réservation passée non annulée)
$stmt = $pdo->prepare('
    SELECT id_reservation FROM reservations
    WHERE id_annonce = ? AND id_voyageur = ? AND date_fin < NOW() AND statut != "annulee"
    LIMIT 1
');
$stmt->execute([$id_annonce, $id_auteur]);
if (!$stmt->fetch()) {
    header('Location: annonce.php?id=' . $id_annonce . '&avis_error=2');
    exit;
}

// Vérifier que l'utilisateur n'a pas déjà laissé un avis
$stmt = $pdo->prepare('SELECT id_avis FROM avis WHERE id_annonce = ? AND id_auteur = ? LIMIT 1');
$stmt->execute([$id_annonce, $id_auteur]);
if ($stmt->fetch()) {
    header('Location: annonce.php?id=' . $id_annonce . '&avis_error=3');
    exit;
}

// Insérer l'avis
$stmt = $pdo->prepare('INSERT INTO avis (id_annonce, id_auteur, note, commentaire) VALUES (?, ?, ?, ?)');
$stmt->execute([$id_annonce, $id_auteur, $note, $commentaire ?: null]);

header('Location: annonce.php?id=' . $id_annonce . '&avis_ok=1#avis');
exit;
