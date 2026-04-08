<?php
session_start();
if (empty($_SESSION['panier'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Règlement Sécurisé - L'Atelier</title>
    <style>
        body { background-color: #1a110a; color: #d4af37; font-family: 'Georgia', serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card-paiement { background: #2b1810; padding: 40px; border: 3px double #ffd700; border-radius: 10px; width: 400px; box-shadow: 0 0 20px rgba(212, 175, 55, 0.2); }
        input { width: 100%; padding: 10px; margin: 10px 0; background: #1a110a; border: 1px solid #8b5a2b; color: #ffd700; border-radius: 5px; }
        .btn-final { background: linear-gradient(to bottom, #8b5a2b, #5d3a1a); color: white; border: none; width: 100%; padding: 15px; font-weight: bold; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="card-paiement">
        <h2 style="text-align: center;">💳 Terminal Cuivré</h2>
        <p style="font-size: 0.8em; text-align: center; color: #8b5a2b;">Cryptage par algorithme à vapeur 128-bits</p>
        
        <form action="confirmation.php" method="POST">
            <label>Nom du détenteur</label>
            <input type="text" placeholder="Jules Verne" required>
            
            <label>Numéro de la carte de crédit</label>
            <input type="text" placeholder="0000 0000 0000 0000" maxlength="16" required>
            
            <div style="display: flex; gap: 10px;">
                <input type="text" placeholder="MM/AA" style="width: 50%;" required>
                <input type="text" placeholder="CVC" style="width: 50%;" required>
            </div>
            
            <button type="submit" class="btn-final">VALIDER LA TRANSACTION</button>
        </form>
    </div>
</body>
</html>