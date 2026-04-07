<?php 
session_start();
include 'connexion.php';

// On récupère l'ID qui est dans l'URL (ex: details.php?id=12)
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $req = $db->prepare("SELECT * FROM creatures WHERE id = ?");
    $req->execute([$id]);
    $animal = $req->fetch();
}

// Si l'animal n'existe pas, on redirige vers l'accueil
if (!$animal) { 
    header('Location: index.php'); 
    exit(); 
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport : <?php echo $animal['nom']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Playfair+Display:ital,wght@0,400;0,900;1,400&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #1a110a; 
            background-image: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
            color: #d4af37; 
            font-family: 'Playfair Display', serif; 
            padding: 40px 20px;
            margin: 0;
        }

        .fiche-technique { 
            max-width: 900px; 
            margin: 0 auto; 
            background: rgba(43, 24, 16, 0.85); 
            border: 5px double #8b5a2b; 
            padding: 40px; 
            border-radius: 5px;
            box-shadow: 0 0 40px rgba(0,0,0,0.9);
        }

        h1 { font-family: 'Special Elite', cursive; color: #ffd700; font-size: 2.5em; border-bottom: 2px solid #8b5a2b; padding-bottom: 10px; }
        
        .image-container { text-align: center; margin: 30px 0; }
        .image-container img { 
            max-width: 100%; 
            border: 3px solid #ffd700; 
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
            border-radius: 10px;
        }

        .badge-cat { 
            background: #8b5a2b; 
            color: #1a110a; 
            display: inline-block; 
            padding: 8px 25px; 
            border-radius: 20px; 
            font-weight: bold; 
            font-family: 'Special Elite', cursive;
            margin-bottom: 20px;
        }

        .histoire { 
            font-size: 1.4em; 
            line-height: 1.8; 
            text-align: justify; 
            font-style: italic;
            background: rgba(0,0,0,0.2);
            padding: 20px;
            border-left: 4px solid #ffd700;
        }

        .prix-label { 
            font-family: 'Special Elite', cursive; 
            font-size: 2.5em; 
            color: #ffd700; 
            margin-top: 40px; 
        }

        .btn-retour {
            display: inline-block;
            margin-bottom: 20px;
            color: #8b5a2b;
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-retour:hover { color: #ffd700; transform: translateX(-5px); }

        /* AJOUT : Style pour le bouton de commande */
        .btn-commande {
            display: inline-block;
            margin-top: 20px;
            background: #ffd700;
            color: #1a110a;
            padding: 15px 30px;
            text-decoration: none;
            font-family: 'Special Elite', cursive;
            font-weight: bold;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-commande:hover { background: #8b5a2b; color: #ffd700; transform: scale(1.05); }
    </style>
</head>
<body>

    <div class="fiche-technique">
        <a href="index.php" class="btn-retour">← Retour à la collection</a>
        
        <p style="text-align:right; font-family: 'Special Elite'; opacity: 0.6;">DOSSIER REF-<?php echo $animal['id']; ?>-B</p>
        
        <h1>PROTOCOLE : <?php echo strtoupper($animal['nom']); ?></h1>
        
        <div class="image-container">
            <img src="<?php echo $animal['image_path']; ?>" alt="<?php echo $animal['nom']; ?>">
        </div>

        <div class="badge-cat">SÉRIE : <?php echo $animal['categorie']; ?></div>

        <div class="histoire">
            "<?php echo nl2br($animal['description']); ?>"
        </div>

        <div class="prix-label">
            VALEUR : <?php echo $animal['prix']; ?> 🟡
        </div>

        <div style="margin-top: 30px;">
            <a href="contact.php?projet=<?php echo urlencode($animal['nom']); ?>" class="btn-commande">
                ✉️ PASSER COMMANDE POUR CE MODÈLE
            </a>
        </div>

        <hr style="border: 0; border-top: 1px dashed #8b5a2b; margin: 40px 0;">
        
        <p style="font-family: 'Special Elite'; font-size: 0.8em; opacity: 0.5;">
            Document certifié par l'Atelier des Chimères - 1894
        </p>
    </div>

</body>
</html>