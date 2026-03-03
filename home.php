<?php
session_start();
require_once 'includes/config.php';

// Récupérer les 3 logements les plus récents avec leur photo principale
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
                p.nom_fichier as photo_principale,
                ROUND(AVG(av.note), 1) as note_moy,
                COUNT(av.id_avis) as nb_avis
            FROM annonces a
            LEFT JOIN photos p ON a.id_annonce = p.id_annonce AND p.photo_principale = 1
            LEFT JOIN avis av ON av.id_annonce = a.id_annonce
            WHERE a.disponible = 1
            GROUP BY a.id_annonce
            ORDER BY a.date_creation DESC
            LIMIT 3";

    $stmt = $pdo->query($sql);
    $annonces_featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $annonces_featured = [];
}

// Favoris de l'utilisateur connecté
$favoris_ids = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT id_annonce FROM favoris WHERE id_user = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $favoris_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>YOKOSO - Accueil</title>
  <meta name="description" content="Découvrez les meilleurs logements à louer au Japon, en France et ailleurs. Réservez facilement avec YOKOSO.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
  <div class="page">
    <?php include 'includes/sidebar.php'; ?>

    <main class="content">
      <?php include 'includes/header.php'; ?>
      
      <section class="hero" aria-label="Carousel Pays Disponibles">
        <p>YOKOSO est disponible dans ces pays :</p>
        <div class="carousel" data-index="0">
          <div class="slides">
            <div class="slide" data-city="TOKYO" data-country="Japon">
              <img src="images/tokyo-japon.jpg" alt="Tokyo, Japon">
            </div>
            <div class="slide" data-city="PARIS" data-country="France">
              <img src="images/paris-france.jpg" alt="Paris, France">
            </div>
          </div>
          <button class="prev-btn carousel-btn" aria-label="Précédent"><i class="fas fa-chevron-left"></i></button>
          <button class="next-btn carousel-btn" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
          <div class="dots" role="presentation">
            <button class="dot active" aria-label="Aller à Tokyo" data-to="0"></button>
            <button class="dot" aria-label="Aller à Paris" data-to="1"></button>
          </div>
          <div class="overlay"></div>
          <div class="centered">
            <div class="title" id="hero-city">TOKYO</div>
            <div class="subtitle" id="hero-country">Japon</div>
          </div>
        </div>
      </section>

      <section>
        <div class="section-header">
          <h3 class="section-title">Nos logements les plus récents :</h3>
          <a href="logement.php" class="view-all">Voir tous les logements →</a>
        </div>
        
        <?php if (empty($annonces_featured)): ?>
          <p style="text-align: center; padding: 40px; color: #666;">
            Aucun logement disponible pour le moment.
          </p>
        <?php else: ?>
          <div class="cards">
            <?php foreach ($annonces_featured as $annonce):
              // Déterminer le chemin de la photo
              if (!empty($annonce['photo_principale'])) {
                  $photo = 'uploads/annonces/' . $annonce['photo_principale'];
              } else {
                  $photo = 'images/placeholder.jpg';
              }

              // Tronquer la description
              $description = strlen($annonce['description']) > 150
                  ? substr($annonce['description'], 0, 150) . '...'
                  : $annonce['description'];
            ?>
              <article class="card" onclick="window.location.href='annonce.php?id=<?= $annonce['id_annonce'] ?>'">
                <div class="card-thumb-wrap">
                  <img src="<?= htmlspecialchars($photo) ?>"
                       alt="<?= htmlspecialchars($annonce['titre']) ?>"
                       class="thumb"
                       loading="lazy">
                  <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="card-favorite <?= in_array($annonce['id_annonce'], $favoris_ids) ? 'is-favorite' : '' ?>"
                            data-id="<?= $annonce['id_annonce'] ?>"
                            onclick="toggleFavoris(this, event)"
                            aria-label="<?= in_array($annonce['id_annonce'], $favoris_ids) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
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
                
                <p class="desc"><?= htmlspecialchars($description) ?></p>
                
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
      </section>

      <?php include 'includes/footer.php'; ?>
    </main>
  </div>

  <script>
    // Carousel
    (function(){
      const root = document.querySelector('.carousel');
      if(!root) return;
      const slides = root.querySelector('.slides');
      const slideEls = Array.from(root.querySelectorAll('.slide'));
      const prev = root.querySelector('.prev-btn');
      const next = root.querySelector('.next-btn');
      const dots = Array.from(root.querySelectorAll('.dot'));
      const cityEl = document.getElementById('hero-city');
      const countryEl = document.getElementById('hero-country');

      let index = 0;
      function update(){
        slides.style.transform = `translateX(${-index * 100}%)`;
        dots.forEach((d,i)=>d.classList.toggle('active', i===index));
        const s = slideEls[index];
        if(cityEl && countryEl && s){
          cityEl.textContent = s.getAttribute('data-city') || '';
          countryEl.textContent = s.getAttribute('data-country') || '';
        }
      }
      function go(to){ index = (to + slideEls.length) % slideEls.length; update(); }
      prev.addEventListener('click', ()=>go(index-1));
      next.addEventListener('click', ()=>go(index+1));
      dots.forEach((d)=> d.addEventListener('click', ()=>{ go(parseInt(d.getAttribute('data-to')||'0',10)); }));
      let timer = setInterval(()=>go(index+1), 5000);
      root.addEventListener('mouseenter', ()=>clearInterval(timer));
      root.addEventListener('mouseleave', ()=>{ timer = setInterval(()=>go(index+1), 5000); });

      // Support swipe tactile fluide
      let touchStartX = 0, dragging = false;
      root.addEventListener('touchstart', (e)=>{
        touchStartX = e.changedTouches[0].clientX;
        dragging = true;
        slides.style.transition = 'none';
        clearInterval(timer);
      }, { passive: true });
      root.addEventListener('touchmove', (e)=>{
        if (!dragging) return;
        const dx = e.changedTouches[0].clientX - touchStartX;
        slides.style.transform = `translateX(calc(${-index * 100}% + ${dx}px))`;
      }, { passive: true });
      root.addEventListener('touchend', (e)=>{
        if (!dragging) return;
        dragging = false;
        slides.style.transition = 'transform 0.3s ease';
        const dx = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(dx) > 50) go(dx < 0 ? index + 1 : index - 1);
        else update();
        timer = setInterval(()=>go(index+1), 5000);
      }, { passive: true });

      update();
    })();
  </script>
</body>
</html>