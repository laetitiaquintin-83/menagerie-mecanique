<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// Initialiser le panier s'il n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Logique pour supprimer un article
if (isset($_GET['delete'])) {
    $id_to_remove = (int)$_GET['delete'];
    unset($_SESSION['panier'][$id_to_remove]);
    header('Location: panier.php');
    exit;
}

$total_panier = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Votre Panier - L'Atelier</title>
    <style>
        body { background-color: #1a110a; color: #d4af37; font-family: 'Georgia', serif; text-align: center; }
        .panier-container { max-width: 800px; margin: 50px auto; background: rgba(43, 24, 16, 0.9); padding: 30px; border: 2px solid #8b5a2b; border-radius: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { border-bottom: 2px solid #8b5a2b; padding: 10px; }
        td { padding: 15px; border-bottom: 1px solid #3d261a; }
        .btn-payer { background: #ffd700; color: #1a110a; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1.2em; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="panier-container">
        <h1>🛒 Votre Inventaire de Commande</h1>
        
        <?php if (empty($_SESSION['panier'])): ?>
            <p>Votre panier est aussi vide qu'une carcasse sans engrenages.</p>
            <a href="index.php" style="color: #8b5a2b;">⬅ Retourner à la boutique</a>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Créature</th>
                        <th>Prix</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['panier'] as $id => $item): 
                        $total_panier += $item['prix'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nom']) ?></td>
                        <td><?= number_format($item['prix'], 2) ?> 🟡</td>
                        <td><a href="panier.php?delete=<?= $id ?>" style="color: #ff4444;">Démonter</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; font-size: 1.5em;">
                Total : <strong><?= number_format($total_panier, 2) ?> 🟡</strong>
            </div>

            <a href="paiement.php" class="btn-payer">Procéder au Règlement ⚙️</a>
        <?php endif; ?>
    </div>
</body>
</html>