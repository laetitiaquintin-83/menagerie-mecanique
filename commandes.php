<?php 
session_start();
require_once 'config.php';
require_once 'helpers.php';

// PROTECTION : Seule l'Inventrice peut lire son courrier
require_admin_connection();

// On récupère toutes les commandes, les plus récentes en premier
try {
    $req = $db->query("SELECT * FROM commandes_speciales ORDER BY date_demande DESC");
    $commandes = $req->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Erreur BD commandes: ' . $e->getMessage());
    $commandes = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Le Courrier de l'Atelier</title>
    <style>
        body { background: #1a110a; color: #d4af37; font-family: 'Georgia', serif; padding: 30px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { border-bottom: 3px double #8b5a2b; padding-bottom: 10px; text-align: center; }
        .lettre { 
            background: #f4e4bc; color: #2b1810; padding: 25px; 
            margin-bottom: 30px; border: 2px solid #8b5a2b;
            box-shadow: 5px 5px 15px rgba(0,0,0,0.5);
            position: relative;
        }
        .lettre::before { content: "📫"; position: absolute; top: 10px; right: 10px; font-size: 20px; }
        .meta { font-size: 0.85em; color: #6d4c41; border-bottom: 1px solid #d7ccc8; margin-bottom: 15px; }
        .projet { font-style: italic; line-height: 1.6; background: rgba(255,255,255,0.3); padding: 15px; }
        .budget { font-weight: bold; color: #8b5a2b; margin-top: 10px; display: block; }
        .back-link { color: #d4af37; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        
        /* Petit style pour le bouton supprimer */
        .btn-delete { 
            display: inline-block;
            margin-top: 15px;
            color: #610b0b; 
            font-size: 0.85em; 
            text-decoration: none;
            border: 1px solid #610b0b;
            padding: 5px 10px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-delete:hover { background: #610b0b; color: white; }
    </style>
</head>
<body>

<div class="container">
    <a href="index.php" class="back-link">← Retour à l'Atelier</a>
    <h1>📜 Courrier des Commandes Spéciales</h1>

    <?php if(empty($commandes)): ?>
        <p style="text-align: center;">Aucune lettre n'a encore été glissée sous la porte...</p>
    <?php endif; ?>

    <?php foreach($commandes as $c): ?>
        <div class="lettre">
            <div class="meta">
                Reçu le : <strong><?= date('d/m/Y à H:i', strtotime($c['date_demande'])) ?></strong><br>
                Expéditeur : <strong><?= htmlspecialchars($c['nom_client']) ?></strong> (<?= htmlspecialchars($c['email_client']) ?>)
            </div>
            
            <h3>Type de mécanisme : <?= htmlspecialchars($c['type_chimere']) ?></h3>
            
            <div class="projet">
                <?= nl2br(htmlspecialchars($c['description_projet'])) ?>
            </div>
            
            <span class="budget">💰 Budget estimé : <?= $c['budget_estime'] ?> pièces d'or</span>

            <form method="POST" action="delete_commande.php" style="display:inline-block; margin-top: 15px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generate_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($c['id']) ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('Voulez-vous vraiment archiver cette commande ?');">🗑️ Classer l'affaire</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>