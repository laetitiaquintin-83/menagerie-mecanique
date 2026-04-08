<?php 
session_start(); 
require_once 'config.php';
require_once 'helpers.php';

// Initialiser le panier s'il n'existe pas pour éviter les erreurs de compte
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// --- LOGIQUE DE RECHERCHE ET FILTRE ---
$filtre = isset($_GET['cat']) ? sanitize_text($_GET['cat']) : 'tous';
$recherche = isset($_GET['recherche']) ? sanitize_text($_GET['recherche']) : '';

try {
    if (!empty($recherche)) {
        $requete = $db->prepare('SELECT * FROM creatures WHERE nom LIKE ? ORDER BY id DESC');
        $requete->execute(["%$recherche%"]);
    } elseif ($filtre != 'tous') {
        $requete = $db->prepare('SELECT * FROM creatures WHERE categorie = ? ORDER BY id DESC');
        $requete->execute([$filtre]);
    } else {
        $requete = $db->query('SELECT * FROM creatures ORDER BY id DESC');
    }
    $toutes_les_creatures = $requete->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération créatures: ' . $e->getMessage());
    $toutes_les_creatures = [];
}

$flash = show_flash_and_clear();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Atelier des Chimères</title>
    <style>
        /* --- LE CURSEUR "CLÉ DE CUIVRE" --- */
        :root {
            --curseur-cle: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='32' height='32' viewBox='0 0 32 32'><path fill='%23b87333' stroke='%235d3a1a' stroke-width='1' d='M10 4C6.7 4 4 6.7 4 10c0 2.5 1.5 4.6 3.7 5.5L4 28l3 3 12.5-3.7c.9 2.2 3 3.7 5.5 3.7 3.3 0 6-2.7 6-6s-2.7-6-6-6c-1.5 0-2.8.5-3.8 1.4L15.5 14c2.2-.9 3.7-3 3.7-5.5 0-3.3-2.7-6-6-6zm0 3c1.7 0 3 1.3 3 3s-1.3 3-3 3-3-1.3-3-3 1.3-3 3-3z'/></svg>") 5 5;
        }

        body { 
            background-color: #1a110a; 
            color: #d4af37; 
            text-align: center; 
            font-family: 'Georgia', serif; 
            margin: 0; padding: 0; 
            overflow-x: hidden;
            cursor: var(--curseur-cle), auto !important;
            position: relative;
        }

        a, button, .carte-animal, .carte-speciale, #ascenseur, input, select {
            cursor: var(--curseur-cle), pointer !important;
        }
        
        /* --- LES ENGRENAGES --- */
        .engrenage { 
            position: fixed; 
            color: rgba(139, 90, 43, 0.1); 
            z-index: -1; 
            animation: rotation 40s linear infinite; 
            user-select: none; 
            pointer-events: none; 
        }
        .engrenage-1 { top: -100px; left: -100px; font-size: 400px; }
        .engrenage-2 { bottom: -120px; right: -120px; font-size: 500px; animation-direction: reverse; animation-duration: 60s; }
        .engrenage-3 { top: 40%; left: -50px; font-size: 200px; opacity: 0.05; }

        @keyframes rotation { 
            from { transform: rotate(0deg); } 
            to { transform: rotate(360deg); } 
        }

        /* --- ENSEIGNE --- */
        .enseigne { 
            background-image: url('images/enseigne.jpg'); 
            background-size: cover; 
            border: 4px solid #8b5a2b; 
            border-radius: 15px; 
            padding: 60px 20px; 
            max-width: 80%; 
            margin: 20px auto; 
            box-shadow: inset 0 0 50px rgba(0,0,0,0.8), 0 10px 30px rgba(0,0,0,0.5); 
            position: relative;
            z-index: 10;
        }
        .enseigne h1 { color: #ffd700 !important; font-size: 46px; text-shadow: 3px 3px 6px rgba(0,0,0,0.9); margin: 0; letter-spacing: 2px; }

        /* --- NAVIGATION & PANIER --- */
        .nav-container {
            margin-bottom: 30px;
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .btn-panier-nav {
            background: #8b5a2b;
            color: #ffd700;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            border: 1px solid #ffd700;
            transition: 0.3s;
        }
        .btn-panier-nav:hover { background: #ffd700; color: #1a110a; }

        /* --- GRILLE ET CARTES --- */
        .grille-creatures { 
            display: flex; justify-content: center; gap: 25px; flex-wrap: wrap; 
            max-width: 1200px; margin: 0 auto; padding: 20px; 
            position: relative; z-index: 5;
        }
        
        .carte-animal, .carte-speciale { 
            width: 30%; min-width: 300px; background-color: rgba(43, 24, 16, 0.95); 
            padding: 20px; border-radius: 15px; border: 1px solid #8b5a2b; 
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }

        .carte-speciale { border: 2px dashed #ffd700; background: linear-gradient(135deg, rgba(43, 24, 16, 0.95) 0%, rgba(80, 50, 30, 0.95) 100%); }

        .carte-animal:hover, .carte-speciale:hover { 
            border-color: #ffd700; transform: translateY(-10px); 
            box-shadow: 0 15px 35px rgba(212, 175, 55, 0.3);
        }

        .image-container { width: 100%; height: 300px; overflow: hidden; border: 2px solid #d4af37; border-radius: 10px; background: #000; }
        .image-chimere { width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .carte-animal:hover .image-chimere { transform: scale(1.1); }

        .carte-animal h2, .carte-speciale h2 { color: #ffd700; margin-top: 15px; font-size: 1.6em; }
        .description-text { font-style: normal; font-size: 0.95em; color: #f4e4bc; line-height: 1.4; margin: 15px 0; height: 65px; overflow: hidden; }
        .prix-tag { font-size: 24px; font-weight: bold; color: #ffd700; margin: 10px 0; }

        /* --- BOUTONS --- */
        .btn-action { transition: all 0.3s ease; display: inline-block; font-weight: bold; border: none; text-decoration: none; cursor: pointer; }
        .btn-action:hover { filter: brightness(1.3); transform: scale(1.05); }

        .nav-filtres a { color: #ffd700; margin: 0 10px; text-decoration: none; font-weight: bold; border-bottom: 1px solid transparent; transition: 0.3s; }
        .nav-filtres a:hover { border-bottom: 1px solid #ffd700; }

        #ascenseur { display: none; position: fixed; bottom: 20px; right: 30px; border: 2px solid #8b5a2b; background: #2b1810; color: #ffd700; padding: 15px; border-radius: 50%; z-index: 100; }

        @media (max-width: 768px) { .carte-animal, .carte-speciale { width: 95% !important; } }
    </style>
</head>
<body>

<div class="engrenage engrenage-1">⚙️</div>
<div class="engrenage engrenage-2">⚙️</div>
<div class="engrenage engrenage-3">⚙️</div>

<div class="auth-zone" style="position: absolute; top: 15px; right: 20px; z-index: 100;">
    <?php if(is_admin_connected()): ?>
        <a href="logout.php" style="color:#ff4444; border: 1px solid #ff4444; text-decoration:none; padding:5px 10px; border-radius:5px;">🗝️ Déconnexion</a>
    <?php else: ?>
        <a href="login.php" style="color:#8b5a2b; border: 1px solid #8b5a2b; text-decoration:none; padding:5px 10px; border-radius:5px;">🗝️ Accès</a>
    <?php endif; ?>
</div>

<div class="enseigne">
    <h1>L'ATELIER DES CHIMÈRES</h1>
    <p>La ménagerie mécanique...</p>
</div>

<div class="nav-container">
    <a href="panier.php" class="btn-panier-nav">
        🛒 MON INVENTAIRE (<?= count($_SESSION['panier']) ?>)
    </a>

    <div class="nav-filtres">
        <a href="index.php?cat=tous">Tous</a>
        <a href="index.php?cat=Explorateur">Explorateurs</a>
        <a href="index.php?cat=Mécanicien">Mécaniciens</a>
        <a href="index.php?cat=Colosse">Colosses</a>
    </div>

    <?php if(is_admin_connected()): ?>
        <div style="margin-top: 10px;">
            <a href="admin.php" style="color: #8b5a2b; border: 1px dashed #8b5a2b; padding: 5px 15px; text-decoration:none; font-weight:bold;">⚒️ Forger une pièce</a>
            <a href="commandes.php" style="color: #f4e4bc; text-decoration: none; font-weight: bold; margin-left:15px;">📜 Courrier</a>
        </div>
    <?php endif; ?>
</div>

<div class="grille-creatures">
    
    <div class="carte-speciale">
        <a href="contact.php" style="text-decoration:none;">
            <div class="image-container" style="display:flex; align-items:center; justify-content:center;">
                <span style="font-size: 80px;">✨</span>
            </div>
            <h2>Chimère sur Mesure</h2>
            <div class="description-text">
                Un projet fou ? Une créature issue de vos rêves les plus mécaniques ?
            </div>
            <div class="prix-tag">Sur Devis 🟡</div>
            <div style="margin-top:20px;">
                <span class="btn-action" style="background: #ffd700; color: #1a110a; padding: 8px 18px; border-radius: 5px;">✍️ Envoyer mon idée</span>
            </div>
        </a>
    </div>

    <?php if(count($toutes_les_creatures) > 0): ?>
        <?php foreach ($toutes_les_creatures as $animal): ?>
            <div class="carte-animal">
                <a href="details.php?id=<?= $animal['id'] ?>">
                    <div class="image-container">
                        <img src="<?= htmlspecialchars($animal['image_path']) ?>" class="image-chimere">
                    </div>
                </a>
                
                <h2><?= html_entity_decode(htmlspecialchars($animal['nom'])) ?></h2>
                
                <div class="description-text">
                    <?= html_entity_decode(htmlspecialchars((strlen($animal['description']) > 120) ? substr($animal['description'], 0, 120) . '...' : $animal['description'])) ?>
                </div>
                
                <div class="prix-tag"><?= htmlspecialchars($animal['prix']) ?> 🟡</div>

                <div style="margin-top:20px; display: flex; justify-content: center; gap: 10px;">
                    <a href="details.php?id=<?= $animal['id'] ?>" class="btn-action" style="background: #8b5a2b; color: #f4e4bc; padding: 8px 18px; text-decoration: none; border-radius: 5px;">🛠️ Voir Détails</a>

                    <?php if(is_admin_connected()): ?>
                        <a href="edit.php?id=<?= $animal['id'] ?>" class="btn-action" style="background: #5d3a1a; color: #ffd700; padding: 8px 18px; text-decoration: none; border-radius: 5px; border: 1px solid #ffd700;">⚙️</a>

                        <form method="POST" action="delete.php" onsubmit="return confirm('Démonter cette chimère ?');" style="display:inline;">
                            <?php csrf_input(); ?>
                            <input type="hidden" name="id" value="<?= $animal['id'] ?>">
                            <button type="submit" class="btn-action" style="background:none; border: 1px solid #ff4444; color:#ff4444; padding: 8px 18px; border-radius: 5px;">🗑️</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<button id="ascenseur" title="Retour en haut">▲</button>

<script>
    const btn = document.getElementById("ascenseur");
    window.onscroll = function() {
        if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
            btn.style.display = "block";
        } else {
            btn.style.display = "none";
        }
    };
    btn.onclick = function() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    };
</script>

</body>
</html>