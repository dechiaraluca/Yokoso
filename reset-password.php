<?php
require_once 'includes/config.php';

$errors = [];
$token = $_GET['token'] ?? '';
$token_valid = false;
$email = '';

if ($token) {
    try {
        $stmt = $pdo->prepare('
            SELECT email 
            FROM password_resets
            WHERE token = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            LIMIT 1
        ');
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $token_valid = true;
            $email = $result['email'];
        } else {
            $errors[] = "Ce lien de réinitialisation est invalide ou a expiré.";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur : " . $e->getMessage();
    }
} else {
    $errors[] = "Aucun token de réinitialisation fourni.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $mot_de_passe_confirm = $_POST['mot_de_passe_confirm'] ?? '';

    if (strlen($mot_de_passe) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if ($mot_de_passe !== $mot_de_passe_confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (!$errors) {
        try {
            // Hasher le nouveau mot de passe
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            // Mettre à jour le mot de passe
            $stmt = $pdo->prepare('UPDATE users SET mot_de_passe = ? WHERE email = ?');
            $stmt->execute([$hash, $email]);

            // Supprimer le token utilisé
            $stmt = $pdo->prepare('DELETE FROM password_resets WHERE token = ?');
            $stmt->execute([$token]);

            // Redirection vers login avec message de succès
            header('Location: login.php?password_reset=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Nouveau mot de passe</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
  <div class="bg-reset-password"></div>
  <div class="overlay-reset-password"></div>
  <div class="page-reset-password">
    <div class="brand-reset-password">
      <img src="images/yokoso-blanc.png" alt="YOKOSO">
    </div>

    <div class="card-reset-password">
      <div class="badge-reset-password">
        <img src="images/logo-blanc-seul-removebg-preview.png" alt="Logo YOKOSO">
      </div>
      <h1>Nouveau mot de passe</h1>
      <p>Choisissez un nouveau mot de passe sécurisé pour votre compte.</p>

      <?php if ($errors): ?>
        <div class="error-reset-password">
          <?php foreach ($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
        </div>
        <p class="link-reset-password">
          <a href="forgot-password.php">Demander un nouveau lien</a>
        </p>
      <?php endif; ?>

      <?php if ($token_valid && !$errors): ?>
        <form action="reset-password.php?token=<?= htmlspecialchars($token) ?>" method="post" autocomplete="off">
          <div>
            <label for="mot_de_passe">Nouveau mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Minimum 8 caractères" minlength="8" required>
          </div>
          <div>
            <label for="mot_de_passe_confirm">Confirmer le mot de passe</label>
            <input type="password" id="mot_de_passe_confirm" name="mot_de_passe_confirm" placeholder="Retapez votre mot de passe" minlength="8" required>
          </div>
          <button type="submit" class="submit-reset-password">Réinitialiser</button>
        </form>

        <p class="link-reset-password">
          <a href="login.php">Retour à la connexion</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>