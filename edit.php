<?php 
session_start();
require_once 'config.php';
require_once 'helpers.php';

// PROTECTION : Vérifier que l'admin est connecté
require_admin_connection();

// Récupérer l'ID de la créature à modifier
if (!isset($_GET['id']) || !validate_positive_integer($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);

// Récupérer la créature
try {
    $req = $db->prepare("SELECT * FROM creatures WHERE id = ?");
    $req->execute([$id]);
    $creature = $req->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération créature: ' . $e->getMessage());
    header('Location: index.php');
    exit();
}

// Si la créature n'existe pas, rediriger
if (!$creature) {
    header('Location: index.php');
    exit();
}

// --- LOGIQUE DE MODIFICATION ---
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Requête invalide. Veuillez réessayer.';
    } else {
        // Valider les données
        $nom = isset($_POST['nom']) ? sanitize_text($_POST['nom'], 100) : null;
        $cat = isset($_POST['categorie']) ? sanitize_text($_POST['categorie']) : null;
        $prix = isset($_POST['prix']) ? intval($_POST['prix']) : null;
        $desc = isset($_POST['description']) ? trim($_POST['description']) : null;
        
        if (!$nom || !$cat || !$prix || !$desc) {
            $error = 'Tous les champs sont requis.';
        } elseif ($prix <= 0) {
            $error = 'Le prix doit être positif.';
        } else {
            // Gérer le nouvel upload (optionnel)
            $image_path = $creature['image_path'];
            
            if (!empty($_FILES['image']['name'])) {
                // Uploader une nouvelle image
                $new_path = handle_image_upload('image');
                
                if ($new_path === null) {
                    $error = $_SESSION['upload_error'] ?? 'Erreur lors de l\'upload du fichier.';
                } else {
                    // Supprimer l'ancienne image
                    delete_image_file($creature['image_path']);
                    $image_path = $new_path;
                }
            }
            
            if (!$error) {
                try {
                    // Mettre à jour la créature
                    $upd = $db->prepare("UPDATE creatures SET nom = ?, categorie = ?, prix = ?, description = ?, image_path = ? WHERE id = ?");
                    $upd->execute([$nom, $cat, $prix, $desc, $image_path, $id]);
                    
                    set_flash_message('Chimère modifiée avec succès !', 'success');
                    header('Location: index.php');
                    exit();
                } catch (PDOException $e) {
                    error_log('Erreur modification chimère: ' . $e->getMessage());
                    $error = 'Erreur lors de la modification. Veuillez réessayer.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la Chimère - L'Atelier</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background: #1a110a; 
            color: #d4af37; 
            font-family: 'Georgia', serif; 
            padding: 50px 20px; 
            text-align: center; 
        }
        .forge-container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #f4e4bc;
            padding: 40px; 
            border: 10px double #8b5a2b; 
            color: #2b1810;
            box-shadow: 20px 20px 0px #3b2313;
        }
        h1 { 
            font-family: 'Courier New', Courier, monospace; 
            text-transform: uppercase; 
            border-bottom: 2px solid #2b1810; 
            padding-bottom: 10px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        input, textarea, select { 
            width: 90%; 
            padding: 12px; 
            margin: 12px 0; 
            background: rgba(139, 90, 43, 0.1); 
            border: 1px solid #8b5a2b;
            font-family: 'Georgia', serif; 
            font-size: 1.1em;
            border-radius: 3px;
        }
        .btn-modifier { 
            background: #2b1810; 
            color: #f4e4bc; 
            padding: 15px 30px; 
            border: none; 
            cursor: pointer; 
            font-weight: bold; 
            font-size: 1.2em;
            transition: 0.3s;
            margin-top: 20px;
            width: auto;
        }
        .btn-modifier:hover { 
            background: #8b5a2b; 
            letter-spacing: 2px; 
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        .link-back {
            display: inline-block;
            margin-bottom: 20px;
            color: #8b5a2b;
            text-decoration: none;
            font-weight: bold;
        }
        .link-back:hover { color: #2b1810; }
        .file-input-label {
            text-align: left;
            width: 90%;
            margin: 0 auto;
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .image-preview {
            max-width: 200px;
            margin: 15px 0;
            border: 2px solid #8b5a2b;
            border-radius: 5px;
        }
    </style>
</head>
<body>

    <div class="forge-container">
        <a href="index.php" class="link-back">← Retourner à l'Atelier</a>
        
        <h1>⚙️ Reprendre une Chimère</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?php csrf_input(); ?>
            
            <label class="file-input-label">Nom de la chimère</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($creature['nom']) ?>" required>
            
            <label class="file-input-label">Catégorie</label>
            <select name="categorie" required>
                <option value="Explorateur" <?= $creature['categorie'] === 'Explorateur' ? 'selected' : '' ?>>Explorateur</option>
                <option value="Mécanicien" <?= $creature['categorie'] === 'Mécanicien' ? 'selected' : '' ?>>Mécanicien</option>
                <option value="Colosse" <?= $creature['categorie'] === 'Colosse' ? 'selected' : '' ?>>Colosse</option>
                <option value="Inclassable" <?= $creature['categorie'] === 'Inclassable' ? 'selected' : '' ?>>Inclassable</option>
            </select>
            
            <label class="file-input-label">Prix (pièces d'or)</label>
            <input type="number" name="prix" value="<?= htmlspecialchars($creature['prix']) ?>" required>
            
            <label class="file-input-label">Description</label>
            <textarea name="description" rows="6" required><?= htmlspecialchars($creature['description']) ?></textarea>
            
            <label class="file-input-label">Image actuelle</label>
            <img src="<?= htmlspecialchars($creature['image_path']) ?>" class="image-preview" alt="<?= htmlspecialchars($creature['nom']) ?>">
            
            <label class="file-input-label">Nouvelle image (optionnel)</label>
            <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif,.webp">
            
            <button type="submit" name="modifier" class="btn-modifier">💾 ENREGISTRER LES MODIFICATIONS</button>
        </form>
    </div>

</body>
</html>
