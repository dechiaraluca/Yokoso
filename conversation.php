<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$id_user         = $_SESSION['user_id'];
$id_conversation = (int)($_GET['id'] ?? 0);

if (!$id_conversation) {
    header('Location: messages.php');
    exit;
}

// Vérifier accès + récupérer infos conversation
$stmt = $pdo->prepare('
    SELECT c.*,
           a.titre as annonce_titre, a.id_annonce,
           u.prenom as interlocuteur_prenom, u.nom as interlocuteur_nom,
           u.photo_profil as interlocuteur_photo
    FROM conversations c
    LEFT JOIN annonces a ON a.id_annonce = c.id_annonce
    JOIN users u ON u.id_user = CASE WHEN c.id_user1 = ? THEN c.id_user2 ELSE c.id_user1 END
    WHERE c.id_conversation = ? AND (c.id_user1 = ? OR c.id_user2 = ?)
');
$stmt->execute([$id_user, $id_conversation, $id_user, $id_user]);
$conv = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$conv) {
    header('Location: messages.php');
    exit;
}

// Marquer les messages de l'interlocuteur comme lus
$pdo->prepare('UPDATE messages SET lu = 1 WHERE id_conversation = ? AND id_expediteur != ?')
    ->execute([$id_conversation, $id_user]);

// Récupérer les messages
$stmt = $pdo->prepare('
    SELECT m.*, u.prenom, u.photo_profil
    FROM messages m
    JOIN users u ON u.id_user = m.id_expediteur
    WHERE m.id_conversation = ?
    ORDER BY m.date_envoi ASC
');
$stmt->execute([$id_conversation]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Conversation</title>
  <?php include 'includes/head.php'; ?>
</head>
<body class="chat-body">
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content chat-layout">
      <?php include 'includes/header.php'; ?>

      <div class="chat-page">

        <!-- En-tête conversation -->
        <div class="chat-header">
          <a href="messages.php" class="chat-back"><i class="fa-solid fa-arrow-left"></i></a>
          <div class="chat-user">
            <?php if (!empty($conv['interlocuteur_photo']) && file_exists($conv['interlocuteur_photo'])): ?>
              <img src="<?= htmlspecialchars($conv['interlocuteur_photo']) ?>" class="chat-avatar" alt="">
            <?php else: ?>
              <div class="chat-avatar-placeholder">
                <?= strtoupper(mb_substr($conv['interlocuteur_prenom'], 0, 1)) ?>
              </div>
            <?php endif; ?>
            <div>
              <div class="chat-username">
                <?= htmlspecialchars($conv['interlocuteur_prenom'] . ' ' . $conv['interlocuteur_nom']) ?>
              </div>
              <?php if (!empty($conv['annonce_titre'])): ?>
                <a href="annonce.php?id=<?= $conv['id_annonce'] ?>" class="chat-annonce-link">
                  <i class="fa-solid fa-house"></i>
                  <?= htmlspecialchars($conv['annonce_titre']) ?>
                </a>
              <?php endif; ?>
            </div>
          </div>
          <div class="chat-header-actions">
            <button class="chat-delete-btn" onclick="openDeleteModal()" title="Supprimer la conversation">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
        </div>

        <!-- Modal suppression -->
        <div class="chat-delete-modal" id="deleteModal">
          <div class="chat-delete-modal-box">
            <h3>Supprimer la conversation ?</h3>
            <p>Tous les messages seront supprimés définitivement. Cette action est irréversible.</p>
            <div class="modal-actions">
              <button class="btn-cancel-modal" onclick="closeDeleteModal()">Annuler</button>
              <button class="btn-delete-modal" id="deleteConfirmBtn" onclick="deleteConversation()">Supprimer</button>
            </div>
          </div>
        </div>

        <!-- Messages -->
        <div class="chat-messages" id="chatMessages">
          <?php if (empty($messages)): ?>
            <div class="chat-empty">Démarrez la conversation !</div>
          <?php else: ?>
            <?php foreach ($messages as $msg):
              $mine = ($msg['id_expediteur'] == $id_user);
            ?>
              <div class="chat-bubble-wrap <?= $mine ? 'mine' : 'theirs' ?>">
                <?php if (!$mine): ?>
                  <?php if (!empty($msg['photo_profil']) && file_exists($msg['photo_profil'])): ?>
                    <img src="<?= htmlspecialchars($msg['photo_profil']) ?>" class="bubble-avatar" alt="">
                  <?php else: ?>
                    <div class="bubble-avatar bubble-avatar--placeholder">
                      <?= strtoupper(mb_substr($msg['prenom'], 0, 1)) ?>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
                <div class="chat-bubble">
                  <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
                  <span class="bubble-time"><?= (new DateTime($msg['date_envoi']))->format('H:i') ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Zone de saisie -->
        <div class="chat-input-wrap">
          <textarea id="msgInput" placeholder="Votre message..." rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage();}"></textarea>
          <button onclick="sendMessage()" class="chat-send-btn" id="sendBtn">
            <i class="fa-solid fa-paper-plane"></i>
          </button>
        </div>
      </div>
    </main>
  </div>

  <script>
    const convId   = <?= $id_conversation ?>;
    const messages = document.getElementById('chatMessages');

    // Scroll en bas au chargement
    messages.scrollTop = messages.scrollHeight;

    function sendMessage() {
      const input = document.getElementById('msgInput');
      const contenu = input.value.trim();
      if (!contenu) return;

      const btn = document.getElementById('sendBtn');
      btn.disabled = true;

      fetch('send-message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_conversation=' + convId + '&contenu=' + encodeURIComponent(contenu)
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          input.value = '';
          input.style.height = 'auto';
          appendBubble(data.message, data.time);
        }
      })
      .finally(() => { btn.disabled = false; input.focus(); });
    }

    function appendBubble(text, time) {
      const wrap = document.createElement('div');
      wrap.className = 'chat-bubble-wrap mine';
      wrap.innerHTML = `<div class="chat-bubble">${escHtml(text).replace(/\n/g,'<br>')}
        <span class="bubble-time">${time}</span></div>`;
      messages.appendChild(wrap);
      messages.scrollTop = messages.scrollHeight;
    }

    function escHtml(str) {
      return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // Auto-resize textarea
    document.getElementById('msgInput').addEventListener('input', function() {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Supprimer la conversation
    function openDeleteModal() {
      document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.remove('active');
    }

    function deleteConversation() {
      const btn = document.getElementById('deleteConfirmBtn');
      btn.disabled = true;
      btn.textContent = 'Suppression…';

      fetch('delete-conversation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_conversation=' + convId
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          window.location.href = 'messages.php';
        } else {
          btn.disabled = false;
          btn.textContent = 'Supprimer';
          alert(data.error || 'Erreur lors de la suppression.');
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.textContent = 'Supprimer';
      });
    }

    // Fermer le modal en cliquant sur l'overlay
    document.getElementById('deleteModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });
  </script>
</body>
</html>
