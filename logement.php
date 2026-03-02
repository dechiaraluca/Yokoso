<?php
session_start();
require_once 'includes/config.php';

// --- Récupération des filtres ---
$search      = trim($_GET['search'] ?? '');
$type        = $_GET['type'] ?? '';
$prix_max    = isset($_GET['prix_max']) && $_GET['prix_max'] !== '' ? (int)$_GET['prix_max'] : null;
$capacite    = isset($_GET['capacite']) && $_GET['capacite'] !== '' ? (int)$_GET['capacite'] : null;
$wifi        = isset($_GET['wifi']);
$parking     = isset($_GET['parking']);
$clim        = isset($_GET['clim']);
$animaux     = isset($_GET['animaux']);
$lave_linge  = isset($_GET['lave_linge']);
$television  = isset($_GET['television']);
$cuisine     = isset($_GET['cuisine']);
$seche       = isset($_GET['seche']);

$types_valides = ['appartement', 'maison', 'villa', 'chambre'];

// --- Tri ---
$tris_valides = ['recent', 'prix_asc', 'prix_desc', 'note'];
$tri = in_array($_GET['tri'] ?? '', $tris_valides) ? $_GET['tri'] : 'recent';

$order_by = match($tri) {
    'prix_asc'  => 'a.prix_nuit ASC',
    'prix_desc' => 'a.prix_nuit DESC',
    'note'      => 'note_moy DESC, a.date_creation DESC',
    default     => 'a.date_creation DESC',
};

// --- Construction de la requête dynamique ---
$where  = ['a.disponible = 1'];
$params = [];

if ($search !== '') {
    $like = '%' . strtolower($search) . '%';
    $where[]  = '(LOWER(a.titre) LIKE ? OR LOWER(a.description) LIKE ? OR LOWER(a.ville) LIKE ? OR LOWER(a.pays) LIKE ?)';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}
if ($type !== '' && in_array($type, $types_valides)) {
    $where[]  = 'a.type_logement = ?';
    $params[] = $type;
}
if ($prix_max !== null) {
    $where[]  = 'a.prix_nuit <= ?';
    $params[] = $prix_max;
}
if ($capacite !== null) {
    $where[]  = 'a.capacite_max >= ?';
    $params[] = $capacite;
}
if ($wifi)       { $where[] = 'a.wifi = 1'; }
if ($parking)    { $where[] = 'a.parking = 1'; }
if ($clim)       { $where[] = 'a.climatisation = 1'; }
if ($animaux)    { $where[] = 'a.animaux_accepte = 1'; }
if ($lave_linge) { $where[] = 'a.lave_linge = 1'; }
if ($television) { $where[] = 'a.television = 1'; }
if ($cuisine)    { $where[] = 'a.cuisine_equipee = 1'; }
if ($seche)      { $where[] = 'a.seche_cheveux = 1'; }

// --- Pagination ---
$par_page = 12;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $par_page;

// Compter le total
$sql_count = "SELECT COUNT(DISTINCT a.id_annonce)
              FROM annonces a
              WHERE " . implode(' AND ', $where);
try {
    $stmt_c = $pdo->prepare($sql_count);
    $stmt_c->execute($params);
    $total_annonces = (int)$stmt_c->fetchColumn();
} catch (PDOException $e) {
    $total_annonces = 0;
}
$total_pages = $total_annonces > 0 ? (int)ceil($total_annonces / $par_page) : 1;
$page = min($page, $total_pages);

$sql = "SELECT
            a.id_annonce, a.titre, a.description, a.ville, a.pays,
            a.prix_nuit, a.capacite_max, a.type_logement,
            p.nom_fichier as photo_principale,
            ROUND(AVG(av.note), 1) as note_moy,
            COUNT(av.id_avis) as nb_avis
        FROM annonces a
        LEFT JOIN photos p ON a.id_annonce = p.id_annonce AND p.photo_principale = 1
        LEFT JOIN avis av ON av.id_annonce = a.id_annonce
        WHERE " . implode(' AND ', $where) . "
        GROUP BY a.id_annonce
        ORDER BY $order_by
        LIMIT $par_page OFFSET $offset";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $annonces = [];
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

