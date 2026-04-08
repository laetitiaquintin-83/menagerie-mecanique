<?php 
session_start();
require_once 'config.php';
require_once 'helpers.php';

$animal = null;

// Récupérer et valider l'ID
if (isset($_GET['id']) && validate_positive_integer($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $req = $db->prepare("SELECT * FROM creatures WHERE id = ?");
        $req->execute([$id]);
        $animal = $req->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur récupération détails: ' . $e->getMessage());
    }
}

// Si l'animal n'existe pas, rediriger
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
    <title>Rapport : <?= htmlspecialchars($animal['nom']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&family=Playfair+Display:ital,wght@0,400;0,900;1,400&display=swap" rel="stylesheet">
    <style>
        /* --- CURSEUR ET STYLE GLOBAL --- */
        :root {
            --curseur-cle: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'><path fill='%23b87333' stroke='%235d3a1a' stroke-width='1' d='M10 4C6.7 4 4 6.7 4 10c0 2.5 1.5 4.6 3.7 5.5L4 28l3 3 12.5-3.7c.9 2.2 3 3.7 5.5 3.7 3.3 0 6-2.7 6-6s-2.7-6-6-6c-1.5 0-2.8.5-3.8 1.4L15.5 14c2.2-.9 3.7-3 3.7-5.5 0-3.3-2.7-6-6-6zm0 3c1.7 0 3 1.3 3 3s-1.3 3-3 3-3-1.3-3-3 1.3-3 3-3z'/></svg>") 5 5;
        }

        body { 
            background-color: #1a110a; 
            background-image: url('https://www.transparenttextures.com/patterns/carbon-fibre.png');
            color: #d4af37; 
            font-family: 'Playfair Display', serif; 
            padding: 40px 20px;
            margin: 0;
            cursor: var(--curseur-cle), auto !important;
        }

        a, button { cursor: var(--curseur-cle), pointer !important; }

        .fiche-technique { 
            max-width: 900px; 
            margin: 0 auto; 
            background: rgba(43, 24, 16, 0.9); 
            border: 5px double #8b5a2b; 
            padding: 40px; 
            border-radius: 5px;
            box-shadow: 0 0 40px rgba(0,0,0,0.9);
            position: relative;
        }

        h1 { font-family: 'Special Elite', cursive; color: #ffd700; font-size: 2.5em; border-bottom: 2px solid #8b5a2b; padding-bottom: 10px; margin-top: 10px; }
        
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
            font-size: 1.3em; 
            line-height: 1.6; 
            text-align: justify; 
            font-style: italic;
            background: rgba(0,0,0,0.3);
            padding: 25px;
            border-left: 4px solid #ffd700;
            color: #f4e4bc;
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

        /* BOUTON PANIER */
        .btn-panier {
            display: inline-block;
            margin-top: 20px;
            background: linear-gradient(135deg, #ffd700 0%, #b8860b 100%);
            color: #1a110a;
            padding: 18px 35px;
            text-decoration: none;
            font-family: 'Special Elite', cursive;
            font-weight: bold;
            font-size: 1.1em;
            border-radius: 5px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
            transition: 0.3s;
            border: none;
        }
        .btn-panier:hover { 
            transform: scale(1.05) translateY(-3px); 
            box-shadow: 0 6px 20px rgba(212, 175, 55, 0.4);
            filter: brightness(1.1);
        }
    </style>
</head>
<body>

    <div class="fiche-technique">
        <a href="index.php" class="btn-retour">← Retour à la collection</a>
        
        <p style="text-align:right; font-family: 'Special Elite'; opacity: 0.6; margin: 0;">DOSSIER REF-<?= htmlspecialchars($animal['id']) ?>-B</p>
        
        <h1>PROTOCOLE : <?= html_entity_decode(htmlspecialchars(strtoupper($animal['nom']))) ?></h1>
        
        <div class="image-container">
            <img src="<?= htmlspecialchars($animal['image_path']) ?>" alt="<?= htmlspecialchars($animal['nom']) ?>">
        </div>

        <div class="badge-cat">SÉRIE : <?= htmlspecialchars($animal['categorie']) ?></div>

        <div class="histoire">
            "<?= nl2br(html_entity_decode(htmlspecialchars($animal['description']))) ?>"
        </div>

        <div class="prix-label">
            VALEUR : <?= htmlspecialchars($animal['prix']) ?> 🟡
        </div>

        <div style="margin-top: 40px;">
            <a href="ajouter_panier.php?id=<?= $animal['id'] ?>&nom=<?= urlencode($animal['nom']) ?>&prix=<?= $animal['prix'] ?>" class="btn-panier">
                📥 AJOUTER À MON INVENTAIRE (PANIER)
            </a>
        </div>

        <hr style="border: 0; border-top: 1px dashed #8b5a2b; margin: 40px 0;">
        
        <p style="font-family: 'Special Elite'; font-size: 0.8em; opacity: 0.5;">
            Document certifié par l'Atelier des Chimères - 1894
        </p>
    </div>

</body>
</html>