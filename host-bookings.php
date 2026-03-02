<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Filtre statut
$filtre = in_array($_GET['statut'] ?? '', ['all', 'upcoming', 'past', 'cancelled']) ? $_GET['statut'] : 'all';

// Conditions selon le filtre
$where_extra = match($filtre) {
    'upcoming'  => "AND r.statut = 'confirmee' AND r.date_fin >= CURDATE()",
    'past'      => "AND r.statut = 'confirmee' AND r.date_fin < CURDATE()",
    'cancelled' => "AND r.statut = 'annulee'",
    default     => '',
};

try {
    $stmt = $pdo->prepare("
        SELECT
            r.id_reservation,
            r.date_debut, r.date_fin,
            r.nb_voyageurs, r.prix_total,
            r.statut, r.date_reservation,
            a.id_annonce, a.titre as annonce_titre, a.ville, a.pays,
            u.prenom as voyageur_prenom, u.nom as voyageur_nom,
            u.photo_profil as voyageur_photo,
            u.id_user as voyageur_id,
            p.nom_fichier as photo
        FROM reservations r
        JOIN annonces a ON r.id_annonce = a.id_annonce
        JOIN users u ON r.id_voyageur = u.id_user
        LEFT JOIN photos p ON p.id_annonce = a.id_annonce AND p.photo_principale = 1
        WHERE a.id_proprietaire = ? $where_extra
        ORDER BY r.date_debut DESC
    ");
    $stmt->execute([$user_id]);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reservations = [];
}

// Compteurs par statut (pour les onglets-filtres)
try {
    $stmt_counts = $pdo->prepare("
        SELECT
            COUNT(*) as total,
            SUM(r.statut = 'confirmee' AND r.date_fin >= CURDATE()) as upcoming,
            SUM(r.statut = 'confirmee' AND r.date_fin < CURDATE())  as past,
            SUM(r.statut = 'annulee')                                as cancelled
        FROM reservations r
        JOIN annonces a ON r.id_annonce = a.id_annonce
        WHERE a.id_proprietaire = ?
    ");
    $stmt_counts->execute([$user_id]);
    $counts = $stmt_counts->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $counts = ['total' => 0, 'upcoming' => 0, 'past' => 0, 'cancelled' => 0];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Réservations reçues</title>
  <meta name="description" content="Consultez et gérez les réservations reçues pour vos logements sur YOKOSO.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>

      <div class="profile-container">
        <!-- Onglets navigation -->
        <div class="profile-tabs">
          <a href="edit-profile.php"  class="profile-tab">Modifier le profil</a>
          <a href="my-bookings.php"   class="profile-tab">Mes réservations</a>
          <a href="my-listings.php"   class="profile-tab">Mes annonces</a>
          <a href="host-bookings.php" class="profile-tab active">Réservations reçues</a>
        </div>

        <!-- Filtres statut -->
        <div class="host-filter-bar">
          <a href="host-bookings.php?statut=all"
             class="host-filter-btn <?= $filtre === 'all' ? 'active' : '' ?>">
            Toutes <span class="filter-count"><?= $counts['total'] ?></span>
          </a>
          <a href="host-bookings.php?statut=upcoming"
             class="host-filter-btn <?= $filtre === 'upcoming' ? 'active' : '' ?>">
            À venir <span class="filter-count"><?= $counts['upcoming'] ?></span>
          </a>
          <a href="host-bookings.php?statut=past"
             class="host-filter-btn <?= $filtre === 'past' ? 'active' : '' ?>">
            Terminées <span class="filter-count"><?= $counts['past'] ?></span>
          </a>
          <a href="host-bookings.php?statut=cancelled"
             class="host-filter-btn <?= $filtre === 'cancelled' ? 'active' : '' ?>">
            Annulées <span class="filter-count"><?= $counts['cancelled'] ?></span>
          </a>
        </div>

        <?php if (empty($reservations)): ?>
          <div class="empty-state">
            <i class="fa-solid fa-calendar-xmark"></i>
            <h2>Aucune réservation</h2>
            <p>
              <?php if ($filtre === 'all'): ?>
                Personne n'a encore réservé vos logements.
              <?php elseif ($filtre === 'upcoming'): ?>
                Aucune réservation à venir.
              <?php elseif ($filtre === 'past'): ?>
                Aucune réservation terminée.
              <?php else: ?>
                Aucune réservation annulée.
              <?php endif; ?>
            </p>
            <?php if ($filtre === 'all'): ?>
              <a href="my-listings.php" class="btn-add-listing">Voir mes annonces</a>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <div class="host-bookings-list">
            <?php foreach ($reservations as $r):
              $d1   = new DateTime($r['date_debut']);
              $d2   = new DateTime($r['date_fin']);
              $nuits = $d1->diff($d2)->days;
              $past  = $d2 < new DateTime('today');
              $upcoming = !$past && $r['statut'] !== 'annulee';
            ?>
              <div class="host-booking-item <?= $r['statut'] === 'annulee' ? 'is-cancelled' : ($past ? 'is-past' : 'is-upcoming') ?>">

                <!-- Photo annonce -->
                <?php if (!empty($r['photo'])): ?>
                  <a href="annonce.php?id=<?= $r['id_annonce'] ?>" class="host-booking-thumb">
                    <img src="uploads/annonces/<?= htmlspecialchars($r['photo']) ?>"
                         alt="<?= htmlspecialchars($r['annonce_titre']) ?>"
                         loading="lazy">
                  </a>
                <?php else: ?>
                  <a href="annonce.php?id=<?= $r['id_annonce'] ?>" class="host-booking-thumb host-booking-thumb--empty">
                    <i class="fa-solid fa-image"></i>
                  </a>
                <?php endif; ?>

                <!-- Infos principales -->
                <div class="host-booking-content">
                  <a href="annonce.php?id=<?= $r['id_annonce'] ?>" class="host-booking-listing">
                    <?= htmlspecialchars($r['annonce_titre']) ?>
                  </a>
                  <p class="host-booking-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <?= htmlspecialchars($r['ville']) ?>, <?= htmlspecialchars($r['pays']) ?>
                  </p>
                  <p class="host-booking-dates">
                    <i class="fa-regular fa-calendar"></i>
                    <?= $d1->format('d/m/Y') ?> → <?= $d2->format('d/m/Y') ?>
                    <span class="nights-badge"><?= $nuits ?> nuit<?= $nuits > 1 ? 's' : '' ?></span>
                  </p>
                  <p class="host-booking-guests">
                    <i class="fa-solid fa-user-group"></i>
                    <?= $r['nb_voyageurs'] ?> voyageur<?= $r['nb_voyageurs'] > 1 ? 's' : '' ?>
                  </p>
                </div>

                <!-- Voyageur -->
                <div class="host-booking-traveler">
                  <?php if (!empty($r['voyageur_photo']) && file_exists($r['voyageur_photo'])): ?>
                    <img src="<?= htmlspecialchars($r['voyageur_photo']) ?>"
                         alt="<?= htmlspecialchars($r['voyageur_prenom']) ?>"
                         class="traveler-avatar">
                  <?php else: ?>
                    <div class="traveler-avatar traveler-avatar--default">
                      <i class="fa-solid fa-user"></i>
                    </div>
                  <?php endif; ?>
                  <div class="traveler-info">
                    <span class="traveler-name"><?= htmlspecialchars($r['voyageur_prenom'] . ' ' . $r['voyageur_nom']) ?></span>
                    <a href="contact.php?user=<?= $r['voyageur_id'] ?>&annonce=<?= $r['id_annonce'] ?>"
                       class="traveler-msg" title="Envoyer un message">
                      <i class="fa-solid fa-comment"></i> Message
                    </a>
                  </div>
                </div>

                <!-- Prix + statut -->
                <div class="host-booking-aside">
                  <span class="booking-status booking-status--<?= $r['statut'] === 'annulee' ? 'cancelled' : ($past ? 'past' : 'confirmed') ?>">
                    <?php if ($r['statut'] === 'annulee'): ?>
                      Annulée
                    <?php elseif ($past): ?>
                      Terminée
                    <?php else: ?>
                      À venir
                    <?php endif; ?>
                  </span>
                  <p class="host-booking-price"><?= number_format($r['prix_total'], 0, ',', ' ') ?> €</p>
                  <span class="host-booking-date-received">
                    Reçue le <?= (new DateTime($r['date_reservation']))->format('d/m/Y') ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>
</body>
</html>
