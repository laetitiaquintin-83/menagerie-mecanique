<?php 
session_start();
require_once 'config.php';
require_once 'helpers.php';

require_admin_connection();

// Traiter les changements de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_statut'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $id = (int)$_POST['id'];
        $nouveau_statut = $_POST['nouveau_statut'] ?? '';
        $type = $_POST['type'] ?? 'boutique';
        
        if (update_commande_statut($id, $nouveau_statut, $type)) {
            set_flash_message('Statut mis à jour ✅', 'success');
        }
    }
    header('Location: commandes.php');
    exit();
}

try {
    $req_speciales = $db->query("SELECT * FROM commandes_speciales ORDER BY date_demande DESC");
    $speciales = $req_speciales->fetchAll(PDO::FETCH_ASSOC);

    $req_boutique = $db->query("SELECT * FROM commandes ORDER BY date_commande DESC");
    $boutique = $req_boutique->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur BD: ' . $e->getMessage());
    $speciales = $boutique = [];
}

$flash = show_flash_and_clear();
$statuts_options = get_statuts_options();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre de l'Atelier</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #1a110a; color: #d4af37; font-family: 'Georgia', serif; padding: 30px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .tabs { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
        .tab-btn { background: #2b1810; color: #8b5a2b; border: 2px solid #8b5a2b; padding: 10px 20px; cursor: pointer; font-weight: bold; }
        .tab-btn.active { background: #8b5a2b; color: #f4e4bc; }
        .section-commande { display: none; }
        .section-commande.active { display: block; }
        .lettre { background: #f4e4bc; color: #2b1810; padding: 25px; margin-bottom: 20px; border: 2px solid #8b5a2b; }
        .statut-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-weight: bold; font-size: 0.9em; }
        .table-boutique { width: 100%; border-collapse: collapse; background: rgba(43, 24, 16, 0.8); }
        .table-boutique th, .table-boutique td { padding: 15px; border: 1px solid #8b5a2b; text-align: left; }
        .table-boutique th { background: #5d3a1a; color: #ffd700; }
        .btn-action { display: inline-block; padding: 8px 15px; margin-top: 10px; border: none; border-radius: 3px; cursor: pointer; font-weight: bold; text-decoration: none; }
        .btn-archiver { background: #8b5a2b; color: #f4e4bc; }
        .btn-archiver:hover { background: #5d3a1a; }
        .actions-row { margin-top: 15px; }
        .statut-select { padding: 8px; background: #2b1810; color: #d4af37; border: 1px solid #8b5a2b; }
        .flash { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .flash.success { background: #90ee90; color: #1a110a; }
        .flash.error { background: #ff6b6b; color: white; }
    </style>
</head>
<body>

<div class="container">
    <?php if (!empty($flash['message'])): ?>
        <div class="flash <?= $flash['type'] ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
    
    <a href="index.php" style="color: #d4af37; text-decoration: none;">← Retour</a>
    <h1 style="text-align: center;">📜 Registre des Commandes</h1>

    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('tab-speciales', this)">📫 Devis (<?= count($speciales) ?>)</button>
        <button class="tab-btn" onclick="showTab('tab-boutique', this)">🛒 Boutique (<?= count($boutique) ?>)</button>
    </div>

    <div id="tab-speciales" class="section-commande active">
        <?php if (empty($speciales)): ?>
            <p style="text-align: center; color: #8b5a2b;">Aucun devis actuellement</p>
        <?php endif; ?>
        <?php foreach($speciales as $s): 
            $statut_display = get_statut_display($s['statut'] ?? 'en_attente');
        ?>
            <div class="lettre">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div>
                        <small>Reçu le : <?= date('d/m/Y', strtotime($s['date_demande'])) ?></small>
                        <h3>Client : <?= htmlspecialchars($s['nom_client']) ?></h3>
                        <p><strong>Email :</strong> <?= htmlspecialchars($s['email_client']) ?></p>
                        <p><strong>Projet :</strong> <?= htmlspecialchars($s['type_chimere']) ?></p>
                        <p><em><?= nl2br(htmlspecialchars($s['description_projet'])) ?></em></p>
                        <p>💰 Budget : <?= $s['budget_estime'] ?> 🟡</p>
                    </div>
                    <div style="text-align: right;">
                        <span class="statut-badge" style="background: <?= $statut_display['color'] ?>; color: #1a110a;">
                            <?= $statut_display['label'] ?>
                        </span>
                    </div>
                </div>
                
                <div class="actions-row">
                    <form method="POST" style="display: inline;">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="type" value="devis">
                        <select name="nouveau_statut" class="statut-select" onchange="this.form.submit()">
                            <option value="">Changer le statut...</option>
                            <?php foreach ($statuts_options as $key => $opt): ?>
                                <option value="<?= $key ?>" <?= ($s['statut'] ?? '') === $key ? 'selected' : '' ?>>
                                    <?= $opt['label'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="change_statut" value="1">
                    </form>
                    
                    <form method="POST" action="delete_commande.php" style="display:inline;">
                        <?php csrf_input(); ?>
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn-action btn-archiver" onclick="return confirm('Archiver ce devis ?')">🗂️ Archiver</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div id="tab-boutique" class="section-commande">
        <table class="table-boutique">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Client</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($boutique as $b): 
                    $statut_display = get_statut_display($b['statut'] ?? 'en_attente');
                ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($b['date_commande'])) ?></td>
                        <td style="color: #ffd700;"><?= htmlspecialchars($b['articles']) ?></td>
                        <td><?= number_format($b['total'], 0) ?> 🟡</td>
                        <td><?= htmlspecialchars($b['nom_client']) ?></td>
                        <td>
                            <span class="statut-badge" style="background: <?= $statut_display['color'] ?>; color: #1a110a;">
                                <?= $statut_display['label'] ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 10px;">
                                <form method="POST" style="display: inline;">
                                    <?php csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="type" value="boutique">
                                    <select name="nouveau_statut" class="statut-select" style="font-size: 0.85em; padding: 5px;" onchange="this.form.submit()">
                                        <option value="">-</option>
                                        <?php foreach ($statuts_options as $key => $opt): ?>
                                            <option value="<?= $key ?>" <?= ($b['statut'] ?? '') === $key ? 'selected' : '' ?>>
                                                <?= $opt['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="change_statut" value="1">
                                </form>
                                
                                <form method="POST" action="delete_commande.php" style="display:inline;">
                                    <?php csrf_input(); ?>
                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                    <button type="submit" class="btn-action btn-archiver" onclick="return confirm('Archiver cette commande ?')" style="padding: 5px 10px; font-size: 0.85em;">Archiver</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showTab(tabId, btn) {
    document.querySelectorAll('.section-commande').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
}
</script>

</body>
</html>