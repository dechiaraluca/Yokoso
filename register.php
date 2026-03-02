<?php
require_once 'includes/config.php';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    $prenom   = trim($_POST['prenom'] ?? '');
    $nom      = trim($_POST['nom'] ?? '');
    $email    = trim($_POST['email'] ?? ''); 
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $telephone    = trim($_POST['telephone'] ?? '');

    if ($prenom === '' || $nom === '') {
        $errors[] = "Le prénom et le nom sont requis.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }

    if (strlen($mot_de_passe) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('SELECT id_user FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]); 

            if ($stmt->fetch()) { 
                $errors[] = "Cet e-mail est déjà utilisé.";
            } else {
                $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare(
                    'INSERT INTO users (prenom, nom, email, mot_de_passe, telephone, date_inscription) 
                     VALUES (?, ?, ?, ?, ?, NOW())'
                );
                $stmt->execute([$prenom, $nom, $email, $hash, $telephone]); 

                header('Location: login.php?registered=1'); 
                exit;
            }
        } catch (PDOException $e) { 
            $errors[] = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Inscription</title>
  <meta name="description" content="Créez votre compte YOKOSO et commencez à réserver des logements ou à publier vos annonces.">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
  <div class="bg-register"></div>
  <div class="overlay-register"></div>

  <div class="page-register">
    <div class="brand-register">
      <img src="images/yokoso-blanc.png" alt="YOKOSO">
    </div>

    <div class="card-register">
      <div class="badge-register">
        <img src="images/logo-blanc-seul-removebg-preview.png" alt="Logo YOKOSO">
      </div>

      <h1>S'inscrire</h1>

      <?php if ($errors): ?>
        <div class="error-register">
          <?php foreach ($errors as $error): ?>
            <p><?= htmlspecialchars($error) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form action="register.php" method="post" autocomplete="on">
        <div>
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" placeholder="Nom" required>
        </div>
        <div>
          <label for="prenom">Prénom</label>
          <input type="text" id="prenom" name="prenom" placeholder="Prénom" required>
        </div>
        <div>
          <label for="email">Adresse mail</label>
          <input type="email" id="email" name="email" placeholder="Adresse mail" inputmode="email" autocomplete="email" required>
        </div>
        <div>
          <label for="mot_de_passe">Mot de passe</label>
          <input type="password" id="mot_de_passe" name="mot_de_passe" placeholder="Mot de passe" minlength="8" required>
        </div>
        <div>
          <label for="telephone">Numéro de téléphone (optionnel)</label>
          <input type="tel" id="telephone" name="telephone" placeholder="Numéro de téléphone" inputmode="tel">
        </div>
        <button type="submit" class="submit-register">Terminé</button>
      </form>
    </div>
  </div>
</body>
</html>