<?php
/**
 * ======================================================================
 * dashboard.php - TABLEAU DE BORD ADMINISTRATEUR
 * ======================================================================
 * Page avec statistiques complètes de l'atelier
 */

session_start();
require_once 'config.php';
require_once 'helpers.php';

// Vérifier que l'utilisateur est admin
require_admin_connection();

// Récupérer les statistiques
try {
    // Total créatures
    $stats_creatures = $db->query("SELECT 
        COUNT(*) as total,
        COUNT(DISTINCT categorie) as categories,
        AVG(prix) as prix_moyen,
        MIN(prix) as prix_min,
        MAX(prix) as prix_max,
        SUM(CASE WHEN stock > 0 THEN 1 ELSE 0 END) as en_stock
    FROM creatures")->fetch(PDO::FETCH_ASSOC);
    
    // Total commandes
    $stats_commandes = $db->query("SELECT 
        COUNT(*) as total,
        COALESCE(SUM(total), 0) as total_ventes,
        COUNT(DISTINCT statut) as statuts_differents
    FROM commandes")->fetch(PDO::FETCH_ASSOC);
    
    // Total devis
    $stats_devis = $db->query("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN statut = 'en_attente' THEN 1 END) as en_attente
    FROM commandes_speciales")->fetch(PDO::FETCH_ASSOC);
    
    // Statuts des commandes
    $statuts_distrib = $db->query("SELECT 
        statut, 
        COUNT(*) as count 
    FROM commandes 
    GROUP BY statut 
    ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Créatures les plus vendues (estimé par présence dans commandes)
    $top_creatures = $db->query("SELECT 
        c.nom, 
        c.categorie,
        c.prix,
        COUNT(CASE WHEN co.articles LIKE CONCAT('%', c.nom, '%') THEN 1 END) as nb_ventes
    FROM creatures c
    LEFT JOIN commandes co ON co.articles LIKE CONCAT('%', c.nom, '%')
    GROUP BY c.id
    ORDER BY nb_ventes DESC
    LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Créatures par catégorie
    $creatures_par_cat = $db->query("SELECT 
        categorie, 
        COUNT(*) as count 
    FROM creatures 
    GROUP BY categorie 
    ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    // Commandes récentes
    $recent_orders = $db->query("SELECT * FROM commandes ORDER BY date_commande DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    // Devis récents
    $recent_devis = $db->query("SELECT * FROM commandes_speciales ORDER BY date_demande DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log('Erreur dashboard: ' . $e->getMessage());
    die('Erreur lors du chargement du dashboard');
}

$flash = show_flash_and_clear();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - L'Atelier des Chimères</title>
    <link rel="stylesheet" href="style.css">
    <script src="toast.js"></script>
    <style>
        body { background-color: #1a110a; color: #d4af37; font-family: 'Georgia', serif; padding: 20px; }
        .dashboard { max-width: 1400px; margin: 0 auto; }
        .dashboard-header { text-align: center; margin-bottom: 40px; }
        .dashboard-header h1 { color: #ffd700; margin: 0; font-size: 2.5em; }
        .dashboard-header p { opacity: 0.8; margin: 10px 0 20px 0; }
        .btn-back { display: inline-block; background: #5d3a1a; color: #d4af37; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: rgba(43, 24, 16, 0.95); border: 2px solid #8b5a2b; border-radius: 10px; padding: 20px; text-align: center; }
        .stat-card h3 { color: #ffd700; margin: 0 0 10px 0; font-size: 0.9em; text-transform: uppercase; }
        .stat-card .value { font-size: 2.5em; font-weight: bold; color: #ff9800; }
        .stat-card .sub { font-size: 0.85em; color: #8b5a2b; margin-top: 10px; }
        
        .section { background: rgba(43, 24, 16, 0.95); border: 1px solid #8b5a2b; border-radius: 10px; padding: 30px; margin-bottom: 30px; }
        .section h2 { color: #ffd700; border-bottom: 2px solid #8b5a2b; padding-bottom: 15px; margin-top: 0; }
        
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        
        @media (max-width: 1024px) {
            .two-col { grid-template-columns: 1fr; }
        }
        
        .chart { background: rgba(0, 0, 0, 0.3); padding: 20px; border-radius: 8px; }
        .chart-bar { display: flex; align-items: center; margin-bottom: 15px; }
        .chart-bar-label { width: 120px; text-align: left; font-size: 0.9em; }
        .chart-bar-value { flex: 1; background: linear-gradient(90deg, #8b5a2b, #d4af37); height: 30px; border-radius: 5px; display: flex; align-items: center; padding-left: 10px; font-weight: bold; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #5d3a1a; color: #ffd700; padding: 12px; text-align: left; border: 1px solid #8b5a2b; }
        td { padding: 12px; border: 1px solid #3d261a; }
        tr:hover { background: rgba(139, 90, 43, 0.2); }
        
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 15px; font-size: 0.85em; font-weight: bold; }
        .status-pending { background: #ffd700; color: #1a110a; }
        .status-confirmed { background: #90ee90; color: #1a110a; }
        .status-shipped { background: #87ceeb; color: #1a110a; }
        .status-delivered { background: #32cd32; color: #1a110a; }
        .status-cancelled { background: #ff6b6b; color: white; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <a href="index.php" class="btn-back">← Retour</a>
            <h1>📊 Tableau de Bord</h1>
            <p>Statistiques complètes de L'Atelier des Chimères</p>
        </div>
        
        <?php if (!empty($flash['message'])): ?>
            <div style="background: <?= $flash['type'] === 'success' ? '#90ee90' : '#ff6b6b' ?>; color: <?= $flash['type'] === 'success' ? '#1a110a' : 'white' ?>; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistiques principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>🔧 Créatures</h3>
                <div class="value"><?= $stats_creatures['total'] ?? 0 ?></div>
                <div class="sub"><?= $stats_creatures['categories'] ?? 0 ?> catégories</div>
            </div>
            
            <div class="stat-card">
                <h3>🛒 Commandes</h3>
                <div class="value"><?= $stats_commandes['total'] ?? 0 ?></div>
                <div class="sub">💰 <?= number_format($stats_commandes['total_ventes'] ?? 0, 0) ?> 🟡</div>
            </div>
            
            <div class="stat-card">
                <h3>📫 Devis</h3>
                <div class="value"><?= $stats_devis['total'] ?? 0 ?></div>
                <div class="sub"><?= $stats_devis['en_attente'] ?? 0 ?> en attente</div>
            </div>
            
            <div class="stat-card">
                <h3>💳 Prix Moyen</h3>
                <div class="value"><?= number_format($stats_creatures['prix_moyen'] ?? 0, 0) ?></div>
                <div class="sub">
                    Min: <?= number_format($stats_creatures['prix_min'] ?? 0, 0) ?> 🟡<br>
                    Max: <?= number_format($stats_creatures['prix_max'] ?? 0, 0) ?> 🟡
                </div>
            </div>
        </div>
        
        <!-- Sections détaillées -->
        <div class="two-col">
            <!-- Statuts des commandes -->
            <div class="section">
                <h2>📊 Répartition Statuts</h2>
                <div class="chart">
                    <?php 
                    $total_orders = array_sum(array_column($statuts_distrib, 'count'));
                    foreach ($statuts_distrib as $status): 
                        $percentage = $total_orders > 0 ? ($status['count'] / $total_orders * 100) : 0;
                    ?>
                        <div class="chart-bar">
                            <div class="chart-bar-label"><?= ucfirst(str_replace('_', ' ', $status['statut'])) ?></div>
                            <div class="chart-bar-value" style="width: <?= $percentage ?>%; min-width: 50px;">
                                <?= (int)$percentage ?>%
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Créatures par catégorie -->
            <div class="section">
                <h2>📂 Créatures par Catégorie</h2>
                <div class="chart">
                    <?php foreach ($creatures_par_cat as $cat): 
                        $total_cat = array_sum(array_column($creatures_par_cat, 'count'));
                        $percentage = $total_cat > 0 ? ($cat['count'] / $total_cat * 100) : 0;
                    ?>
                        <div class="chart-bar">
                            <div class="chart-bar-label"><?= htmlspecialchars($cat['categorie']) ?></div>
                            <div class="chart-bar-value" style="width: <?= $percentage ?>%; min-width: 50px;">
                                <?= $cat['count'] ?> (<?= (int)$percentage ?>%)
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Top créatures -->
        <div class="section">
            <h2>🏆 CreatureS les Plus Vendues</h2>
            <table>
                <thead>
                    <tr>
                        <th>Créature</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Ventes Estimées</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_creatures as $creature): ?>
                        <tr>
                            <td><?= htmlspecialchars($creature['nom']) ?></td>
                            <td><?= htmlspecialchars($creature['categorie']) ?></td>
                            <td><?= number_format($creature['prix'], 0) ?> 🟡</td>
                            <td><strong><?= $creature['nb_ventes'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Commandes récentes -->
        <div class="two-col">
            <div class="section">
                <h2>🛒 Commandes Récentes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order): 
                            $statut_info = get_statut_display($order['statut'] ?? 'en_attente');
                        ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($order['date_commande'])) ?></td>
                                <td><?= htmlspecialchars(substr($order['nom_client'], 0, 20)) ?></td>
                                <td><?= number_format($order['total'], 0) ?> 🟡</td>
                                <td><span class="status-badge status-<?= str_replace('_', '-', $order['statut'] ?? 'pending') ?>"><?= $statut_info['label'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Devis récents -->
            <div class="section">
                <h2>📫 Devis Récents</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Budget</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_devis as $devis): 
                            $statut_info = get_statut_display($devis['statut'] ?? 'en_attente');
                        ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($devis['date_demande'])) ?></td>
                                <td><?= htmlspecialchars(substr($devis['nom_client'], 0, 20)) ?></td>
                                <td><?= $devis['budget_estime'] ?> 🟡</td>
                                <td><span class="status-badge status-<?= str_replace('_', '-', $devis['statut'] ?? 'pending') ?>"><?= $statut_info['label'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <?php if (!empty($flash['message'])): ?>
    <script>
        window.addEventListener('DOMContentLoaded', function() {
            showToast('<?= addslashes($flash['message']) ?>', '<?= $flash['type'] ?? 'info' ?>');
        });
    </script>
    <?php endif; ?>
    
</body>
</html>