$has_filters = $search !== '' || $type !== '' || $prix_max !== null || $capacite !== null || $wifi || $parking || $clim || $animaux || $lave_linge || $television || $cuisine || $seche;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YOKOSO - Nos logements</title>
    <meta name="description" content="Parcourez tous nos logements disponibles à Tokyo, Paris et dans le monde entier. Filtrez par équipements, prix et capacité.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="page">
    <?php include 'includes/sidebar.php'; ?>

        <main class="content">
            <?php include 'includes/header.php'; ?>

            <section class="logement-section">

                <!-- Panneau de filtres -->
                <form method="get" action="logement.php" class="filters-panel<?= $has_filters ? '' : ' is-collapsed' ?>">
                    <div class="filters-row">
                        <div class="filter-group">
                            <label>Type</label>
                            <div class="select-wrap">
                                <select name="type">
                                    <option value="">Tous</option>
                                    <option value="appartement" <?= $type === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                                    <option value="maison"      <?= $type === 'maison'      ? 'selected' : '' ?>>Maison</option>
                                    <option value="villa"       <?= $type === 'villa'       ? 'selected' : '' ?>>Villa</option>
                                    <option value="chambre"     <?= $type === 'chambre'     ? 'selected' : '' ?>>Chambre</option>
                                </select>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label>Prix max / nuit</label>
                            <div class="price-input-wrap">
                                <input type="number" name="prix_max" min="1" placeholder="ex: 200"
                                       value="<?= htmlspecialchars($prix_max ?? '') ?>">
                                <span class="price-currency">€</span>
                            </div>
                        </div>

                        <div class="filter-group">
                            <label>Voyageurs min</label>
                            <div class="select-wrap">
                                <select name="capacite">
                                    <option value="">Peu importe</option>
                                    <?php foreach ([1,2,3,4,5,6,8,10] as $n): ?>
                                        <option value="<?= $n ?>" <?= $capacite == $n ? 'selected' : '' ?>><?= $n ?>+</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="filter-group filter-group--checks">
                            <label>Équipements</label>
                            <div class="checks-row">
                                <label class="check-label"><input type="checkbox" name="wifi"       <?= $wifi       ? 'checked' : '' ?>> Wifi</label>
                                <label class="check-label"><input type="checkbox" name="parking"    <?= $parking    ? 'checked' : '' ?>> Parking</label>
                                <label class="check-label"><input type="checkbox" name="clim"       <?= $clim       ? 'checked' : '' ?>> Clim</label>
                                <label class="check-label"><input type="checkbox" name="animaux"    <?= $animaux    ? 'checked' : '' ?>> Animaux</label>
                                <label class="check-label"><input type="checkbox" name="lave_linge" <?= $lave_linge ? 'checked' : '' ?>> Lave-linge</label>
                                <label class="check-label"><input type="checkbox" name="television" <?= $television ? 'checked' : '' ?>> TV</label>
                                <label class="check-label"><input type="checkbox" name="cuisine"    <?= $cuisine    ? 'checked' : '' ?>> Cuisine</label>
                                <label class="check-label"><input type="checkbox" name="seche"      <?= $seche      ? 'checked' : '' ?>> Sèche-cheveux</label>
                            </div>
                        </div>

                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="fa-solid fa-filter"></i> Filtrer
                            </button>
                            <?php if ($has_filters): ?>
                                <a href="logement.php" class="btn-reset">
                                    <i class="fa-solid fa-xmark"></i> Réinitialiser
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($search !== ''): ?>
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <?php endif; ?>
                    <?php if ($tri !== 'recent'): ?>
                        <input type="hidden" name="tri" value="<?= htmlspecialchars($tri) ?>">
                    <?php endif; ?>
                </form>

                <!-- Titre + compteur + tri -->
                <div class="section-toolbar">
                    <h3 class="section-title">
                        <?php if ($has_filters): ?>
                            <?= count($annonces) ?> logement<?= count($annonces) > 1 ? 's' : '' ?> trouvé<?= count($annonces) > 1 ? 's' : '' ?>
                            <?= $search !== '' ? ' pour "' . htmlspecialchars($search) . '"' : '' ?>
                        <?php else: ?>
                            Nos logements (<?= count($annonces) ?>) :
                        <?php endif; ?>
                    </h3>
                    <div class="sort-wrap">
                        <label for="triSelect"><i class="fa-solid fa-arrow-down-wide-short"></i></label>
                        <select id="triSelect" name="tri" onchange="applySort(this.value)">
                            <option value="recent"    <?= $tri === 'recent'    ? 'selected' : '' ?>>Plus récents</option>
                            <option value="prix_asc"  <?= $tri === 'prix_asc'  ? 'selected' : '' ?>>Prix croissant</option>
                            <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                            <option value="note"      <?= $tri === 'note'      ? 'selected' : '' ?>>Meilleures notes</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($annonces)): ?>
                    <div class="empty-state" style="text-align:center;padding:60px 20px;color:#666;">
                        <i class="fa-solid fa-house-circle-xmark" style="font-size:48px;color:#ccc;margin-bottom:16px;display:block;"></i>
                        <p>Aucun logement ne correspond à vos critères.</p>
                        <a href="logement.php" style="color:#000;font-weight:600;">Voir tous les logements</a>
                    </div>
                <?php else: ?>
                    <div class="cards">
                        <?php foreach ($annonces as $annonce):
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
                                    <span class="capacity"><i class="fa-solid fa-user"></i> <?= $annonce['capacite_max'] ?> pers.</span>
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

                <!-- Pagination -->
                <?php if ($total_pages > 1):
                    // Construire les params d'URL en conservant les filtres + tri
                    $qp = array_filter([
                        'search'     => $search     ?: null,
                        'type'       => $type       ?: null,
                        'prix_max'   => $prix_max,
                        'capacite'   => $capacite,
                        'wifi'       => $wifi       ? '1' : null,
                        'parking'    => $parking    ? '1' : null,
                        'clim'       => $clim       ? '1' : null,
                        'animaux'    => $animaux    ? '1' : null,
                        'lave_linge' => $lave_linge ? '1' : null,
                        'television' => $television ? '1' : null,
                        'cuisine'    => $cuisine    ? '1' : null,
                        'seche'      => $seche      ? '1' : null,
                        'tri'        => $tri !== 'recent' ? $tri : null,
                    ], fn($v) => $v !== null);

                    function paginUrl(array $qp, int $p): string {
                        $qp['page'] = $p;
                        return 'logement.php?' . http_build_query($qp);
                    }
                ?>
                <nav class="pagination" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="<?= paginUrl($qp, $page - 1) ?>" class="pagin-btn pagin-prev">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php
                    $window = 2;
                    $start  = max(1, $page - $window);
                    $end    = min($total_pages, $page + $window);
                    if ($start > 1): ?>
                        <a href="<?= paginUrl($qp, 1) ?>" class="pagin-btn">1</a>
                        <?php if ($start > 2): ?><span class="pagin-dots">…</span><?php endif; ?>
                    <?php endif; ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                        <a href="<?= paginUrl($qp, $p) ?>"
                           class="pagin-btn <?= $p === $page ? 'is-active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?><span class="pagin-dots">…</span><?php endif; ?>
                        <a href="<?= paginUrl($qp, $total_pages) ?>" class="pagin-btn"><?= $total_pages ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?= paginUrl($qp, $page + 1) ?>" class="pagin-btn pagin-next">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </section>

      <?php include 'includes/footer.php'; ?>
        </main>
    </div>
    <script>
    function applySort(value) {
        const url = new URL(window.location.href);
        if (value === 'recent') {
            url.searchParams.delete('tri');
        } else {
            url.searchParams.set('tri', value);
        }
        window.location.href = url.toString();
    }
    </script>
</body>
</html>
