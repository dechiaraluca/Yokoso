<?php
session_start();
require_once 'includes/config.php';

$id_hote = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_hote === 0) {
    header('Location: home.php');
    exit;
}

// Infos de l'hôte
try {
    $stmt = $pdo->prepare('SELECT id_user, prenom, nom, photo_profil, date_inscription FROM users WHERE id_user = ? LIMIT 1');
    $stmt->execute([$id_hote]);
    $hote = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$hote) {
        header('Location: home.php');
        exit;
    }
} catch (PDOException $e) {
    header('Location: home.php');
    exit;
}

// Annonces actives de l'hôte avec stats
try {
    $stmt = $pdo->prepare("
        SELECT a.id_annonce, a.titre, a.description, a.ville, a.pays,
               a.prix_nuit, a.capacite_max, a.type_logement,
               p.nom_fichier as photo,
               ROUND(AVG(av.note), 1) as note_moy,
               COUNT(DISTINCT av.id_avis) as nb_avis
        FROM annonces a
        LEFT JOIN photos p ON p.id_annonce = a.id_annonce AND p.photo_principale = 1
        LEFT JOIN avis av ON av.id_annonce = a.id_annonce
        WHERE a.id_proprietaire = ? AND a.disponible = 1
        GROUP BY a.id_annonce
        ORDER BY a.date_creation DESC
    ");
    $stmt->execute([$id_hote]);
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $annonces = [];
}

// Stats globales de l'hôte
$notes   = array_filter(array_column($annonces, 'note_moy'), fn($n) => $n !== null);
$note_globale = !empty($notes) ? round(array_sum($notes) / count($notes), 1) : null;
$total_avis   = array_sum(array_column($annonces, 'nb_avis'));

// Date d'inscription en français
$mois_fr = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$d = new DateTime($hote['date_inscription']);
$date_display = $mois_fr[(int)$d->format('n')] . ' ' . $d->format('Y');

// Favoris de l'utilisateur connecté
$user_id    = $_SESSION['user_id'] ?? null;
$favoris_ids = [];
if ($user_id) {
    try {
        $stmt = $pdo->prepare('SELECT id_annonce FROM favoris WHERE id_user = ?');
        $stmt->execute([$user_id]);
        $favoris_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - <?= htmlspecialchars($hote['prenom'] . ' ' . $hote['nom']) ?></title>
  <meta name="description" content="Découvrez le profil de <?= htmlspecialchars($hote['prenom'] . ' ' . $hote['nom']) ?> sur YOKOSO.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>

      <div class="profil-page">

        <!-- En-tête du profil -->
        <div class="profil-hero">
          <div class="profil-avatar-wrap">
            <?php if (!empty($hote['photo_profil']) && file_exists($hote['photo_profil'])): ?>
              <img src="<?= htmlspecialchars($hote['photo_profil']) ?>"
                   alt="<?= htmlspecialchars($hote['prenom']) ?>"
                   class="profil-avatar">
            <?php else: ?>
              <div class="profil-avatar profil-avatar--default">
                <i class="fa-solid fa-user"></i>
              </div>
            <?php endif; ?>
          </div>

          <div class="profil-info">
            <h1><?= htmlspecialchars($hote['prenom'] . ' ' . $hote['nom']) ?></h1>
            <p class="profil-since">
              <i class="fa-regular fa-calendar"></i> Hôte depuis <?= $date_display ?>
            </p>
            <div class="profil-stats">
              <span class="profil-stat">
                <i class="fa-solid fa-home"></i>
                <?= count($annonces) ?> logement<?= count($annonces) > 1 ? 's' : '' ?>
              </span>
              <?php if ($note_globale !== null): ?>
                <span class="profil-stat">
                  <i class="fa-solid fa-star"></i>
                  <?= $note_globale ?> · <?= $total_avis ?> avis
                </span>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($user_id && $user_id !== $id_hote): ?>
            <a href="contact.php?user=<?= $id_hote ?>"
               class="profil-contact-btn">
              <i class="fa-solid fa-envelope"></i> Contacter
            </a>
          <?php endif; ?>
        </div>

        <!-- Annonces de l'hôte -->
        <?php if (empty($annonces)): ?>
          <div class="empty-state" style="padding: 60px 20px;">
            <i class="fa-solid fa-house-circle-xmark" style="font-size:56px; color:#ccc; display:block; margin-bottom:16px;"></i>
            <h2 style="font-size:20px; color:#333; margin-bottom:8px;">Aucun logement disponible</h2>
            <p style="color:#888; font-size:15px;">Cet hôte n'a pas encore de logement actif.</p>
          </div>
        <?php else: ?>
          <h2 class="profil-listings-title">
            Logements de <?= htmlspecialchars($hote['prenom']) ?>
          </h2>
          <div class="cards">
            <?php foreach ($annonces as $annonce):
              $photo = !empty($annonce['photo'])
                ? 'uploads/annonces/' . $annonce['photo']
                : 'images/placeholder.jpg';
              $desc = strlen($annonce['description']) > 150
                ? substr($annonce['description'], 0, 150) . '...'
                : $annonce['description'];
            ?>
              <article class="card" onclick="window.location.href='annonce.php?id=<?= $annonce['id_annonce'] ?>'">
                <div class="card-thumb-wrap">
                  <img src="<?= htmlspecialchars($photo) ?>"
                       alt="<?= htmlspecialchars($annonce['titre']) ?>"
                       class="thumb"
                       loading="lazy">
                  <?php if ($user_id): ?>
                    <button class="card-favorite <?= in_array($annonce['id_annonce'], $favoris_ids) ? 'is-favorite' : '' ?>"
                            data-id="<?= $annonce['id_annonce'] ?>"
                            onclick="toggleFavoris(this, event)">
                      <i class="fa-<?= in_array($annonce['id_annonce'], $favoris_ids) ? 'solid' : 'regular' ?> fa-heart"></i>
                    </button>
                  <?php endif; ?>
                </div>
                <div class="card-header">
                  <div class="name"><?= htmlspecialchars($annonce['titre']) ?></div>
                  <div class="card-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <?= htmlspecialchars($annonce['ville']) ?>, <?= htmlspecialchars($annonce['pays']) ?>
                  </div>
                </div>
                <p class="desc"><?= htmlspecialchars($desc) ?></p>
                <div class="card-footer">
                  <span class="price"><?= number_format($annonce['prix_nuit'], 0, ',', ' ') ?>€<small>/nuit</small></span>
                  <span class="capacity">
                    <i class="fa-solid fa-user"></i> <?= $annonce['capacite_max'] ?> pers.
                  </span>
                  <?php if (!empty($annonce['note_moy'])): ?>
                    <span class="card-rating"><i class="fa-solid fa-star"></i> <?= $annonce['note_moy'] ?></span>
                  <?php else: ?>
                    <span class="type"><?= ucfirst($annonce['type_logement']) ?></span>
                  <?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>
</body>
</html>
