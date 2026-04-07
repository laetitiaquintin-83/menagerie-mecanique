<?php 
session_start(); 
include 'connexion.php';

// --- LOGIQUE DE RECHERCHE ET FILTRE ---
$filtre = isset($_GET['cat']) ? $_GET['cat'] : 'tous';
$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';

if (!empty($recherche)) {
    $requete = $db->prepare('SELECT * FROM creatures WHERE nom LIKE ? ORDER BY id DESC');
    $requete->execute(["%$recherche%"]);
} elseif ($filtre != 'tous') {
    $requete = $db->prepare('SELECT * FROM creatures WHERE categorie = ? ORDER BY id DESC');
    $requete->execute([$filtre]);
} else {
    $requete = $db->query('SELECT * FROM creatures ORDER BY id DESC');
}
$toutes_les_creatures = $requete->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Atelier des Chimères</title>
    <style>
        /* --- STYLE GÉNÉRAL --- */
        body { 
            background-color: #1a110a; 
            color: #d4af37; 
            text-align: center; 
            font-family: 'Georgia', serif; 
            margin: 0; 
            padding: 0; 
            overflow-x: hidden;
        }
        
        /* --- LE SÉSAME (LOGIN/LOGOUT) --- */
        .auth-zone {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 0.8em;
            z-index: 1000;
        }
        .auth-zone a {
            color: #8b5a2b;
            text-decoration: none;
            border: 1px solid #8b5a2b;
            padding: 5px 12px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .auth-zone a:hover {
            color: #ffd700;
            border-color: #ffd700;
            background: rgba(139, 90, 43, 0.1);
        }
        .logout-link { color: #ff4444 !important; border-color: #ff4444 !important; }

        /* --- BANDEAU ADMIN / COURRIER --- */
        .admin-bar {
            background: linear-gradient(to right, #8b5a2b, #2b1810); 
            padding: 15px; 
            text-align: center; 
            border-bottom: 2px solid #ffd700; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            margin-bottom: 20px;
        }

        /* --- ANIMATION DES ENGRENAGES DE FOND --- */
        .engrenage {
            position: fixed;
            color: rgba(139, 90, 43, 0.15);
            z-index: -1;
            user-select: none;
            animation: rotation 30s linear infinite;
        }
        .engrenage-1 { top: -80px; left: -80px; font-size: 300px; }
        .engrenage-2 { bottom: -100px; right: -100px; font-size: 400px; animation-direction: reverse; }

        @keyframes rotation {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* --- L'ENSEIGNE --- */
        .enseigne {
            background-image: url('images/enseigne.jpg'); 
            background-size: cover; 
            border: 4px solid #8b5a2b; 
            border-radius: 15px; 
            padding: 60px 20px; 
            max-width: 80%; 
            margin: 20px auto;
            position: relative;
        }

        .enseigne h1 { 
            color: #ffd700 !important; 
            font-size: 46px; 
            margin: 0; 
            text-shadow: 3px 3px 6px rgba(0,0,0,0.9), -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;
        }

        .enseigne p { 
            color: #ffffff !important; 
            font-size: 20px; 
            font-weight: bold;
            text-shadow: 2px 2px 4px #000;
        }

        /* --- BARRE DE RECHERCHE --- */
        .search-bar { margin-bottom: 20px; }
        .search-bar input {
            background: rgba(43, 24, 16, 0.8);
            border: 1px solid #8b5a2b;
            color: #ffd700;
            padding: 10px;
            border-radius: 20px;
            width: 250px;
            outline: none;
        }
        .search-bar button {
            background: #8b5a2b;
            color: #1a110a;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            margin-left: -40px;
        }

        /* --- NAVIGATION --- */
        .nav-filtres a {
            color: #ffd700; 
            margin: 0 10px; 
            text-decoration: none; 
            font-weight: bold;
        }
        .nav-filtres a:hover { border-bottom: 2px solid #ffd700; }

        /* --- GRILLE ET CARTES --- */
        .grille-creatures {
            display: flex; 
            justify-content: center; 
            gap: 25px; 
            flex-wrap: wrap; 
            max-width: 1200px; 
            margin: 0 auto;
            padding: 20px;
        }

        .carte-animal {
            width: 30%; 
            min-width: 300px; 
            background-color: rgba(43, 24, 16, 0.7); 
            padding: 20px; 
            border-radius: 15px; 
            border: 1px solid #8b5a2b; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between;
            transition: 0.3s;
            backdrop-filter: blur(3px);
        }

        .image-chimere { 
            width: 100% !important; 
            height: 300px !important; 
            object-fit: cover !important; 
            border: 3px solid #d4af37; 
            border-radius: 10px; 
            transition: transform 0.5s ease;
        }

        .carte-animal:hover { transform: translateY(-5px); border-color: #ffd700; }
        .carte-animal:hover .image-chimere { transform: rotate(2deg) scale(1.05); }

        /* --- BOUTONS --- */
        .btn-adopter { 
            background-color: #c08b5c; color: #2b1810; padding: 10px 25px; 
            font-weight: bold; border-radius: 5px; display: inline-block; 
            transition: 0.3s; text-decoration: none; margin-top: 10px;
        }
        .btn-adopter:hover { background-color: #8b5a2b !important; color: #ffd700 !important; transform: scale(1.1); }

        .btn-forge {
            background: #2b1810; color: #8b5a2b; padding: 12px 25px; 
            border: 2px dashed #8b5a2b; text-decoration: none; 
            border-radius: 5px; font-weight: bold; display: inline-block;
        }

        /* --- RESPONSIVE --- */
        @media (max-width: 1024px) { .carte-animal { width: 45%; } }
        @media (max-width: 768px) {
            .engrenage { display: none; }
            .enseigne h1 { font-size: 32px !important; }
            .carte-animal { width: 95% !important; }
        }
    </style>
</head>
<body>

<div class="auth-zone">
    <?php if(isset($_SESSION['admin'])): ?>
        <a href="logout.php" class="logout-link">🗝️ Déconnexion</a>
    <?php else: ?>
        <a href="login.php">🗝️ Accès</a>
    <?php endif; ?>
</div>

<?php if(isset($_SESSION['admin'])): ?>
    <div class="admin-bar">
        <a href="commandes.php" style="color: #f4e4bc; text-decoration: none; font-weight: bold; letter-spacing: 1px;">
            📜 CONSULTER LE COURRIER DES COMMANDES
        </a>
    </div>
<?php endif; ?>

<div class="engrenage engrenage-1">⚙️</div>
<div class="engrenage engrenage-2">⚙️</div>

<div class="enseigne">
    <h1>L'ATELIER DES CHIMÈRES</h1>
    <p>La ménagerie mécanique...</p>
</div>

<?php if(isset($_SESSION['nom_admin'])): ?>
    <div style="margin-bottom: 30px;">
        <p style="color: #ffd700; font-style: italic;">
            ⚙️ Bienvenue, Maîtresse <strong><?php echo $_SESSION['nom_admin']; ?></strong>.
        </p>
    </div>
<?php endif; ?>

<form method="GET" action="index.php" class="search-bar">
    <input type="text" name="recherche" placeholder="Nom de la créature..." value="<?php echo htmlspecialchars($recherche); ?>">
    <button type="submit">🔍</button>
</form>

<div class="nav-filtres" style="margin-bottom: 30px;">
    <a href="index.php?cat=tous">Tous</a>
    <a href="index.php?cat=Explorateur">Explorateurs</a>
    <a href="index.php?cat=Mécanicien">Mécaniciens</a>
    <a href="index.php?cat=Colosse">Colosses</a>
    
    <?php if(isset($_SESSION['admin'])): ?>
        <div style="margin-top: 30px;">
            <a href="admin.php" class="btn-forge">⚒️ Accéder à la Forge</a>
        </div>
    <?php endif; ?>
</div>

<div style="margin: 20px 0;">
    <a href="contact.php" style="
        background: #d4af37; 
        color: #1a110a; 
        padding: 15px 25px; 
        text-decoration: none; 
        font-weight: bold; 
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    ">
        ✉️ PASSER UNE COMMANDE SPÉCIALE
    </a>
</div>

<div class="grille-creatures">
    <?php if(count($toutes_les_creatures) > 0): ?>
        <?php foreach ($toutes_les_creatures as $animal): ?>
            <div class="carte-animal">
                <a href="details.php?id=<?php echo $animal['id']; ?>" style="text-decoration: none; color: inherit;">
                    <div>
                        <img src="<?php echo $animal['image_path']; ?>" class="image-chimere">
                        <h2 style="color: #c08b5c; margin-top: 15px;"><?php echo $animal['nom']; ?></h2>
                        <span style="font-size: 11px; background: #8b5a2b; color: #1a110a; padding: 3px 10px; border-radius: 10px;"><?php echo $animal['categorie']; ?></span>
                        <p style="font-style: italic; color: #d4af37; margin-top: 15px; font-size: 0.9em;">
                            <?php 
                                $desc = $animal['description'];
                                echo (strlen($desc) > 100) ? substr($desc, 0, 100) . '...' : $desc; 
                            ?>
                        </p>
                    </div>
                </a>

                <div style="text-align: center; margin-top: 20px;">
                    <p style="font-size: 22px; font-weight: bold; color: #ffd700; border-top: 1px dashed #8b5a2b; padding-top: 10px; margin-bottom: 10px;">
                        <?php echo $animal['prix']; ?> 🟡
                    </p>
                    
                    <a href="details.php?id=<?php echo $animal['id']; ?>" class="btn-adopter">Détails techniques</a>

                    <?php if(isset($_SESSION['admin'])): ?>
                        <div style="margin-top: 15px; border-top: 1px solid rgba(139, 90, 43, 0.2); padding-top: 10px;">
                            <a href="admin.php?modifier=<?php echo $animal['id']; ?>" style="color: #8b5a2b; font-size: 12px; text-decoration: none; display: block; margin-bottom: 5px;">⚙️ Ajuster</a>
                            <a href="delete.php?id=<?php echo $animal['id']; ?>" onclick="confirmerDemontage(event, this.href);" style="color: #ff4444; font-size: 11px; text-decoration: none;">🗑️ Démonter</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color: #ffd700; font-style: italic;">Aucune pièce mécanique ne correspond à cette recherche...</p>
    <?php endif; ?>
</div>

<div id="customAlert" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#2b1810; border:3px solid #8b5a2b; padding:30px; border-radius:15px; text-align:center; max-width:400px; box-shadow: 0 0 20px #ffd700;">
        <h3 style="color:#ffd700; margin-top:0;">⚠️ DÉMANTÈLEMENT ?</h3>
        <p style="color:#d4af37;">Voulez-vous vraiment démonter cette chimère ? Les pièces seront éparpillées.</p>
        <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
            <button id="btnAnnuler" style="background:#444; color:white; border:none; padding:10px 20px; cursor:pointer; border-radius:5px;">Annuler</button>
            <a id="linkConfirmer" href="#" style="background:#610b0b; color:white; padding:10px 20px; border-radius:5px; text-decoration:none; font-weight:bold;">DÉMONTER</a>
        </div>
    </div>
</div>

<script>
function confirmerDemontage(event, url) {
    event.preventDefault();
    const modal = document.getElementById('customAlert');
    modal.style.display = 'flex';
    document.getElementById('btnAnnuler').onclick = () => modal.style.display = 'none';
    document.getElementById('linkConfirmer').href = url;
}
</script>

</body>
</html>