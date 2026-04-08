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

// Traiter le formulaire client (avant paiement)
$error_client = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commander'])) {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_client = 'Requête invalide. Veuillez réessayer.';
    } else {
        // Valider les données
        $nom = isset($_POST['client_nom']) ? sanitize_text($_POST['client_nom'], 100) : null;
        $email = isset($_POST['client_email']) ? trim($_POST['client_email']) : null;
        
        if (!$nom || !$email) {
            $error_client = 'Nom et email sont requis.';
        } elseif (!validate_email($email)) {
            $error_client = 'Email invalide.';
        } else {
            // Stocker les données du client en session
            $_SESSION['client_nom'] = $nom;
            $_SESSION['client_email'] = $email;
            
            // Redirection vers paiement
            header('Location: paiement.php');
            exit();
        }
    }
}

$total_panier = 0;

// Récupérer les messages flash pour les convertir en toasts
$flash = show_flash_and_clear();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Panier - L'Atelier</title>
    <link rel="stylesheet" href="style.css">
    <script src="toast.js"></script>
    <style>
        body { background-color: #1a110a; color: #d4af37; font-family: 'Georgia', serif; text-align: center; }
        .panier-container { max-width: 800px; margin: 50px auto; background: rgba(43, 24, 16, 0.9); padding: 30px; border: 2px solid #8b5a2b; border-radius: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { border-bottom: 2px solid #8b5a2b; padding: 10px; }
        td { padding: 15px; border-bottom: 1px solid #3d261a; }
        .btn-payer { background: #ffd700; color: #1a110a; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1.2em; text-decoration: none; display: inline-block; margin-top: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 15px; border-left: 4px solid #f5c6cb; }
        .form-client { background: rgba(139, 90, 43, 0.2); padding: 20px; border-radius: 10px; margin-top: 30px; }
        .form-client input { width: 100%; padding: 10px; margin: 10px 0; background: #1a110a; border: 1px solid #8b5a2b; color: #ffd700; border-radius: 3px; box-sizing: border-box; font-family: 'Georgia', serif; }
        .form-client label { text-align: left; display: block; font-weight: bold; margin-top: 10px; }
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
                        <td><?= htmlspecialchars(html_entity_decode($item['nom'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= number_format($item['prix'], 2) ?> 🟡</td>
                        <td><a href="panier.php?delete=<?= $id ?>" style="color: #ff4444;">Démonter</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="margin-top: 30px; font-size: 1.5em;">
                Total : <strong><?= number_format($total_panier, 2) ?> 🟡</strong>
            </div>

            <?php if ($error_client): ?>
                <div class="alert-error"><?= htmlspecialchars($error_client) ?></div>
            <?php endif; ?>

            <div class="form-client">
                <h3>Avant de Commander</h3>
                <form method="POST">
                    <?php csrf_input(); ?>
                    
                    <label>Votre Nom ou Titre</label>
                    <input type="text" name="client_nom" placeholder="Ex: Jules Verne" required maxlength="100">
                    
                    <label>Votre Email</label>
                    <input type="email" name="client_email" placeholder="Ex: jules@vapeur.com" required maxlength="100">
                    
                    <button type="submit" name="commander" class="btn-payer">Procéder au Règlement ⚙️</button>
                </form>
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

</body>
</html>