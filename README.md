![Yokoso Banner](images/banner.png)

# Yokoso — Plateforme de location de logements

> Projet étudiant inspiré d'Airbnb, développé en PHP procédural / MySQL / SCSS.

---

## Fonctionnalités

- **Authentification** — inscription, connexion, déconnexion, réinitialisation de mot de passe
- **Annonces** — créer, modifier, supprimer, galerie photos, carte de localisation (Leaflet.js)
- **Recherche** — recherche AJAX temps réel + filtres (wifi, parking, climatisation, animaux)
- **Tri & pagination** — tri par date / prix / note, 12 annonces par page
- **Réservations** — formulaire avec calcul de prix dynamique, détection de conflits de dates
- **Messagerie** — conversations, envoi de messages, suppression de conversation
- **Notifications** — temps réel dans le header
- **Favoris** — toggle AJAX, page dédiée
- **Avis & notes** — formulaire étoiles, grille d'avis sur la fiche annonce
- **Profil hôte public** — statistiques, note moyenne, toggle disponibilité des annonces
- **Réservations reçues** — tableau de bord hôte avec filtres par statut
- **Responsive** — mobile-first, bottom-nav, sidebar slide, breakpoints 480/768/1024px

---

## Stack technique

| Couche      | Technologie                              |
|-------------|------------------------------------------|
| Backend     | PHP 8 procédural, PDO                    |
| BDD         | MySQL (WAMP / phpMyAdmin)                |
| Frontend    | HTML5, SCSS compilé, Vanilla JS          |
| Icônes      | Font Awesome 6 (CDN)                     |
| Polices     | Google Fonts — Inter                     |
| Carte       | Leaflet.js + Nominatim (OpenStreetMap)   |
| Build CSS   | Node.js + Sass 1.93.2                    |

---

## Structure du projet

```
yokoso/
├── assets/
│   ├── css/          # CSS compilé (main.css)
│   └── scss/
│       ├── main.scss              # Point d'entrée SCSS
│       ├── abstracts/_variables   # Variables couleurs, typographie
│       ├── base/                  # Reset, typographie, animations
│       ├── components/            # Navbar, cartes, boutons, carousel, recherche
│       ├── layout/                # Sidebar, footer
│       └── pages/                 # Styles spécifiques par page
├── doc/              # Personas UX (PDF)
├── images/           # Images statiques (logo, etc.)
├── includes/
│   ├── config.php    # Connexion PDO
│   ├── head.php      # Balises <head> communes
│   ├── header.php    # Topbar + bottom-nav mobile + JS global
│   ├── sidebar.php   # Sidebar avec navigation et auth conditionnelle
│   └── footer.php    # Footer HTML
├── sound/            # Sons UI
├── uploads/          # Photos uploadées (annonces, avatars)
└── *.php             # Pages de l'application
```

---

## Pages principales

| Fichier                | Description                                  |
|------------------------|----------------------------------------------|
| `home.php`             | Accueil — carousel + annonces vedettes       |
| `logement.php`         | Catalogue annonces — filtres, tri, pagination|
| `annonce.php`          | Fiche annonce — galerie, carte, réservation  |
| `login.php`            | Connexion (param `?redirect=` supporté)      |
| `register.php`         | Inscription                                  |
| `edit-profile.php`     | Modifier le profil + upload avatar           |
| `profil.php`           | Profil public d'un hôte                      |
| `publier-annonce.php`  | Créer une annonce                            |
| `modifier-annonce.php` | Modifier une annonce                         |
| `my-listings.php`      | Mes annonces — stats, badge, toggle dispo    |
| `my-bookings.php`      | Mes réservations — annulation, laisser un avis|
| `host-bookings.php`    | Réservations reçues (hôte) — filtres statut  |
| `reservation.php`      | Traitement d'une réservation                 |
| `messages.php`         | Liste des conversations                      |
| `conversation.php`     | Chat — layout full-height avec barre de saisie|
| `my-favoris.php`       | Favoris sauvegardés                          |

---

## Base de données

Tables : `users`, `annonces`, `photos`, `reservations`, `messages`, `conversations`, `notifications`, `favoris`, `avis`, `password_resets`

---

## Personas UX

Le projet a été conçu autour de 4 personas définis dans `/doc/` :

| Persona          | Profil                                          |
|------------------|-------------------------------------------------|
| Aiko Sazawa      | Étudiante, 19 ans, Tokyo — séjour solo à Paris  |
| Elena Gilbert    | Community Manager, 27 ans — déplacement pro     |
| Jennie Rose      | Secrétaire, 32 ans — tourisme multi-villes      |
| Ishiki Jishaku   | Étudiant, 21 ans — groupe festival              |

---

## Scripts npm disponibles

```bash
npm run sass           # Compilation SCSS (non compressé)
npm run sass:build     # Compilation SCSS compressée (production)
npm run sass:watch     # Watch + recompilation automatique
```

---

## Déploiement

Le projet est hébergé sur **InfinityFree**.

Site : [yokoso.infinityfree.me](https://yokoso.infinityfree.me)