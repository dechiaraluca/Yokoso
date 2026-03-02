<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['user_id'];

// Toutes les conversations de l'utilisateur avec le dernier message et l'interlocuteur
$stmt = $pdo->prepare('
    SELECT
        c.id_conversation,
        c.id_annonce,
        c.date_dernier_message,
        a.titre as annonce_titre,
        -- Interlocuteur
        CASE WHEN c.id_user1 = ? THEN c.id_user2 ELSE c.id_user1 END as id_interlocuteur,
        u.prenom as interlocuteur_prenom,
        u.nom    as interlocuteur_nom,
        u.photo_profil as interlocuteur_photo,
        -- Dernier message
        (SELECT contenu FROM messages m WHERE m.id_conversation = c.id_conversation ORDER BY m.date_envoi DESC LIMIT 1) as dernier_message,
        -- Non lus
        (SELECT COUNT(*) FROM messages m WHERE m.id_conversation = c.id_conversation AND m.id_expediteur != ? AND m.lu = 0) as non_lus
    FROM conversations c
    JOIN users u ON u.id_user = CASE WHEN c.id_user1 = ? THEN c.id_user2 ELSE c.id_user1 END
    LEFT JOIN annonces a ON a.id_annonce = c.id_annonce
    WHERE c.id_user1 = ? OR c.id_user2 = ?
    ORDER BY c.date_dernier_message DESC
');
$stmt->execute([$id_user, $id_user, $id_user, $id_user, $id_user]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_non_lus = array_sum(array_column($conversations, 'non_lus'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Messages</title>
  <meta name="description" content="Accédez à vos conversations avec les hôtes et voyageurs sur YOKOSO.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>

      <div class="messagerie-page">
        <div class="messagerie-header">
          <h2><i class="fa-solid fa-envelope"></i> Messages</h2>
          <?php if ($total_non_lus > 0): ?>
            <span class="msg-count-badge"><?= $total_non_lus ?> non lu<?= $total_non_lus > 1 ? 's' : '' ?></span>
          <?php endif; ?>
        </div>

        <?php if (empty($conversations)): ?>
          <div class="empty-state-msg">
            <i class="fa-regular fa-envelope"></i>
            <h3>Aucun message</h3>
            <p>Contactez un hôte depuis la page d'une annonce pour démarrer une conversation.</p>
            <a href="logement.php" class="btn-explore-msg">Explorer les logements</a>
          </div>
        <?php else: ?>
          <div class="conv-list">
            <?php foreach ($conversations as $conv): ?>
              <a href="conversation.php?id=<?= $conv['id_conversation'] ?>"
                 class="conv-item <?= $conv['non_lus'] > 0 ? 'has-unread' : '' ?>">

                <div class="conv-avatar">
                  <?php if (!empty($conv['interlocuteur_photo']) && file_exists($conv['interlocuteur_photo'])): ?>
                    <img src="<?= htmlspecialchars($conv['interlocuteur_photo']) ?>" alt="">
                  <?php else: ?>
                    <div class="conv-avatar-placeholder">
                      <?= strtoupper(mb_substr($conv['interlocuteur_prenom'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="conv-body">
                  <div class="conv-top">
                    <span class="conv-name">
                      <?= htmlspecialchars($conv['interlocuteur_prenom'] . ' ' . $conv['interlocuteur_nom']) ?>
                    </span>
                    <span class="conv-time">
                      <?= (new DateTime($conv['date_dernier_message']))->format('d/m · H:i') ?>
                    </span>
                  </div>
                  <?php if (!empty($conv['annonce_titre'])): ?>
                    <div class="conv-annonce">
                      <i class="fa-solid fa-house"></i>
                      <?= htmlspecialchars($conv['annonce_titre']) ?>
                    </div>
                  <?php endif; ?>
                  <div class="conv-preview">
                    <?= htmlspecialchars(mb_substr($conv['dernier_message'] ?? '…', 0, 80)) ?>
                  </div>
                </div>

                <?php if ($conv['non_lus'] > 0): ?>
                  <span class="conv-badge"><?= $conv['non_lus'] ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>
</body>
</html>
