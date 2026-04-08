<?php 
session_start();
require_once 'config.php';
require_once 'helpers.php';

require_admin_connection();

try {
    $req_speciales = $db->query("SELECT * FROM commandes_speciales ORDER BY date_demande DESC");
    $speciales = $req_speciales->fetchAll(PDO::FETCH_ASSOC);

    $req_boutique = $db->query("SELECT * FROM commandes ORDER BY date_commande DESC");
    $boutique = $req_boutique->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur BD: ' . $e->getMessage());
    $speciales = $boutique = [];
}
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
        .container { max-width: 1000px; margin: 0 auto; }
        .tabs { display: flex; justify-content: center; gap: 10px; margin-bottom: 30px; }
        .tab-btn { background: #2b1810; color: #8b5a2b; border: 2px solid #8b5a2b; padding: 10px 20px; cursor: pointer; font-weight: bold; }
        .tab-btn.active { background: #8b5a2b; color: #f4e4bc; }
        .section-commande { display: none; }
        .section-commande.active { display: block; }
        .lettre { background: #f4e4bc; color: #2b1810; padding: 25px; margin-bottom: 20px; border: 2px solid #8b5a2b; }
        .table-boutique { width: 100%; border-collapse: collapse; background: rgba(43, 24, 16, 0.8); }
        .table-boutique th, .table-boutique td { padding: 15px; border: 1px solid #8b5a2b; text-align: left; }
        .table-boutique th { background: #5d3a1a; color: #ffd700; }
        .btn-action { display: inline-block; padding: 8px 15px; margin-top: 10px; border: none; border-radius: 3px; cursor: pointer; font-weight: bold; text-decoration: none; }
        .btn-archiver { background: #8b5a2b; color: #f4e4bc; }
        .btn-archiver:hover { background: #5d3a1a; }
        .actions-row { margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" style="color: #d4af37; text-decoration: none;">← Retour</a>
    <h1 style="text-align: center;">📜 Registre des Commandes</h1>

    <div class="tabs">
        <button class="tab-btn active" onclick="showTab('tab-speciales', this)">📫 Devis (<?= count($speciales) ?>)</button>
        <button class="tab-btn" onclick="showTab('tab-boutique', this)">🛒 Boutique (<?= count($boutique) ?>)</button>
    </div>

    <div id="tab-speciales" class="section-commande active">
        <?php foreach($speciales as $s): ?>
            <div class="lettre">
                <small>Reçu le : <?= date('d/m/Y', strtotime($s['date_demande'])) ?></small>
                <h3>Client : <?= htmlspecialchars($s['nom_client']) ?></h3>
                <p><strong>Email :</strong> <?= htmlspecialchars($s['email_client']) ?></p>
                <p><strong>Projet :</strong> <?= htmlspecialchars($s['type_chimere']) ?></p>
                <p><em><?= nl2br(htmlspecialchars($s['description_projet'])) ?></em></p>
                <p>💰 Budget : <?= $s['budget_estime'] ?> 🟡</p>
                <div class="actions-row">
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($boutique as $b): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($b['date_commande'])) ?></td>
                        <td style="color: #ffd700;"><?= htmlspecialchars($b['articles']) ?></td>
                        <td><?= number_format($b['total'], 0) ?> 🟡</td>
                        <td><?= htmlspecialchars($b['nom_client']) ?></td>
                        <td>
                            <form method="POST" action="delete_commande.php" style="display:inline;">
                                <?php csrf_input(); ?>
                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button type="submit" class="btn-action btn-archiver" onclick="return confirm('Archiver cette commande ?')" style="padding: 5px 10px; font-size: 0.9em;">Archiver</button>
                            </form>
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