<?php 
session_start(); 
require_once 'config.php';
require_once 'helpers.php';

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Pagination
$items_par_page = 12;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;

// Filtres
$filtre = isset($_GET['cat']) ? sanitize_text($_GET['cat']) : 'tous';
$recherche = isset($_GET['recherche']) ? sanitize_text($_GET['recherche']) : '';

try {
    // Compter le nombre total d'éléments
    if (!empty($recherche)) {
        $count_req = $db->prepare('SELECT COUNT(*) FROM creatures WHERE nom LIKE ?');
        $count_req->execute(["%$recherche%"]);
        $count_requete = $db->prepare('SELECT * FROM creatures WHERE nom LIKE ? ORDER BY id DESC LIMIT ? OFFSET ?');
    } elseif ($filtre != 'tous') {
        $count_req = $db->prepare('SELECT COUNT(*) FROM creatures WHERE categorie = ?');
        $count_req->execute([$filtre]);
        $count_requete = $db->prepare('SELECT * FROM creatures WHERE categorie = ? ORDER BY id DESC LIMIT ? OFFSET ?');
    } else {
        $count_req = $db->query('SELECT COUNT(*) FROM creatures');
        $count_requete = $db->prepare('SELECT * FROM creatures ORDER BY id DESC LIMIT ? OFFSET ?');
    }
    
    $total_count = $count_req->fetchColumn();
    $total_pages = ceil($total_count / $items_par_page);
    
    // Vérifier que la page n'est pas trop grande
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    }
    
    // Calculer l'offset
    $offset = ($page - 1) * $items_par_page;
    
    // Récupérer les créatures pour cette page
    if (!empty($recherche)) {
        $count_requete->execute(["%$recherche%", $items_par_page, $offset]);
    } elseif ($filtre != 'tous') {
        $count_requete->execute([$filtre, $items_par_page, $offset]);
    } else {
        $count_requete->execute([$items_par_page, $offset]);
    }
    $toutes_les_creatures = $count_requete->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération créatures: ' . $e->getMessage());
    $toutes_les_creatures = [];
    $total_pages = 0;
    $page = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Atelier des Chimères</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #1a110a; color: #d4af37; text-align: center; font-family: 'Georgia', serif; margin: 0; cursor: var(--curseur-cle), auto !important; }
        .engrenage { position: fixed; color: rgba(139, 90, 43, 0.1); z-index: -1; animation: rotation 40s linear infinite; pointer-events: none; }
        .engrenage-1 { top: -100px; left: -100px; font-size: 400px; }
        .engrenage-2 { bottom: -120px; right: -120px; font-size: 500px; animation-direction: reverse; }
        @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .enseigne { background-image: url('images/enseigne.jpg'); background-size: cover; border: 4px solid #8b5a2b; border-radius: 15px; padding: 60px 20px; max-width: 80%; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .nav-container { margin-bottom: 30px; display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .btn-panier-nav { background: #8b5a2b; color: #ffd700; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 1px solid #ffd700; }
        .grille-creatures { display: flex; justify-content: center; gap: 25px; flex-wrap: wrap; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .carte-animal, .carte-speciale { width: 30%; min-width: 300px; background-color: rgba(43, 24, 16, 0.95); padding: 20px; border-radius: 15px; border: 1px solid #8b5a2b; transition: 0.4s; }
        .image-container { width: 100%; height: 300px; overflow: hidden; border: 2px solid #d4af37; border-radius: 10px; background: #000; }
        .image-chimere { width: 100%; height: 100%; object-fit: cover; }
        .prix-tag { font-size: 24px; font-weight: bold; color: #ffd700; margin: 10px 0; }
        .btn-action { padding: 8px 18px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>

<!-- Accès Admin Discret -->
<div style="position: fixed; top: 10px; right: 10px; z-index: 100;">
    <?php if(is_admin_connected()): ?>
        <a href="logout.php" title="Mode admin : Cliquer pour se déconnecter" style="display: inline-block; font-size: 1.5em; text-decoration: none; opacity: 0.6; transition: 0.3s; cursor: var(--curseur-cle), pointer;">🔓</a>
    <?php else: ?>
        <a href="login.php" title="Accès administrateur" style="display: inline-block; font-size: 1.5em; text-decoration: none; opacity: 0.4; transition: 0.3s; hover: opacity 0.8;">🔐</a>
    <?php endif; ?>
</div>

<div class="engrenage engrenage-1">⚙️</div>
<div class="engrenage engrenage-2">⚙️</div>

<div class="enseigne">
    <h1>L'ATELIER DES CHIMÈRES</h1>
    <p>La ménagerie mécanique...</p>
</div>

<div class="nav-container">
    <a href="panier.php" class="btn-panier-nav">🛒 MON INVENTAIRE (<?= count($_SESSION['panier']) ?>)</a>
    <div class="nav-filtres">
        <a href="index.php?cat=tous" style="color:#ffd700; margin:0 10px;">Tous</a>
        <a href="index.php?cat=Explorateur" style="color:#ffd700; margin:0 10px;">Explorateurs</a>
        <a href="index.php?cat=Mécanicien" style="color:#ffd700; margin:0 10px;">Mécaniciens</a>
        <a href="index.php?cat=Colosse" style="color:#ffd700; margin:0 10px;">Colosses</a>
    </div>
    <?php if(is_admin_connected()): ?>
        <div style="margin-top: 10px;">
            <a href="admin.php" style="color: #8b5a2b; border: 1px dashed #8b5a2b; padding: 5px 15px; text-decoration:none;">⚒️ Forger</a>
            <a href="commandes.php" style="color: #f4e4bc; margin-left:15px; text-decoration:none;">📜 Courrier</a>
        </div>
    <?php endif; ?>
</div>

<div class="grille-creatures">
    <div class="carte-speciale">
        <a href="contact.php" style="text-decoration:none;">
            <div class="image-container" style="display:flex; align-items:center; justify-content:center;"><span style="font-size: 80px;">✨</span></div>
            <h2>Chimère sur Mesure</h2>
            <div class="prix-tag">Sur Devis 🟡</div>
            <span class="btn-action" style="background: #ffd700; color: #1a110a;">✍️ Envoyer mon idée</span>
        </a>
    </div>

    <?php foreach ($toutes_les_creatures as $animal): ?>
        <div class="carte-animal">
            <div class="image-container">
                <img src="<?= htmlspecialchars($animal['image_path']) ?>" class="image-chimere">
            </div>
            <h2><?= htmlspecialchars(html_entity_decode($animal['nom'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></h2>
            <p style="color: #f4e4bc; height: 60px; overflow: hidden;">
                <?= htmlspecialchars(substr($animal['description'], 0, 100)) ?>...
            </p>
            <div class="prix-tag"><?= number_format($animal['prix'], 0, '.', ' ') ?> 🟡</div>
            <div style="margin-top:20px; display: flex; justify-content: center; gap: 10px;">
                <a href="details.php?id=<?= $animal['id'] ?>" class="btn-action" style="background: #8b5a2b; color: #f4e4bc;">🛠️ Détails</a>
                <?php if(is_admin_connected()): ?>
                    <a href="edit.php?id=<?= $animal['id'] ?>" class="btn-action" style="background: #5d3a1a; color: #ffd700; border: 1px solid #ffd700;">⚙️</a>
                    <form method="POST" action="delete.php" style="display:inline;">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="id" value="<?= $animal['id'] ?>">
                        <button type="submit" class="btn-action" style="background: #cc3333; color: #f4e4bc; border: 1px solid #ff6666;" onclick="return confirm('⚠️ Supprimer cette chimère ?')">🗑️</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 2px solid #8b5a2b; width: 100%;">
        <p style="color: #d4af37; margin-bottom: 20px;">
            Page <?= $page ?> sur <?= $total_pages ?> (<?= $total_count ?> créature<?= $total_count > 1 ? 's' : '' ?> total)
        </p>
        
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
            <?php 
            // Bouton Précédent
            if ($page > 1): ?>
                <a href="index.php?page=<?= $page - 1 ?><?= $filtre !== 'tous' ? '&cat=' . urlencode($filtre) : '' ?><?= !empty($recherche) ? '&recherche=' . urlencode($recherche) : '' ?>" 
                   style="background: #8b5a2b; color: #f4e4bc; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                    ← Précédent
                </a>
            <?php endif; ?>
            
            <?php 
            // Numéros de pages (max 5 pages visibles)
            $max_pages_display = 5;
            $page_start = max(1, $page - 2);
            $page_end = min($total_pages, $page_start + $max_pages_display - 1);
            $page_start = max(1, $page_end - $max_pages_display + 1);
            
            if ($page_start > 1): ?>
                <a href="index.php?page=1<?= $filtre !== 'tous' ? '&cat=' . urlencode($filtre) : '' ?><?= !empty($recherche) ? '&recherche=' . urlencode($recherche) : '' ?>" 
                   style="background: #5d3a1a; color: #f4e4bc; padding: 8px 12px; border-radius: 5px; text-decoration: none;">1</a>
                <?php if ($page_start > 2): ?>
                    <span style="color: #d4af37; padding: 8px 5px;">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $page_start; $i <= $page_end; $i++): ?>
                <?php if ($i === $page): ?>
                    <span style="background: #ffd700; color: #1a110a; padding: 8px 12px; border-radius: 5px; font-weight: bold;">
                        <?= $i ?>
                    </span>
                <?php else: ?>
                    <a href="index.php?page=<?= $i ?><?= $filtre !== 'tous' ? '&cat=' . urlencode($filtre) : '' ?><?= !empty($recherche) ? '&recherche=' . urlencode($recherche) : '' ?>" 
                       style="background: #5d3a1a; color: #f4e4bc; padding: 8px 12px; border-radius: 5px; text-decoration: none;">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page_end < $total_pages): ?>
                <?php if ($page_end < $total_pages - 1): ?>
                    <span style="color: #d4af37; padding: 8px 5px;">...</span>
                <?php endif; ?>
                <a href="index.php?page=<?= $total_pages ?><?= $filtre !== 'tous' ? '&cat=' . urlencode($filtre) : '' ?><?= !empty($recherche) ? '&recherche=' . urlencode($recherche) : '' ?>" 
                   style="background: #5d3a1a; color: #f4e4bc; padding: 8px 12px; border-radius: 5px; text-decoration: none;"><?= $total_pages ?></a>
            <?php endif; ?>
            
            <?php 
            // Bouton Suivant
            if ($page < $total_pages): ?>
                <a href="index.php?page=<?= $page + 1 ?><?= $filtre !== 'tous' ? '&cat=' . urlencode($filtre) : '' ?><?= !empty($recherche) ? '&recherche=' . urlencode($recherche) : '' ?>" 
                   style="background: #8b5a2b; color: #f4e4bc; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: bold;">
                    Suivant →
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

</body>
</html>