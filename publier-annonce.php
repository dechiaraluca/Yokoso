<?php
session_start();
require_once 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données
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
    
    // Équipements (booléens)
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
            // Insérer l'annonce
            $sql = "INSERT INTO annonces (
                id_proprietaire, titre, description, adresse, ville, code_postal, pays,
                prix_nuit, nb_chambres, nb_lits, nb_sdb, capacite_max, type_logement,
                wifi, parking, climatisation, lave_linge, television, 
                cuisine_equipee, seche_cheveux, animaux_accepte, disponible
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
            )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_SESSION['user_id'], $titre, $description, $adresse, $ville, $code_postal, $pays,
                $prix_nuit, $nb_chambres, $nb_lits, $nb_sdb, $capacite_max, $type_logement,
                $wifi, $parking, $climatisation, $lave_linge, $television,
                $cuisine_equipee, $seche_cheveux, $animaux_accepte
            ]);
            
            $id_annonce = $pdo->lastInsertId();
            
            // Traiter les photos uploadées
            if (!empty($_FILES['photos']['name'][0])) {
                $uploadDir = 'uploads/annonces/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $ordre = 1;
                foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                        $extension = pathinfo($_FILES['photos']['name'][$key], PATHINFO_EXTENSION);
                        $filename = 'annonce_' . $id_annonce . '_' . time() . '_' . $ordre . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $filepath)) {
                            $photo_principale = ($ordre === 1) ? 1 : 0;
                            $stmt = $pdo->prepare("INSERT INTO photos (id_annonce, nom_fichier, photo_principale, ordre_affichage) VALUES (?, ?, ?, ?)");
                            $stmt->execute([$id_annonce, $filename, $photo_principale, $ordre]);
                            $ordre++;
                        }
                    }
                }
            }
            
            $success = true;
            
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la publication : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YOKOSO - Publier une annonce</title>
    <meta name="description" content="Publiez votre logement sur YOKOSO et commencez à accueillir des voyageurs du monde entier.">
  <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="page">
    <?php include 'includes/sidebar.php'; ?>

        <main class="content">
            <?php include 'includes/header.php'; ?>

            <div class="publish-container">
                <?php if ($success): ?>
                    <!-- Message de succès -->
                    <div class="success-message">
                        <div class="success-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h1>Votre annonce a bien été prise en compte par YOKOSO</h1>
                        <p>Elle sera visible publiquement après validation par notre équipe.</p>
                        <div class="success-actions">
                            <a href="my-listings.php" class="btn btn-primary">Voir mes annonces</a>
                            <a href="home.php" class="btn btn-secondary">Retour à l'accueil</a>
                        </div>
                    </div>
                <?php else: ?>
                    <h1 class="publish-title">Proposer un logement</h1>

                    <?php if (!empty($errors)): ?>
                        <div class="error-box">
                            <?php foreach ($errors as $error): ?>
                                <p><?= htmlspecialchars($error) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form action="publier-annonce.php" method="post" enctype="multipart/form-data" class="publish-form">
                        <!-- Section 1 : Informations principales -->
                        <div class="form-section">
                            <h2>Informations principales</h2>
                            <div class="form-row">
                                <div class="form-group full">
                                    <label>Titre *</label>
                                    <input type="text" name="titre" placeholder="Ex: Appartement cosy au cœur de Paris" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full">
                                    <label>Description *</label>
                                    <textarea name="description" rows="6" placeholder="Décrivez votre logement..." required></textarea>
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
                                        <option value="">Sélectionner...</option>
                                        <option value="maison">Maison</option>
                                        <option value="appartement">Appartement</option>
                                        <option value="studio">Studio</option>
                                        <option value="chambre">Chambre</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>WiFi</label>
                                    <select name="wifi">
                                        <option value="1">Oui</option>
                                        <option value="0">Non</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Cuisine</label>
                                    <select name="cuisine_equipee">
                                        <option value="1">Oui</option>
                                        <option value="0">Non</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3 : Localisation -->
                        <div class="form-section">
                            <h2>Localisation</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Pays *</label>
                                    <input type="text" name="pays" placeholder="Ex: France" required>
                                </div>

                                <div class="form-group">
                                    <label>Ville *</label>
                                    <input type="text" name="ville" placeholder="Ex: Paris" required>
                                </div>

                                <div class="form-group">
                                    <label>Code postal</label>
                                    <input type="text" name="code_postal" placeholder="Ex: 75001">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group full">
                                    <label>Adresse</label>
                                    <input type="text" name="adresse" placeholder="Ex: 12 Rue de la Paix">
                                </div>
                            </div>
                        </div>

                        <!-- Section 4 : Capacités -->
                        <div class="form-section">
                            <h2>Capacités</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Sèche-cheveux</label>
                                    <input type="checkbox" name="seche_cheveux" value="1">
                                </div>

                                <div class="form-group">
                                    <label>Animaux</label>
                                    <input type="checkbox" name="animaux_accepte" value="1">
                                </div>

                                <div class="form-group">
                                    <label>Télévision</label>
                                    <input type="checkbox" name="television" value="1">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Climatisation</label>
                                    <input type="checkbox" name="climatisation" value="1">
                                </div>

                                <div class="form-group">
                                    <label>Parking</label>
                                    <input type="checkbox" name="parking" value="1">
                                </div>

                                <div class="form-group">
                                    <label>Lave-linge</label>
                                    <input type="checkbox" name="lave_linge" value="1">
                                </div>
                            </div>
                        </div>

                        <!-- Section 5 : Informations -->
                        <div class="form-section">
                            <h2>Informations</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Personnes *</label>
                                    <input type="number" name="capacite_max" min="1" value="2" required>
                                </div>

                                <div class="form-group">
                                    <label>Chambres *</label>
                                    <input type="number" name="nb_chambres" min="0" value="1" required>
                                </div>

                                <div class="form-group">
                                    <label>Lits *</label>
                                    <input type="number" name="nb_lits" min="1" value="1" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Salle de bain *</label>
                                    <input type="number" name="nb_sdb" min="1" value="1" required>
                                </div>

                                <div class="form-group">
                                    <label>Prix par nuit (€) *</label>
                                    <input type="number" name="prix_nuit" min="1" step="1" placeholder="Ex: 85" required>
                                </div>
                            </div>
                        </div>

                        <!-- Section 6 : Photos -->
                        <div class="form-section">
                            <h2>Photos</h2>
                            <div class="form-row">
                                <div class="form-group full">
                                    <label>Ajouter des photos (5 max)</label>
                                    <input type="file" name="photos[]" accept="image/*" multiple max="5" id="photoInput">
                                    <p class="form-hint">
                                        <strong>💡 Astuce :</strong> Pour sélectionner plusieurs photos, maintenez <kbd>Ctrl</kbd> (Windows) ou <kbd>Cmd</kbd> (Mac) en cliquant sur les fichiers.<br>
                                        Formats acceptés : JPG, PNG, WebP (5 MB max par photo)
                                    </p>
                                    <div id="photoPreview" class="photo-preview"></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn-publish">Publier l'annonce</button>
                    </form>
                <?php endif; ?>
            </div>

      <?php include 'includes/footer.php'; ?>
        </main>
    </div>

    <script>
        // Prévisualisation des photos sélectionnées
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const preview = document.getElementById('photoPreview');
            preview.innerHTML = '';

            const files = Array.from(e.target.files);

            if (files.length > 0) {
                const countText = document.createElement('p');
                countText.style.marginBottom = '12px';
                countText.style.fontWeight = 'bold';
                countText.innerHTML = `📸 ${files.length} photo(s) sélectionnée(s)`;
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