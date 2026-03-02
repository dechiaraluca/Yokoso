<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;
$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare('SELECT prenom, nom, email, telephone, date_inscription, photo_profil, date_naissance FROM users WHERE id_user = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Erreur lors de la récupération des données.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');

    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $change_password = $new_password !== '';

    if (empty($prenom) || empty($nom)) {
        $errors[] = "Le prénom et le nom sont requis.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if ($change_password) {
        if (strlen($new_password) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('SELECT id_user FROM users WHERE email = ? AND id_user != ? LIMIT 1');
            $stmt->execute([$email, $user_id]);

            if ($stmt->fetch()) {
                $errors[] = "Cet email est déjà utilisé par un autre compte.";
            } else {
                $stmt = $pdo->prepare('
                    UPDATE users
                    SET prenom = ?, nom = ?, email = ?, telephone = ?, date_naissance = ?
                    WHERE id_user = ?
                ');
                $stmt->execute([$prenom, $nom, $email, $telephone, $date_naissance ?: null, $user_id]);

                if ($change_password) {
                    $stmt = $pdo->prepare('UPDATE users SET mot_de_passe = ? WHERE id_user = ?');
                    $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
                }

                $_SESSION['user_prenom'] = $prenom;
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_email'] = $email;

                $user['prenom'] = $prenom;
                $user['nom'] = $nom;
                $user['email'] = $email;
                $user['telephone'] = $telephone;
                $user['date_naissance'] = $date_naissance;

                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

$annee_inscription = date('Y', strtotime($user['date_inscription']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Mon Profil</title>
  <meta name="description" content="Gérez votre profil YOKOSO : informations personnelles, mot de passe et photo de profil.">
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
          <a href="edit-profile.php"  class="profile-tab active">Modifier le profil</a>
          <a href="my-bookings.php"   class="profile-tab">Mes réservations</a>
          <a href="my-listings.php"   class="profile-tab">Mes annonces</a>
          <a href="host-bookings.php" class="profile-tab">Réservations reçues</a>
        </div>

        <!-- Messages -->
        <?php if ($success): ?>
          <div class="message success">
            ✓ Profil mis à jour avec succès !
          </div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="message error">
            <?php foreach ($errors as $error): ?>
              <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Header profil -->
        <div class="profile-header">
          <div class="profile-avatar" onclick="document.getElementById('photoInput').click()">
            <?php if (!empty($user['photo_profil']) && file_exists($user['photo_profil'])): ?>
              <img src="<?= htmlspecialchars($user['photo_profil']) ?>" alt="Photo de profil" id="avatarPreview">
            <?php else: ?>
              <i class="fa-solid fa-user" style="font-size: 48px; color: #999;" id="avatarIcon"></i>
            <?php endif; ?>
            <div class="avatar-overlay">
              <i class="fa-solid fa-camera"></i>
            </div>
            <div class="avatar-loading" id="avatarLoading">
              <div class="spinner"></div>
            </div>
          </div>
          <input type="file" id="photoInput" accept="image/*" style="display: none;">
          
          <div class="profile-info">
            <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
            <p>Inscrit depuis <?= htmlspecialchars($annee_inscription) ?></p>
          </div>
        </div>

        <!-- Formulaire -->
        <form method="post" action="edit-profile.php">
          <div class="profile-form">
            <div class="form-field">
              <label>Prénom:</label>
              <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>
            </div>

            <div class="form-field">
              <label>Nom:</label>
              <input type="text" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
            </div>

            <div class="form-field">
              <label>Email:</label>
              <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-field">
              <label>Téléphone:</label>
              <input type="tel" name="telephone" placeholder="Optionnel" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
            </div>

            <div class="form-field">
              <label>Naissance:</label>
              <input type="date" name="date_naissance" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
            </div>
          </div>

          <!-- Changer le mot de passe -->
          <div class="password-section">
            <button type="button" class="password-toggle" id="passwordToggle">
              <i class="fa-solid fa-lock"></i>
              Changer le mot de passe
              <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="password-fields" id="passwordFields">
              <div class="form-field">
                <label>Nouveau mot de passe:</label>
                <input type="password" name="new_password" placeholder="Min. 8 caractères" autocomplete="new-password">
              </div>
              <div class="form-field">
                <label>Confirmer:</label>
                <input type="password" name="confirm_password" placeholder="Répéter le mot de passe" autocomplete="new-password">
              </div>
            </div>
          </div>

          <button type="submit" class="save-btn">Sauvegarder</button>
        </form>
      </div>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>

  <script>
    // Password section toggle
    const passwordToggle = document.getElementById('passwordToggle');
    const passwordFields = document.getElementById('passwordFields');

    passwordToggle.addEventListener('click', function() {
      this.classList.toggle('open');
      passwordFields.classList.toggle('open');
      // Clear fields when closing
      if (!passwordFields.classList.contains('open')) {
        passwordFields.querySelectorAll('input').forEach(i => i.value = '');
      }
    });

    <?php if ($errors && ($new_password ?? '') !== ''): ?>
    // Re-open password section if there were password errors
    passwordToggle.classList.add('open');
    passwordFields.classList.add('open');
    <?php endif; ?>

    const photoInput = document.getElementById('photoInput');
    const avatarPreview = document.getElementById('avatarPreview');
    const avatarIcon = document.getElementById('avatarIcon');
    const avatarLoading = document.getElementById('avatarLoading');
    const profileAvatar = document.querySelector('.profile-avatar');

    photoInput.addEventListener('change', async function(e) {
      const file = e.target.files[0];
      if (!file) return;

      // Type de fichier
      if (!file.type.match('image.*')) {
        alert('Veuillez sélectionner une image');
        return;
      }

      // Taille (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        alert('L\'image est trop volumineuse (max 5MB)');
        return;
      }

      // Afficher le loader
      avatarLoading.classList.add('active');

      // Créer un FormData pour l'upload
      const formData = new FormData();
      formData.append('photo', file);

      try {
        const response = await fetch('upload-avatar.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Mettre à jour l'aperçu
          if (avatarPreview) {
            avatarPreview.src = result.photo_url + '?' + new Date().getTime();
          } else {
            // Créer l'image si elle n'existe pas
            if (avatarIcon) avatarIcon.remove();
            const img = document.createElement('img');
            img.id = 'avatarPreview';
            img.src = result.photo_url + '?' + new Date().getTime();
            img.alt = 'Photo de profil';
            profileAvatar.insertBefore(img, profileAvatar.firstChild);
          }

          // Recharger la page pour mettre à jour partout
          setTimeout(() => {
            location.reload();
          }, 500);
        } else {
          alert('Erreur : ' + result.message);
        }
      } catch (error) {
        alert('Erreur lors de l\'upload : ' + error.message);
      } finally {
        avatarLoading.classList.remove('active');
      }
    });
  </script>
</body>
</html>