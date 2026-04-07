<?php 
session_start();
include 'connexion.php';

// PROTECTION : On vérifie si l'admin est bien connecté avec sa session sécurisée
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { 
    header('Location: login.php'); 
    exit(); 
}

// --- LOGIQUE D'AJOUT D'UNE CHIMÈRE ---
if(isset($_POST['ajouter'])) {
    $nom = htmlspecialchars($_POST['nom']); // Sécurité contre les failles XSS
    $cat = $_POST['categorie'];
    $prix = intval($_POST['prix']); // On s'assure que c'est un nombre
    $desc = htmlspecialchars($_POST['description']);
    
    $image = $_FILES['image']['name'];
    // On nettoie le nom du fichier pour éviter les espaces ou caractères bizarres
    $image_nom = time() . "_" . basename($image); 
    $target = "images/" . $image_nom;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        $ins = $db->prepare("INSERT INTO creatures (nom, categorie, prix, description, image_path) VALUES (?,?,?,?,?)");
        $ins->execute([$nom, $cat, $prix, $desc, $target]);
        header('Location: index.php?statut=succes');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
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
            background: #f4e4bc; /* Couleur vieux papier */
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
    </style>
</head>
<body>

    <div class="forge-container">
        <a href="index.php" class="link-back">← Retourner à l'Atelier</a>
        
        <h1>⚒️ Registre de Création</h1>
        
        <p style="font-style: italic; font-size: 0.9em;">Session de l'Inventrice : <strong><?php echo $_SESSION['nom_admin']; ?></strong></p>

        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="nom" placeholder="Nom de la Chimère" required>
            
            <select name="categorie">
                <option value="Explorateur">Série Explorateur</option>
                <option value="Mécanicien">Série Mécanicien</option>
                <option value="Colosse">Série Colosse</option>
            </select>
            
            <input type="number" name="prix" placeholder="Valeur (Pièces d'or)" required>
            
            <textarea name="description" rows="5" placeholder="Détails des engrenages et histoire..."></textarea>
            
            <div style="text-align: left; width: 90%; margin: 0 auto;">
                <p style="font-size: 0.8em; font-weight: bold; margin-bottom: 5px;">Schéma visuel (Image) :</p>
                <input type="file" name="image" required style="border: none; background: none; padding: 0;">
            </div>
            
            <button type="submit" name="ajouter" class="btn-creer">SCELLER LA CRÉATION</button>
        </form>
    </div>

</body>
</html>