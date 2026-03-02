<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit;
}

$errors = [];
$id_annonce    = (int)($_POST['id_annonce'] ?? 0);
$date_debut    = $_POST['date_debut'] ?? '';
$date_fin      = $_POST['date_fin'] ?? '';
$nb_voyageurs  = (int)($_POST['nb_voyageurs'] ?? 1);
$id_voyageur   = $_SESSION['user_id'];

if (!$id_annonce) {
    $errors[] = "Annonce introuvable.";
}

if (!$date_debut || !$date_fin) {
    $errors[] = "Les dates sont obligatoires.";
}

if ($date_debut && $date_fin) {
    $d1 = new DateTime($date_debut);
    $d2 = new DateTime($date_fin);
    if ($d1 >= $d2) {
        $errors[] = "La date de départ doit être après la date d'arrivée.";
    }
    if ($d1 < new DateTime('today')) {
        $errors[] = "La date d'arrivée ne peut pas être dans le passé.";
    }
}

if (!$errors) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM annonces WHERE id_annonce = ? AND disponible = 1 LIMIT 1');
        $stmt->execute([$id_annonce]);
        $annonce = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$annonce) {
            $errors[] = "Cette annonce n'existe pas ou n'est plus disponible.";
        } else {
            // Empêcher le proprio de réserver son propre logement
            if ($annonce['id_proprietaire'] == $id_voyageur) {
                $errors[] = "Vous ne pouvez pas réserver votre propre logement.";
            }

            // Vérifier la capacité
            if ($nb_voyageurs > $annonce['capacite_max']) {
                $errors[] = "Le nombre de voyageurs dépasse la capacité maximale (" . $annonce['capacite_max'] . " personnes).";
            }

            // Vérifier les conflits de dates
            $stmt = $pdo->prepare('
                SELECT COUNT(*) FROM reservations
                WHERE id_annonce = ?
                  AND statut = "confirmee"
                  AND date_debut < ?
                  AND date_fin   > ?
            ');
            $stmt->execute([$id_annonce, $date_fin, $date_debut]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Ces dates ne sont plus disponibles. Veuillez choisir d'autres dates.";
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur : " . $e->getMessage();
    }
}

if (!$errors) {
    try {
        $d1 = new DateTime($date_debut);
        $d2 = new DateTime($date_fin);
        $nuits      = $d1->diff($d2)->days;
        $prix_total = $nuits * $annonce['prix_nuit'];

        $stmt = $pdo->prepare('
            INSERT INTO reservations (id_annonce, id_voyageur, date_debut, date_fin, nb_voyageurs, prix_total)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$id_annonce, $id_voyageur, $date_debut, $date_fin, $nb_voyageurs, $prix_total]);

        // Notifier le propriétaire
        $voyageur_nom = $_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom'];
        $msg = htmlspecialchars($voyageur_nom) . ' a réservé "' . htmlspecialchars($annonce['titre'])
             . '" du ' . $d1->format('d/m/Y') . ' au ' . $d2->format('d/m/Y') . '.';
        $pdo->prepare('INSERT INTO notifications (id_user, type, message, lien) VALUES (?, "reservation_recue", ?, "my-listings.php")')
            ->execute([$annonce['id_proprietaire'], $msg]);

        header('Location: my-bookings.php?success=1');
        exit;
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la réservation : " . $e->getMessage();
    }
}

// En cas d'erreur, on revient sur l'annonce avec le message
$_SESSION['reservation_errors'] = $errors;
header('Location: annonce.php?id=' . $id_annonce . '&booking_error=1');
exit;
