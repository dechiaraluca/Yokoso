<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['user_id'];

$stmt = $pdo->prepare('
    SELECT a.id_annonce, a.titre, a.description, a.ville, a.pays,
           a.prix_nuit, a.capacite_max, a.type_logement,
           p.nom_fichier as photo_principale,
           f.date_ajout
    FROM favoris f
    JOIN annonces a ON f.id_annonce = a.id_annonce
    LEFT JOIN photos p ON p.id_annonce = a.id_annonce AND p.photo_principale = 1
    WHERE f.id_user = ?
    ORDER BY f.date_ajout DESC
');
$stmt->execute([$id_user]);
$favoris = $stmt->fetchAll(PDO::FETCH_ASSOC);
$favoris_ids = array_column($favoris, 'id_annonce');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Mes favoris</title>
  <meta name="description" content="Retrouvez tous vos logements favoris sauvegardés sur YOKOSO.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>

      <div class="favoris-page">
        <div class="favoris-header">
          <h2><i class="fa-solid fa-heart"></i> Mes favoris</h2>
          <span class="favoris-count"><?= count($favoris) ?> logement<?= count($favoris) > 1 ? 's' : '' ?></span>
        </div>

        <?php if (empty($favoris)): ?>
          <div class="empty-state">
            <i class="fa-regular fa-heart"></i>
            <h3>Aucun favori pour l'instant</h3>
            <p>Cliquez sur le cœur d'une annonce pour la sauvegarder ici.</p>
            <a href="logement.php" class="btn-explore">Explorer les logements</a>
          </div>
        <?php else: ?>
          <div class="cards">
            <?php foreach ($favoris as $annonce):
              $photo = !empty($annonce['photo_principale'])
                ? 'uploads/annonces/' . $annonce['photo_principale']
                : 'images/placeholder.jpg';
              $description = strlen($annonce['description']) > 150
                ? substr($annonce['description'], 0, 150) . '...'
                : $annonce['description'];
            ?>
              <article class="card" onclick="window.location.href='annonce.php?id=<?= $annonce['id_annonce'] ?>'">
                <div class="card-thumb-wrap">
                  <img src="<?= htmlspecialchars($photo) ?>"
                       loading="lazy"
                       alt="<?= htmlspecialchars($annonce['titre']) ?>"
                       class="thumb">
                  <button class="card-favorite is-favorite"
                          data-id="<?= $annonce['id_annonce'] ?>"
                          onclick="toggleFavoris(this, event)"
                          aria-label="Retirer des favoris">
                    <i class="fa-solid fa-heart"></i>
                  </button>
                </div>
                <div class="card-header">
                  <div class="name"><?= htmlspecialchars($annonce['titre']) ?></div>
                  <div class="card-location">
                    <i class="fa-solid fa-location-dot"></i>
                    <?= htmlspecialchars($annonce['ville']) ?>, <?= htmlspecialchars($annonce['pays']) ?>
                  </div>
                </div>
                <p class="desc"><?= htmlspecialchars($description) ?></p>
                <div class="card-footer">
                  <span class="price"><?= number_format($annonce['prix_nuit'], 0, ',', ' ') ?>€<small>/nuit</small></span>
                  <span class="capacity"><i class="fa-solid fa-user"></i> <?= $annonce['capacite_max'] ?> pers.</span>
                  <span class="type"><?= ucfirst($annonce['type_logement']) ?></span>
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
