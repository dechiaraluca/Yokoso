<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['photo'];
    
    // Erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload du fichier']);
        exit;
    }
    
    // Taille (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'Le fichier est trop volumineux (max 5MB)']);
        exit;
    }
    
    // Vérifier le type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Format de fichier non autorisé (JPG, PNG, GIF, WEBP uniquement)']);
        exit;
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'user_' . $user_id . '_' . time() . '.' . $extension;
    $uploadDir = 'uploads/profiles/';
    
    // Créer le dossier s'il n'existe pas
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadPath = $uploadDir . $newFileName;
    
    try {
        // Récupérer l'ancienne photo pour la supprimer
        $stmt = $pdo->prepare('SELECT photo_profil FROM users WHERE id_user = ? LIMIT 1');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Déplacer le fichier uploadé
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Mettre à jour la base de données
            $stmt = $pdo->prepare('UPDATE users SET photo_profil = ? WHERE id_user = ?');
            $stmt->execute([$uploadPath, $user_id]);
            
            // Supprimer l'ancienne photo si elle existe
            if ($user['photo_profil'] && file_exists($user['photo_profil'])) {
                unlink($user['photo_profil']);
            }
            
            // Mettre à jour la session
            $_SESSION['user_photo'] = $uploadPath;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Photo mise à jour avec succès',
                'photo_url' => $uploadPath
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors du déplacement du fichier']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur base de données : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Aucun fichier reçu']);
}
?>