<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

$order_created = false;

// Traiter POST uniquement (paiement.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        set_flash_message('Requête invalide. Veuillez réessayer.', 'error');
        header('Location: panier.php');
        exit();
    }

    // Vérifier que le panier n'est pas vide
    if (empty($_SESSION['panier'])) {
        header('Location: index.php');
        exit();
    }

    // Construire la liste des articles
    $total = 0;
    $liste_articles = [];
    
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['prix'];
        $liste_articles[] = $item['nom'];
    }
    $articles_string = implode(", ", $liste_articles);

    try {
        // Insérer la commande avec les données du client
        $nom_client = isset($_SESSION['client_nom']) ? $_SESSION['client_nom'] : "Client Mystère";
        $email_client = isset($_SESSION['client_email']) ? $_SESSION['client_email'] : "client@vapeur.com";
        
        $ins = $db->prepare("INSERT INTO commandes (nom_client, email_client, articles, total) VALUES (?, ?, ?, ?)");
        $ins->execute([
            $nom_client, 
            $email_client, 
            $articles_string, 
            $total
        ]);
        
        // Récupérer l'ID de la commande créée
        $order_id = $db->lastInsertId();
        
        // Vider le panier ET les données du client
        $_SESSION['panier'] = [];
        unset($_SESSION['client_nom']);
        unset($_SESSION['client_email']);
        
        // Redirection GET pour éviter doublon
        header('Location: confirmation.php?order_id=' . $order_id);
        exit();
    } catch (PDOException $e) {
        error_log("Erreur commande : " . $e->getMessage());
        set_flash_message('Une erreur technique est survenue lors de la forge de votre commande.', 'error');
        header('Location: panier.php');
        exit();
    }
}

// Traiter GET (affichage du message de succès)
if (isset($_GET['order_id']) && validate_positive_integer($_GET['order_id'])) {
    $order_created = true;
} else {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande Validée - L'Atelier des Chimères</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { 
            background-color: #1a110a; 
            color: #d4af37; 
            font-family: 'Georgia', serif; 
            text-align: center; 
            padding-top: 100px; 
            margin: 0;
        }
        .success-box { 
            max-width: 600px; 
            margin: 0 auto; 
            border: 2px solid #ffd700; 
            padding: 40px; 
            background: rgba(43, 24, 16, 0.9); 
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0,0,0,0.8);
        }
        .stamp { font-size: 80px; margin-bottom: 20px; filter: sepia(1) saturate(2); }
        h1 { color: #ffd700; text-transform: uppercase; letter-spacing: 2px; }
        p { font-size: 1.2em; line-height: 1.6; color: #f4e4bc; }
        .btn-home { 
            display: inline-block; 
            margin-top: 30px; 
            padding: 12px 25px; 
            background: #8b5a2b; 
            color: #ffd700; 
            text-decoration: none; 
            border-radius: 5px; 
            font-weight: bold;
            border: 1px solid #ffd700;
            transition: 0.3s;
        }
        .btn-home:hover { background: #ffd700; color: #1a110a; }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="stamp">📜</div>
        <h1>Ordre de Fabrication Reçu</h1>
        <p>Merci, cher client ! Vos plans ont été transmis à nos maîtres artisans.</p>
        <p>Votre chimère est actuellement en cours de montage dans nos ateliers. Vous serez averti dès que les rouages seront huilés et prêts pour l'expédition.</p>
        
        <a href="index.php" class="btn-home">Retourner à la Ménagerie</a>
    </div>
</body>
</html>