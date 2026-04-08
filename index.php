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

// Filtres et tri
$filtre = isset($_GET['cat']) ? sanitize_text($_GET['cat']) : 'tous';
$recherche = isset($_GET['recherche']) ? sanitize_text($_GET['recherche']) : '';
$prix_min = isset($_GET['prix_min']) && is_numeric($_GET['prix_min']) ? floatval($_GET['prix_min']) : 0;
$prix_max = isset($_GET['prix_max']) && is_numeric($_GET['prix_max']) ? floatval($_GET['prix_max']) : null;
$tri = isset($_GET['tri']) ? sanitize_text($_GET['tri']) : 'recent'; // recent, nom, prix_asc, prix_desc

try {
    // Construire la requête de base
    $where_conditions = [];
    $params = [];
    
    if (!empty($recherche)) {
        $where_conditions[] = "nom LIKE ?";
        $params[] = "%$recherche%";
    }
    
    if ($filtre !== 'tous') {
        $where_conditions[] = "categorie = ?";
        $params[] = $filtre;
    }
    
    if ($prix_min > 0) {
        $where_conditions[] = "prix >= ?";
        $params[] = $prix_min;
    }
    
    if ($prix_max !== null && $prix_max > 0) {
        $where_conditions[] = "prix <= ?";
        $params[] = $prix_max;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Déterminer le tri
    $order_by = 'id DESC';
    switch ($tri) {
        case 'nom':
            $order_by = 'nom ASC';
            break;
        case 'prix_asc':
            $order_by = 'prix ASC';
            break;
        case 'prix_desc':
            $order_by = 'prix DESC';
            break;
        case 'recent':
        default:
            $order_by = 'id DESC';
    }
    
    // Compter le total
    $count_req = $db->prepare("SELECT COUNT(*) FROM creatures $where_clause");
    $count_req->execute($params);
    $total_count = $count_req->fetchColumn();
    $total_pages = ceil($total_count / $items_par_page);
    
    // Vérifier que la page n'est pas trop grande
    if ($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
    }
    
    // Calculer l'offset
    $offset = ($page - 1) * $items_par_page;
    
    // Récupérer les créatures
    $creatures_req = $db->prepare("SELECT * FROM creatures $where_clause ORDER BY $order_by LIMIT ? OFFSET ?");
    $creatures_req->execute(array_merge($params, [$items_par_page, $offset]));
    $toutes_les_creatures = $creatures_req->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur récupération créatures: ' . $e->getMessage());
    $toutes_les_creatures = [];
    $total_pages = 0;
    $page = 0;
}

// Récupérer les messages flash pour les convertir en toasts
$flash = show_flash_and_clear();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>L'Atelier des Chimères</title>
    <link rel="stylesheet" href="style.css">
    <script src="toast.js"></script>
    <style>
        body { background-color: #1a110a; color: #d4af37; text-align: center; font-family: 'Georgia', serif; margin: 0; }
        .engrenage { position: fixed; color: rgba(139, 90, 43, 0.1); z-index: -1; animation: rotation 40s linear infinite; pointer-events: none; }
        .engrenage-1 { top: -100px; left: -100px; font-size: 400px; }
        .engrenage-2 { bottom: -120px; right: -120px; font-size: 500px; animation-direction: reverse; }
        @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .enseigne { background-image: url('images/enseigne.jpg'); background-size: cover; border: 4px solid #8b5a2b; border-radius: 15px; padding: 60px 20px; max-width: 80%; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .nav-container { margin-bottom: 30px; display: flex; flex-direction: column; align-items: center; gap: 15px; }
        .btn-panier-nav { background: #8b5a2b; color: #ffd700; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 1px solid #ffd700; }
        .filtres-avances { background: rgba(43, 24, 16, 0.9); padding: 20px; border: 1px solid #8b5a2b; border-radius: 10px; max-width: 90%; margin: 15px auto; }
        .filtres-row { display: flex; flex-wrap: wrap; gap: 15px; justify-content: center; align-items: flex-end; }
        .filtre-group { display: flex; flex-direction: column; align-items: flex-start; }
        .filtre-group label { color: #f4e4bc; font-weight: bold; margin-bottom: 5px; font-size: 0.9em; }
        .filtre-group input, .filtre-group select { background: #1a110a; color: #d4af37; border: 1px solid #8b5a2b; padding: 8px; border-radius: 5px; }
        .filtre-group input::placeholder { color: #8b5a2b; }
        .btn-search { background: #8b5a2b; color: #f4e4bc; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-search:hover { background: #5d3a1a; }
        .btn-reset { background: #5d3a1a; color: #d4af37; border: none; padding: 8px 20px; border-radius: 5px; cursor: pointer; }
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
        <a href="logout.php" title="Mode admin : Cliquer pour se déconnecter" style="display: inline-block; font-size: 1.5em; text-decoration: none; opacity: 0.6; transition: 0.3s;">🔓</a>
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
    <a href="favorites.php" class="btn-panier-nav" style="background: #5d3a1a; border-color: #ff69b4;">❤️ MES FAVORIS (<?= count(get_user_favorites()) ?>)</a>
    
    <!-- Filtres Avancés --> 
    <form method="GET" class="filtres-avances">
        <div class="filtres-row">
            <div class="filtre-group" style="flex: 1; min-width: 150px;">
                <label>🔍 Nom</label>
                <input type="text" name="recherche" placeholder="Chercher..." value="<?= htmlspecialchars($recherche) ?>">
            </div>
            
            <div class="filtre-group" style="min-width: 120px;">
                <label>📂 Catégorie</label>
                <select name="cat">
                    <option value="tous" <?= $filtre === 'tous' ? 'selected' : '' ?>>Tous</option>
                    <option value="Explorateur" <?= $filtre === 'Explorateur' ? 'selected' : '' ?>>Explorateurs</option>
                    <option value="Mécanicien" <?= $filtre === 'Mécanicien' ? 'selected' : '' ?>>Mécaniciens</option>
                    <option value="Colosse" <?= $filtre === 'Colosse' ? 'selected' : '' ?>>Colosses</option>
                </select>
            </div>
            
            <div class="filtre-group" style="min-width: 80px;">
                <label>💰 Min</label>
                <input type="number" name="prix_min" placeholder="0" value="<?= $prix_min ?>" min="0">
            </div>
            
            <div class="filtre-group" style="min-width: 80px;">
                <label>💰 Max</label>
                <input type="number" name="prix_max" placeholder="Max" value="<?= $prix_max > 0 ? $prix_max : '' ?>" min="0">
            </div>
            
            <div class="filtre-group" style="min-width: 120px;">
                <label>🔄 Tri</label>
                <select name="tri">
                    <option value="recent" <?= $tri === 'recent' ? 'selected' : '' ?>>Plus récent</option>
                    <option value="nom" <?= $tri === 'nom' ? 'selected' : '' ?>>Nom (A-Z)</option>
                    <option value="prix_asc" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix ↑</option>
                    <option value="prix_desc" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix ↓</option>
                </select>
            </div>
            
            <button type="submit" class="btn-search">🔎 Chercher</button>
            <a href="index.php" class="btn-reset">Réinitialiser</a>
        </div>
    </form>
    
    <!-- Liens rapides catégories -->
    <div class="nav-filtres" style="margin-top: 10px;">
        <small style="color: #8b5a2b; margin-right: 10px;">Catégories rapides:</small>
        <a href="index.php?cat=Explorateur" style="color:#ffd700; margin:0 10px; text-decoration: none;">Explorateurs</a>
        <a href="index.php?cat=Mécanicien" style="color:#ffd700; margin:0 10px; text-decoration: none;">Mécaniciens</a>
        <a href="index.php?cat=Colosse" style="color:#ffd700; margin:0 10px; text-decoration: none;">Colosses</a>
    </div>
    
    <?php if(is_admin_connected()): ?>
        <div style="margin-top: 10px;">
            <a href="admin.php" style="color: #8b5a2b; border: 1px dashed #8b5a2b; padding: 5px 15px; text-decoration:none;">⚒️ Forger</a>
            <a href="commandes.php" style="color: #f4e4bc; margin-left:15px; text-decoration:none;">📜 Courrier</a>
            <a href="dashboard.php" style="color: #ff9800; margin-left:15px; text-decoration:none;">📊 Dashboard</a>
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
        <div class="carte-animal" style="position: relative;">
            <!-- Bouton cœur favori -->
            <button class="favorite-btn" data-creature-id="<?= $animal['id'] ?>" title="Ajouter/Retirer des favoris" style="
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                font-size: 1.8em;
                cursor: pointer;
                padding: 5px;
                opacity: 0.85;
                transition: all 0.3s;
                <?= is_favorite($animal['id']) ? 'color: #ff69b4;' : 'color: #d4af37;' ?>
                z-index: 10;
            ">
                <?= is_favorite($animal['id']) ? '❤️' : '🤍' ?>
            </button>
            
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

<?php if (!empty($flash['message'])): ?>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        showToast('<?= addslashes($flash['message']) ?>', '<?= $flash['type'] ?? 'info' ?>');
    });
</script>
<?php endif; ?>

<!-- Script pour gérer les favoris -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons favoris
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const creatureId = this.getAttribute('data-creature-id');
            const btn = this;
            
            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    creature_id: creatureId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Changer l'icon et couleur
                    btn.textContent = data.icon;
                    if (data.is_favorite) {
                        btn.style.color = '#ff69b4';
                        showToast('❤️ Ajouté aux favoris', 'success');
                    } else {
                        btn.style.color = '#d4af37';
                        showToast('Retiré des favoris', 'info');
                    }
                } else {
                    showToast('Erreur: ' + (data.error || 'Inconnue'), 'error');
                }
            })
            .catch(err => {
                showToast('Erreur réseau: ' + err.message, 'error');
            });
        });
    });
});
</script>

</body>
</html>