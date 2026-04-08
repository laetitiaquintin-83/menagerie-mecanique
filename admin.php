<?php 
session_start();
require_once 'config.php';
require_once 'helpers.php';

// PROTECTION : Vérifier que l'admin est connecté
require_admin_connection();

// --- LOGIQUE D'AJOUT D'UNE CHIMÈRE ---
$error = null;
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Requête invalide. Veuillez réessayer.';
    } else {
        // Valider les données
        $nom = isset($_POST['nom']) ? sanitize_text($_POST['nom'], 100) : null;
        $cat = isset($_POST['categorie']) ? sanitize_text($_POST['categorie']) : null;
        $prix = isset($_POST['prix']) ? intval($_POST['prix']) : null;
        
        // CORRECTION : On utilise trim() pour ne pas transformer les apostrophes en codes HTML en BD
        // La sécurité SQL est assurée par la requête préparée ($ins->execute)
        $desc = isset($_POST['description']) ? trim($_POST['description']) : null;
        
        if (!$nom || !$cat || !$prix || !$desc) {
            $error = 'Tous les champs sont requis.';
        } elseif ($prix <= 0) {
            $error = 'Le prix doit être positif.';
        } else {
            // Traiter l'upload
            $image_path = handle_image_upload('image');
            
            if ($image_path === null) {
                $error = $_SESSION['upload_error'] ?? 'Erreur lors de l\'upload du fichier.';
            } else {
                try {
                    // Les paramètres (?) garantissent qu'aucune injection SQL n'est possible
                    $ins = $db->prepare("INSERT INTO creatures (nom, categorie, prix, description, image_path) VALUES (?,?,?,?,?)");
                    $ins->execute([$nom, $cat, $prix, $desc, $image_path]);
                    
                    $success = true;
                    set_flash_message('Chimère créée avec succès !', 'success');
                    header('Location: index.php');
                    exit();
                } catch (PDOException $e) {
                    error_log('Erreur création chimère: ' . $e->getMessage());
                    $error = 'Erreur lors de la création. Veuillez réessayer.';
                    // Supprimer l'image si la base de données a échoué
                    delete_image_file($image_path);
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
    <title>La Forge des Chimères</title>
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
        .btn-creer { 
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
        .btn-creer:hover { 
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
    </style>
</head>
<body>

    <div class="forge-container">
        <a href="index.php" class="link-back">← Retourner à l'Atelier</a>
        
        <h1>⚒️ Registre de Création</h1>
        
        <p style="font-style: italic; font-size: 0.9em;">Session de l'Inventrice : <strong><?= htmlspecialchars($_SESSION['nom_admin'] ?? 'Admin') ?></strong></p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?php csrf_input(); ?>
            
            <input type="text" name="nom" placeholder="Nom de la Chimère" required maxlength="100">
            
            <select name="categorie" required>
                <option value="">-- Choisir une série --</option>
                <option value="Explorateur">Série Explorateur</option>
                <option value="Mécanicien">Série Mécanicien</option>
                <option value="Colosse">Série Colosse</option>
            </select>
            
            <input type="number" name="prix" placeholder="Valeur (Pièces d'or)" min="1" required>
            
            <textarea name="description" rows="5" placeholder="Détails des engrenages et histoire..." required maxlength="5000"></textarea>
            
            <label class="file-input-label">Schéma visuel (Image - Max 5MB) :</label>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
            
            <button type="submit" name="ajouter" class="btn-creer">SCELLER LA CRÉATION</button>
        </form>
    </div>

</body>
</html>