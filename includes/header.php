<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_data = null;
$notif_count = 0;
$notifications_recentes = [];

if ($is_logged_in) {
    if (!isset($_SESSION['user_prenom']) || !isset($_SESSION['user_nom']) || !isset($_SESSION['user_email'])) {
        require_once __DIR__ . '/config.php';
        try {
            $stmt = $pdo->prepare('SELECT prenom, nom, email FROM users WHERE id_user = ? LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_data) {
                $_SESSION['user_prenom'] = $user_data['prenom'];
                $_SESSION['user_nom'] = $user_data['nom'];
                $_SESSION['user_email'] = $user_data['email'];
            }
        } catch (PDOException $e) {
        }
    }
    
    $user_data = [
        'prenom' => $_SESSION['user_prenom'] ?? '',
        'nom' => $_SESSION['user_nom'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];

    // Notifications
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE id_user = ? AND lu = 0');
        $stmt->execute([$_SESSION['user_id']]);
        $notif_count = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id_user = ? ORDER BY date_creation DESC LIMIT 8');
        $stmt->execute([$_SESSION['user_id']]);
        $notifications_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    // Messages non lus
    $msg_count = 0;
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM messages m
            JOIN conversations c ON c.id_conversation = m.id_conversation
            WHERE m.id_expediteur != ? AND m.lu = 0
              AND (c.id_user1 = ? OR c.id_user2 = ?)
        ');
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
        $msg_count = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {}
}
?>

<!-- Topbar -->
<div class="topbar">
  <button class="burger-btn" id="burgerBtn" aria-label="Menu">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="search-container">
    <div class="search">
      <span><i class="fa-solid fa-magnifying-glass" style="color: #000000;"></i></span>
      <input type="text" id="searchInput" placeholder="Rechercher un logement..." autocomplete="off">
      <button class="icon-btn" title="Filtres" aria-label="Filtres" onclick="openFiltersModal()"><i class="fa-solid fa-filter" style="color: #000000;"></i></button>
    </div>
    <!-- Dropdown des résultats -->
    <div class="search-dropdown" id="searchDropdown"></div>
  </div>
  <div class="notif-wrapper">
    <button class="icon-btn notif-btn" title="Notifications" aria-label="Notifications" onclick="toggleNotifDropdown(event)">
      <i class="fa-solid fa-bell" style="color: #000000;"></i>
      <?php if ($notif_count > 0): ?>
        <span class="notif-badge"><?= $notif_count > 9 ? '9+' : $notif_count ?></span>
      <?php endif; ?>
    </button>
    <?php if ($is_logged_in): ?>
    <div class="notif-dropdown" id="notifDropdown">
      <div class="notif-dropdown-header">
        <span>Notifications</span>
        <?php if ($notif_count > 0): ?>
          <button class="notif-mark-read" onclick="markAllRead()">Tout marquer lu</button>
        <?php endif; ?>
      </div>
      <div class="notif-list">
        <?php if (empty($notifications_recentes)): ?>
          <div class="notif-empty">Aucune notification</div>
        <?php else: ?>
          <?php foreach ($notifications_recentes as $notif): ?>
            <a href="<?= htmlspecialchars($notif['lien'] ?? '#') ?>"
               class="notif-item <?= $notif['lu'] ? '' : 'is-unread' ?>">
              <span class="notif-icon">
                <?= $notif['type'] === 'reservation_annulee' ? '<i class="fa-solid fa-calendar-xmark"></i>' : '<i class="fa-solid fa-calendar-check"></i>' ?>
              </span>
              <span class="notif-content">
                <span class="notif-msg"><?= htmlspecialchars($notif['message']) ?></span>
                <span class="notif-time"><?= (new DateTime($notif['date_creation']))->format('d/m/Y à H:i') ?></span>
              </span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="notif-wrapper topbar-desktop-only">
    <a href="messages.php" class="icon-btn" title="Messages" aria-label="Messages">
      <i class="fa-solid fa-envelope" style="color: #000000;"></i>
      <?php if (!empty($msg_count) && $msg_count > 0): ?>
        <span class="notif-badge"><?= $msg_count > 9 ? '9+' : $msg_count ?></span>
      <?php endif; ?>
    </a>
  </div>
  <a href="my-favoris.php" class="icon-btn topbar-desktop-only" title="Mes favoris" aria-label="Mes favoris"><i class="fa-solid fa-heart" style="color: #000000;"></i></a>

  <?php if ($is_logged_in): ?>
    <div class="user-greeting topbar-desktop-only">
      Bonjour, <?= htmlspecialchars($user_data['prenom']) ?>
    </div>
    <div class="profile-menu-wrapper topbar-desktop-only">
      <button class="icon-btn" title="Mon profil" aria-label="Mon profil" onclick="toggleProfileMenu(event)">
        <i class="fa-solid fa-user" style="color: #000000;"></i>
      </button>
      
      <!-- Menu déroulant -->
      <div class="profile-dropdown" id="profileDropdown">
        <div class="profile-dropdown-header">
          <?php
          // Récupérer la photo de profil si elle existe
          $photo_profil = null;
          if ($is_logged_in) {
              try {
                  require_once __DIR__ . '/config.php';
                  $stmt = $pdo->prepare('SELECT photo_profil FROM users WHERE id_user = ? LIMIT 1');
                  $stmt->execute([$_SESSION['user_id']]);
                  $result = $stmt->fetch(PDO::FETCH_ASSOC);
                  $photo_profil = $result['photo_profil'] ?? null;
              } catch (PDOException $e) {}
          }
          ?>
          <?php if (!empty($photo_profil) && file_exists($photo_profil)): ?>
            <img src="<?= htmlspecialchars($photo_profil) ?>" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
          <?php endif; ?>
          <div>
            <strong><?= htmlspecialchars($user_data['prenom'] . ' ' . $user_data['nom']) ?></strong>
            <span><?= htmlspecialchars($user_data['email']) ?></span>
          </div>
        </div>
        <div class="profile-dropdown-menu">
          <a href="edit-profile.php" class="profile-dropdown-item">
            <i class="fa-solid fa-user-pen"></i>
            <span>Mon profil</span>
          </a>
          <a href="my-listings.php" class="profile-dropdown-item">
            <i class="fa-solid fa-house"></i>
            <span>Mes annonces</span>
          </a>
          <a href="my-bookings.php" class="profile-dropdown-item">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Mes réservations</span>
          </a>
          <div class="profile-dropdown-divider"></div>
          <a href="logout.php" class="profile-dropdown-item logout">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Se déconnecter</span>
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <a href="register.php" class="connexion topbar-desktop-only">S'inscrire</a>
    <a href="login.php" class="connexion topbar-desktop-only">Connexion</a>
  <?php endif; ?>
</div>

<!-- Bottom nav (mobile only) -->
<nav class="bottom-nav">
  <a href="home.php" class="bottom-nav-item">
    <i class="fa-solid fa-house"></i>
    <span>Accueil</span>
  </a>
  <a href="logement.php" class="bottom-nav-item">
    <i class="fa-solid fa-building"></i>
    <span>Logements</span>
  </a>
  <?php if ($is_logged_in): ?>
    <a href="my-favoris.php" class="bottom-nav-item">
      <i class="fa-solid fa-heart"></i>
      <span>Favoris</span>
    </a>
    <a href="messages.php" class="bottom-nav-item">
      <i class="fa-solid fa-envelope"></i>
      <?php if (!empty($msg_count) && $msg_count > 0): ?>
        <span class="bottom-badge"><?= $msg_count > 9 ? '9+' : $msg_count ?></span>
      <?php endif; ?>
      <span>Messages</span>
    </a>
    <a href="edit-profile.php" class="bottom-nav-item">
      <i class="fa-solid fa-user"></i>
      <span>Profil</span>
    </a>
  <?php else: ?>
    <a href="login.php" class="bottom-nav-item">
      <i class="fa-solid fa-right-to-bracket"></i>
      <span>Connexion</span>
    </a>
    <a href="register.php" class="bottom-nav-item">
      <i class="fa-solid fa-user-plus"></i>
      <span>S'inscrire</span>
    </a>
  <?php endif; ?>
</nav>

<!-- Script pour le menu déroulant -->
<script>
  // Menu profil
  function toggleProfileMenu(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
  }

  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const wrapper = document.querySelector('.profile-menu-wrapper');
    
    if (dropdown && wrapper && !wrapper.contains(event.target)) {
      dropdown.classList.remove('active');
    }
  });

  document.getElementById('profileDropdown')?.addEventListener('click', function(event) {
    event.stopPropagation();
  });

  // Recherche en temps réel
  let searchTimeout;
  const searchInput = document.getElementById('searchInput');
  const searchDropdown = document.getElementById('searchDropdown');

  if (searchInput) {
    searchInput.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const query = this.value.trim();

      if (query.length < 2) {
        searchDropdown.classList.remove('active');
        return;
      }

      searchTimeout = setTimeout(() => {
        fetch(`search-ajax.php?q=${encodeURIComponent(query)}`)
          .then(response => response.json())
          .then(data => {
            if (data.success && data.results.length > 0) {
              displaySearchResults(data.results);
            } else {
              searchDropdown.innerHTML = '<div class="search-no-result">Aucun résultat trouvé</div>';
              searchDropdown.classList.add('active');
            }
          })
          .catch(error => {
            console.error('Erreur de recherche:', error);
          });
      }, 300);
    });

    // Fermer le dropdown en cliquant ailleurs
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.search-container')) {
        searchDropdown.classList.remove('active');
      }
    });
  }

  function displaySearchResults(results) {
    let html = '';
    
    results.forEach(result => {
      html += `
        <a href="${result.url}" class="search-result-item">
          <img src="${result.photo}" alt="${result.titre}" class="search-result-img">
          <div class="search-result-info">
            <div class="search-result-title">${result.titre}</div>
            <div class="search-result-meta">
              <span>📍 ${result.ville}, ${result.pays}</span>
              <span>💰 ${result.prix}€/nuit</span>
              <span>👥 ${result.capacite} pers.</span>
            </div>
          </div>
        </a>
      `;
    });

    html += `
      <a href="logement.php?search=${encodeURIComponent(searchInput.value)}" class="search-view-all">
        → Voir tous les résultats
      </a>
    `;

    searchDropdown.innerHTML = html;
    searchDropdown.classList.add('active');
  }

  // Notifications
  function toggleNotifDropdown(event) {
    event.stopPropagation();
    const dd = document.getElementById('notifDropdown');
    if (!dd) return;
    const isOpen = dd.classList.toggle('active');
    if (isOpen) {
      document.getElementById('profileDropdown')?.classList.remove('active');
      markAllRead();
    }
  }

  function markAllRead() {
    fetch('mark-notif-read.php', { method: 'POST' }).then(() => {
      document.querySelectorAll('.notif-item.is-unread').forEach(el => el.classList.remove('is-unread'));
      const badge = document.querySelector('.notif-badge');
      if (badge) badge.remove();
    });
  }

  document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
      document.getElementById('notifDropdown')?.classList.remove('active');
    }
  });

  function toggleFavoris(btn, event) {
    event.stopPropagation();
    const id = btn.dataset.id;
    const icon = btn.querySelector('i');
    const isFav = btn.classList.contains('is-favorite');

    fetch('toggle-favoris.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id_annonce=' + id
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        if (data.action === 'added') {
          btn.classList.add('is-favorite');
          icon.className = 'fa-solid fa-heart';
        } else {
          btn.classList.remove('is-favorite');
          icon.className = 'fa-regular fa-heart';
        }
      }
    });
  }

  function openFiltersModal() {
    const panel = document.querySelector('.filters-panel');
    if (panel) {
      panel.classList.toggle('is-collapsed');
    } else {
      window.location.href = 'logement.php#open-filters';
    }
  }

  // Auto-ouvrir les filtres si on arrive depuis une autre page via le bouton filtre
  if (window.location.hash === '#open-filters') {
    history.replaceState(null, null, window.location.pathname + window.location.search);
    window.addEventListener('DOMContentLoaded', function() {
      const panel = document.querySelector('.filters-panel');
      if (panel) panel.classList.remove('is-collapsed');
    });
  }

  // Burger menu mobile
  (function() {
    const burgerBtn = document.getElementById('burgerBtn');
    if (!burgerBtn) return;

    // Créer l'overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    function openSidebar() {
      document.querySelector('.sidebar')?.classList.add('open');
      overlay.classList.add('active');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      document.querySelector('.sidebar')?.classList.remove('open');
      overlay.classList.remove('active');
      document.body.style.overflow = '';
    }

    burgerBtn.addEventListener('click', function() {
      const isOpen = document.querySelector('.sidebar')?.classList.contains('open');
      isOpen ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    // Fermer si on resize vers desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth > 1024) closeSidebar();
    });
  })();

  <?php if ($is_logged_in): ?>
  // Injecter le lien déconnexion en bas de la sidebar (accessible sur mobile)
  (function() {
    const menu = document.querySelector('.sidebar .menu');
    if (!menu) return;
    const sep = document.createElement('div');
    sep.className = 'sidebar-logout-sep';
    const link = document.createElement('a');
    link.href = 'logout.php';
    link.className = 'sidebar-logout';
    link.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i> Se déconnecter';
    menu.appendChild(sep);
    menu.appendChild(link);
  })();
  <?php endif; ?>

  // ── Transitions de pages
  (function() {
    let isLeaving = false;
    document.addEventListener('click', function(e) {
      if (isLeaving) return;
      const link = e.target.closest('a[href]');
      if (!link) return;
      const href = link.getAttribute('href');
      if (!href || href.startsWith('#') || href.startsWith('javascript') ||
          href.startsWith('mailto') || href.startsWith('tel') ||
          link.target === '_blank' || link.hasAttribute('download') ||
          !link.href.startsWith(window.location.origin)) return;
      isLeaving = true;
      e.preventDefault();
      const dest = link.href;
      document.body.classList.add('is-leaving');
      setTimeout(function() { window.location.href = dest; }, 200);
    });
  })();

  // ── Animation d'apparition des cards au scroll
  (function() {
    if (!('IntersectionObserver' in window)) return;
    const els = document.querySelectorAll('.card, .listing-item, .booking-item, .conv-item');
    if (!els.length) return;
    const observer = new IntersectionObserver(function(entries) {
      let batch = 0;
      entries.forEach(function(entry) {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        el.style.animationDelay = Math.min(batch * 70, 350) + 'ms';
        el.classList.add('anim-visible');
        observer.unobserve(el);
        batch++;
        el.addEventListener('animationend', function() {
          el.classList.remove('anim-ready', 'anim-visible');
          el.style.animationDelay = '';
        }, { once: true });
      });
    }, { threshold: 0.07 });
    els.forEach(function(el) {
      el.classList.add('anim-ready');
      observer.observe(el);
    });
  })();
</script>