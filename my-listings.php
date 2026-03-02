<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT
                a.id_annonce,
                a.titre,
                a.description,
                a.ville,
                a.pays,
                a.prix_nuit,
                a.capacite_max,
                a.type_logement,
                a.disponible,
                a.date_creation,
                p.nom_fichier as photo_principale,
                ROUND(AVG(av.note), 1)        as note_moy,
                COUNT(DISTINCT av.id_avis)     as nb_avis,
                COUNT(DISTINCT r.id_reservation) as nb_reservations
            FROM annonces a
            LEFT JOIN photos p ON a.id_annonce = p.id_annonce AND p.photo_principale = 1
            LEFT JOIN avis av ON av.id_annonce = a.id_annonce
            LEFT JOIN reservations r ON r.id_annonce = a.id_annonce AND r.statut != 'annulee'
            WHERE a.id_proprietaire = ?
            GROUP BY a.id_annonce
            ORDER BY a.date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $annonces = [];
}

// Gérer la suppression
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_annonce = (int)$_GET['delete'];
    try {
        // Vérifier que l'annonce appartient bien à l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM annonces WHERE id_annonce = ? AND id_proprietaire = ?");
        $stmt->execute([$id_annonce, $user_id]);
        
        // Supprimer aussi les photos associées
        $stmt = $pdo->prepare("DELETE FROM photos WHERE id_annonce = ?");
        $stmt->execute([$id_annonce]);
        
        header('Location: my-listings.php?deleted=1');
        exit;
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression";
    }
}

try {
    $stmt = $pdo->prepare('SELECT prenom, nom, email FROM users WHERE id_user = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Mes annonces</title>
  <meta name="description" content="Gérez vos annonces de logements sur YOKOSO : publiez, modifiez et suivez vos réservations.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>

      <div class="profile-container">
        <!-- Onglets -->
        <div class="profile-tabs">
          <a href="edit-profile.php"  class="profile-tab">Modifier le profil</a>
          <a href="my-bookings.php"   class="profile-tab">Mes réservations</a>
          <a href="my-listings.php"   class="profile-tab active">Mes annonces</a>
          <a href="host-bookings.php" class="profile-tab">Réservations reçues</a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
          <div class="message success">
            ✓ Annonce supprimée avec succès
          </div>
        <?php endif; ?>

        <?php if (empty($annonces)): ?>
          <!-- État vide -->
          <div class="empty-state">
            <i class="fa-solid fa-house-circle-xmark"></i>
            <h2>Aucune annonce</h2>
            <p>Vous n'avez pas encore publié d'annonce.</p>
            <a href="publier-annonce.php" class="btn-add-listing"> Publier une annonce </a>
          </div>
        <?php else: ?>
          <!-- Liste des annonces -->
          <div class="listings-grid">
            <?php foreach ($annonces as $annonce):
              // Déterminer le chemin de la photo
              if (!empty($annonce['photo_principale'])) {
                  $photo = 'uploads/annonces/' . $annonce['photo_principale'];
              } else {
                  $photo = 'images/placeholder.jpg';
              }

              $description_courte = strlen($annonce['description']) > 200
                  ? substr($annonce['description'], 0, 200) . '...'
                  : $annonce['description'];
            ?>
              <div class="listing-item" data-id="<?= $annonce['id_annonce'] ?>">
                <div class="listing-thumb-wrap">
                  <img src="<?= htmlspecialchars($photo) ?>"
                       alt="<?= htmlspecialchars($annonce['titre']) ?>"
                       class="listing-image"
                       loading="lazy">
                  <span class="listing-badge <?= $annonce['disponible'] ? 'badge-active' : 'badge-inactive' ?>">
                    <?= $annonce['disponible'] ? 'Active' : 'Inactive' ?>
                  </span>
                </div>

                <div class="listing-content">
                  <div class="listing-header">
                    <h3><?= htmlspecialchars($annonce['titre']) ?></h3>
                    <span class="listing-date">Publiée le <?= date('d/m/Y', strtotime($annonce['date_creation'])) ?></span>
                  </div>

                  <div class="listing-meta">
                    <span><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($annonce['ville']) ?>, <?= htmlspecialchars($annonce['pays']) ?></span>
                    <span><i class="fa-solid fa-euro-sign"></i> <?= number_format($annonce['prix_nuit'], 0, ',', ' ') ?>€/nuit</span>
                    <span><i class="fa-solid fa-user"></i> <?= $annonce['capacite_max'] ?> pers.</span>
                  </div>

                  <div class="listing-stats">
                    <span class="stat">
                      <i class="fa-solid fa-calendar-check"></i>
                      <?= $annonce['nb_reservations'] ?> réservation<?= $annonce['nb_reservations'] != 1 ? 's' : '' ?>
                    </span>
                    <?php if ($annonce['nb_avis'] > 0): ?>
                      <span class="stat">
                        <i class="fa-solid fa-star"></i>
                        <?= $annonce['note_moy'] ?> (<?= $annonce['nb_avis'] ?> avis)
                      </span>
                    <?php else: ?>
                      <span class="stat stat--muted">
                        <i class="fa-regular fa-star"></i> Aucun avis
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="listing-actions">
                  <button class="action-btn toggle-dispo <?= $annonce['disponible'] ? 'is-active' : '' ?>"
                          onclick="toggleDispo(this, <?= $annonce['id_annonce'] ?>)"
                          title="<?= $annonce['disponible'] ? 'Désactiver' : 'Activer' ?>">
                    <i class="fa-solid <?= $annonce['disponible'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                  </button>
                  <a href="annonce.php?id=<?= $annonce['id_annonce'] ?>"
                     class="action-btn"
                     title="Voir l'annonce">
                    <i class="fa-solid fa-eye"></i>
                  </a>
                  <a href="modifier-annonce.php?id=<?= $annonce['id_annonce'] ?>"
                     class="action-btn"
                     title="Modifier">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                  <button onclick="confirmDelete(<?= $annonce['id_annonce'] ?>, '<?= htmlspecialchars($annonce['titre'], ENT_QUOTES) ?>')"
                          class="action-btn delete"
                          title="Supprimer">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="add-listing-btn-container">
            <a href="publier-annonce.php" class="btn-add-listing">
              <i class="fa-solid fa-plus"></i> Publier une nouvelle annonce
            </a>
          </div>
        <?php endif; ?>
      </div>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>

  <script>
    function confirmDelete(id, titre) {
      if (confirm(`Êtes-vous sûr de vouloir supprimer l'annonce "${titre}" ?\n\nCette action est irréversible.`)) {
        window.location.href = `my-listings.php?delete=${id}`;
      }
    }

    async function toggleDispo(btn, id) {
      btn.disabled = true;
      try {
        const res = await fetch('toggle-disponible.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${id}`
        });
        const data = await res.json();
        if (data.success) {
          const isActive = data.disponible === 1;
          const card = btn.closest('.listing-item');
          const badge = card.querySelector('.listing-badge');

          btn.classList.toggle('is-active', isActive);
          btn.title = isActive ? 'Désactiver' : 'Activer';
          btn.querySelector('i').className = `fa-solid ${isActive ? 'fa-toggle-on' : 'fa-toggle-off'}`;

          badge.className = `listing-badge ${isActive ? 'badge-active' : 'badge-inactive'}`;
          badge.textContent = isActive ? 'Active' : 'Inactive';
        }
      } catch (e) {}
      btn.disabled = false;
    }
  </script>
</body>
</html>