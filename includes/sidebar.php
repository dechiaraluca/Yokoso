    <aside class="sidebar">
      <div class="logo">
        <a href="home.php" aria-label="YOKOSO - Accueil"><img src="images/logo-blanc-seul.png" class="logo-blanc" alt="YOKOSO"></a>
        <img src="images/yokoso-blanc.png" alt="Yokoso" class="logo-yokoso">
      </div>
      <nav class="menu">
        <a href="home.php">Accueil</a>
        <a href="logement.php">Nos logements</a>
        <?php if (!empty($_SESSION['user_id'])): ?>
          <a href="publier-annonce.php">Publier une annonce</a>
          <a href="edit-profile.php">Mon profil</a>
          <a href="my-bookings.php">Mes réservations</a>
          <a href="my-listings.php">Mes annonces</a>
        <?php endif; ?>
      </nav>
    </aside>
