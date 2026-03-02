<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_annonce = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_annonce === 0) {
    header('Location: my-listings.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM annonces WHERE id_annonce = ? AND id_proprietaire = ? LIMIT 1");
    $stmt->execute([$id_annonce, $user_id]);
    $annonce = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$annonce) {
        header('Location: my-listings.php');
        exit;
    }
    
    // Récupérer les photos existantes
    $stmt = $pdo->prepare("SELECT * FROM photos WHERE id_annonce = ? ORDER BY photo_principale DESC, ordre_affichage ASC");
    $stmt->execute([$id_annonce]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    header('Location: my-listings.php');
    exit;
}

$errors = [];
$success = false;

// Traitement de la suppression de photo
if (isset($_GET['delete_photo']) && is_numeric($_GET['delete_photo'])) {
    $id_photo = (int)$_GET['delete_photo'];
    try {
        $stmt = $pdo->prepare("SELECT p.* FROM photos p JOIN annonces a ON p.id_annonce = a.id_annonce WHERE p.id_photo = ? AND a.id_proprietaire = ?");
        $stmt->execute([$id_photo, $user_id]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($photo) {
            // Supprimer le fichier
            $filepath = 'uploads/annonces/' . $photo['nom_fichier'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            // Supprimer de la BDD
            $stmt = $pdo->prepare("DELETE FROM photos WHERE id_photo = ?");
            $stmt->execute([$id_photo]);
        }
        header('Location: modifier-annonce.php?id=' . $id_annonce . '&photo_deleted=1');
        exit;
    } catch (PDOException $e) {
        $errors[] = "Erreur lors de la suppression de la photo";
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type_logement = $_POST['type_logement'] ?? '';
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');
    $pays = $_POST['pays'] ?? '';
    $prix_nuit = floatval($_POST['prix_nuit'] ?? 0);
    $nb_chambres = intval($_POST['nb_chambres'] ?? 1);
    $nb_lits = intval($_POST['nb_lits'] ?? 1);
    $nb_sdb = intval($_POST['nb_sdb'] ?? 1);
    $capacite_max = intval($_POST['capacite_max'] ?? 1);
    
    $wifi = isset($_POST['wifi']) ? 1 : 0;
    $parking = isset($_POST['parking']) ? 1 : 0;
    $climatisation = isset($_POST['climatisation']) ? 1 : 0;
    $lave_linge = isset($_POST['lave_linge']) ? 1 : 0;
    $television = isset($_POST['television']) ? 1 : 0;
    $cuisine_equipee = isset($_POST['cuisine_equipee']) ? 1 : 0;
    $seche_cheveux = isset($_POST['seche_cheveux']) ? 1 : 0;
    $animaux_accepte = isset($_POST['animaux_accepte']) ? 1 : 0;
    
    // Validations
    if (empty($titre)) $errors[] = "Le titre est requis";
    if (empty($description)) $errors[] = "La description est requise";
    if (empty($type_logement)) $errors[] = "Le type de logement est requis";
    if (empty($ville)) $errors[] = "La ville est requise";
    if (empty($pays)) $errors[] = "Le pays est requis";
    if ($prix_nuit <= 0) $errors[] = "Le prix doit être supérieur à 0";
    if ($capacite_max <= 0) $errors[] = "La capacité doit être supérieure à 0";
    
    if (empty($errors)) {
        try {
            $sql = "UPDATE annonces SET 
                titre = ?, description = ?, adresse = ?, ville = ?, code_postal = ?, pays = ?,
                prix_nuit = ?, nb_chambres = ?, nb_lits = ?, nb_sdb = ?, capacite_max = ?, type_logement = ?,
                wifi = ?, parking = ?, climatisation = ?, lave_linge = ?, television = ?, 
                cuisine_equipee = ?, seche_cheveux = ?, animaux_accepte = ?
                WHERE id_annonce = ? AND id_proprietaire = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $titre, $description, $adresse, $ville, $code_postal, $pays,
                $prix_nuit, $nb_chambres, $nb_lits, $nb_sdb, $capacite_max, $type_logement,
                $wifi, $parking, $climatisation, $lave_linge, $television,
                $cuisine_equipee, $seche_cheveux, $animaux_accepte,
                $id_annonce, $user_id
            ]);
            
            // Traiter les nouvelles photos uploadées
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = 'uploads/annonces/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Récupérer le dernier ordre
                $stmt = $pdo->prepare("SELECT MAX(ordre_affichage) as max_ordre FROM photos WHERE id_annonce = ?");
                $stmt->execute([$id_annonce]);
                $max = $stmt->fetch(PDO::FETCH_ASSOC);
                $ordre = ($max['max_ordre'] ?? 0) + 1;
                
                foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                        $filename = 'annonce_' . $id_annonce . '_' . time() . '_' . $ordre . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $filepath)) {
                            // Si c'est la première photo, la définir comme principale
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM photos WHERE id_annonce = ?");
                            $stmt->execute([$id_annonce]);
                            $count = $stmt->fetch(PDO::FETCH_ASSOC);
                            $photo_principale = ($count['total'] == 0) ? 1 : 0;
                            
                            $stmt = $pdo->prepare("INSERT INTO photos (id_annonce, nom_fichier, photo_principale, ordre_affichage) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$id_annonce, $filename, $photo_principale, $ordre]);
                            $ordre++;
                        }
                    }
                }
                
                // Recharger les photos
                $stmt = $pdo->prepare("SELECT * FROM photos WHERE id_annonce = ? ORDER BY photo_principale DESC, ordre_affichage ASC");
                $stmt->execute([$id_annonce]);
                $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            $success = true;
            $annonce = array_merge($annonce, [
                'titre' => $titre,
                'description' => $description,
                'adresse' => $adresse,
                'ville' => $ville,
                'code_postal' => $code_postal,
                'pays' => $pays,
                'prix_nuit' => $prix_nuit,
                'nb_chambres' => $nb_chambres,
                'nb_lits' => $nb_lits,
                'nb_sdb' => $nb_sdb,
                'capacite_max' => $capacite_max,
                'type_logement' => $type_logement,
                'wifi' => $wifi,
                'parking' => $parking,
                'climatisation' => $climatisation,
                'lave_linge' => $lave_linge,
                'television' => $television,
                'cuisine_equipee' => $cuisine_equipee,
                'seche_cheveux' => $seche_cheveux,
                'animaux_accepte' => $animaux_accepte
            ]);
            
            $success = true;
            
            // Rediriger vers mes annonces avec message de succès
            header('Location: my-listings.php?updated=1');
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
    <title>YOKOSO - Modifier l'annonce</title>
  <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="page">
    <?php include 'includes/sidebar.php'; ?>

        <main class="content">
            <?php include 'includes/header.php'; ?>

            <div class="publish-container">
                <div class="breadcrumb">
                    <a href="my-listings.php">Mes annonces</a>
                    <span>›</span>
                    <span>Modifier</span>
                </div>

                <h1 class="publish-title">Modifier l'annonce</h1>

                <?php if ($success): ?>
                    <div class="message success">
                        ✓ Annonce mise à jour avec succès !
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['photo_deleted'])): ?>
                    <div class="message success">
                        ✓ Photo supprimée avec succès
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="error-box">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="modifier-annonce.php?id=<?= $id_annonce ?>" method="post" enctype="multipart/form-data" class="publish-form">
                    <!-- Section 1 : Informations principales -->
                    <div class="form-section">
                        <h2>Informations principales</h2>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Titre *</label>
                                <input type="text" name="titre" value="<?= htmlspecialchars($annonce['titre']) ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full">
                                <label>Description *</label>
                                <textarea name="description" rows="6" required><?= htmlspecialchars($annonce['description']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2 : Détails sur le logement -->
                    <div class="form-section">
                        <h2>Détails sur le logement</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Type de logement *</label>
                                <select name="type_logement" required>
                                    <option value="maison" <?= $annonce['type_logement'] === 'maison' ? 'selected' : '' ?>>Maison</option>
                                    <option value="appartement" <?= $annonce['type_logement'] === 'appartement' ? 'selected' : '' ?>>Appartement</option>
                                    <option value="studio" <?= $annonce['type_logement'] === 'studio' ? 'selected' : '' ?>>Studio</option>
                                    <option value="chambre" <?= $annonce['type_logement'] === 'chambre' ? 'selected' : '' ?>>Chambre</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>WiFi</label>
                                <input type="checkbox" name="wifi" value="1" <?= $annonce['wifi'] ? 'checked' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label>Cuisine</label>
                                <input type="checkbox" name="cuisine_equipee" value="1" <?= $annonce['cuisine_equipee'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3 : Localisation -->
                    <div class="form-section">
                        <h2>Localisation</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Pays *</label>
                                <input type="text" name="pays" value="<?= htmlspecialchars($annonce['pays']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Ville *</label>
                                <input type="text" name="ville" value="<?= htmlspecialchars($annonce['ville']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Code postal</label>
                                <input type="text" name="code_postal" value="<?= htmlspecialchars($annonce['code_postal'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group full">
                                <label>Adresse</label>
                                <input type="text" name="adresse" value="<?= htmlspecialchars($annonce['adresse'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Section 4 : Capacités -->
                    <div class="form-section">
                        <h2>Capacités</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Sèche-cheveux</label>
                                <input type="checkbox" name="seche_cheveux" value="1" <?= $annonce['seche_cheveux'] ? 'checked' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label>Animaux</label>
                                <input type="checkbox" name="animaux_accepte" value="1" <?= $annonce['animaux_accepte'] ? 'checked' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label>Télévision</label>
                                <input type="checkbox" name="television" value="1" <?= $annonce['television'] ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Climatisation</label>
                                <input type="checkbox" name="climatisation" value="1" <?= $annonce['climatisation'] ? 'checked' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label>Parking</label>
                                <input type="checkbox" name="parking" value="1" <?= $annonce['parking'] ? 'checked' : '' ?>>
                            </div>

                            <div class="form-group">
                                <label>Lave-linge</label>
                                <input type="checkbox" name="lave_linge" value="1" <?= $annonce['lave_linge'] ? 'checked' : '' ?>>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5 : Informations -->
                    <div class="form-section">
                        <h2>Informations</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Personnes *</label>
                                <input type="number" name="capacite_max" min="1" value="<?= $annonce['capacite_max'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Chambres *</label>
                                <input type="number" name="nb_chambres" min="0" value="<?= $annonce['nb_chambres'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Lits *</label>
                                <input type="number" name="nb_lits" min="1" value="<?= $annonce['nb_lits'] ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Salle de bain *</label>
                                <input type="number" name="nb_sdb" min="1" value="<?= $annonce['nb_sdb'] ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Prix par nuit (€) *</label>
                                <input type="number" name="prix_nuit" min="1" step="1" value="<?= $annonce['prix_nuit'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Section 6 : Photos -->
                    <div class="form-section">
                        <h2>Photos existantes</h2>
                        
                        <?php if (!empty($photos)): ?>
                            <div class="photos-grid">
                                <?php foreach ($photos as $photo): ?>
                                    <div class="photo-item">
                                        <img src="uploads/annonces/<?= htmlspecialchars($photo['nom_fichier']) ?>" 
                                             alt="Photo"
                                             onerror="this.src='images/placeholder.jpg'">
                                        <?php if ($photo['photo_principale']): ?>
                                            <span class="photo-badge">Principale</span>
                                        <?php endif; ?>
                                        <button type="button" 
                                                class="photo-delete" 
                                                onclick="confirmPhotoDelete(<?= $photo['id_photo'] ?>, <?= $id_annonce ?>)"
                                                title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #999; font-size: 14px; margin-bottom: 16px;">Aucune photo pour cette annonce.</p>
                        <?php endif; ?>

                        <h2 style="margin-top: 32px;">Ajouter de nouvelles photos</h2>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Sélectionner des photos (5 max)</label>
                                <input type="file" name="photos[]" accept="image/*" multiple max="5" id="photoInput">
                                <p class="form-hint">
                                    <strong>💡 Astuce :</strong> Pour sélectionner plusieurs photos, maintenez <kbd>Ctrl</kbd> (Windows) ou <kbd>Cmd</kbd> (Mac) en cliquant sur les fichiers.<br>
                                    Formats acceptés : JPG, PNG, WebP (5 MB max par photo)
                                </p>
                                <div id="photoPreview" class="photo-preview"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-publish">Enregistrer les modifications</button>
                        <a href="my-listings.php" class="btn-cancel">Annuler</a>
                    </div>
                </form>
            </div>

      <?php include 'includes/footer.php'; ?>
        </main>
    </div>

    <script>
        function confirmPhotoDelete(photoId, annonceId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette photo ?\n\nCette action est irréversible.')) {
                window.location.href = `modifier-annonce.php?id=${annonceId}&delete_photo=${photoId}`;
            }
        }

        // Prévisualisation des nouvelles photos sélectionnées
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';

            const files = Array.from(e.target.files);

            if (files.length > 0) {
                const countText = document.createElement('p');
                countText.style.marginBottom = '12px';
                countText.style.fontWeight = 'bold';
                countText.innerHTML = `📸 ${files.length} nouvelle(s) photo(s) sélectionnée(s)`;
                preview.appendChild(countText);

                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.width = '100px';
                            img.style.height = '100px';
                            img.style.objectFit = 'cover';
                            img.style.borderRadius = '8px';
                            img.style.marginRight = '8px';
                            img.style.marginBottom = '8px';
                            img.title = file.name;
                            preview.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
</body>
</html>