<?php 
session_start(); 
include 'connexion.php';

if(isset($_POST['connexion'])) {
    $pseudo = $_POST['pseudo'];
    $password = $_POST['password'];

    // 1. On cherche l'utilisateur par son nom
    $check = $db->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ?");
    $check->execute([$pseudo]);
    $user = $check->fetch();

    // 2. Vérification du mot de passe haché
    if($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['admin'] = true;
        // On stocke le nom pour l'affichage sur l'index
        $_SESSION['nom_admin'] = $user['nom_utilisateur']; 
        header('Location: index.php');
        exit();
    } else {
        $erreur = "Accès refusé. Mécanisme bloqué.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identification - L'Atelier</title>
    <style>
        body { 
            background: #0c0805; 
            display: flex; 
            height: 100vh; 
            align-items: center; 
            justify-content: center; 
            margin: 0; 
            overflow: hidden;
            font-family: 'Georgia', serif;
        }
        
        .lock-box { 
            background: #2b1810; 
            padding: 40px; 
            border: 3px solid #8b5a2b; 
            border-radius: 50%; 
            width: 350px; 
            height: 350px;
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center;
            box-shadow: 0 0 50px rgba(139, 90, 43, 0.4);
            position: relative;
            transition: 0.5s;
        }

        .lock-box:hover { border-color: #ffd700; box-shadow: 0 0 60px rgba(212, 175, 55, 0.3); }

        h2 { color:#ffd700; font-size: 1.2em; margin-bottom: 10px; letter-spacing: 3px; text-transform: uppercase; }
        
        input { 
            background: #1a110a; 
            border: 1px solid #8b5a2b; 
            color: #ffd700; 
            padding: 12px; 
            margin: 8px; 
            text-align: center; 
            border-radius: 5px; 
            outline: none; 
            width: 180px;
            transition: 0.3s;
        }

        input:focus { border-color: #ffd700; box-shadow: 0 0 10px rgba(212, 175, 55, 0.2); }

        button { 
            background: #8b5a2b; 
            color: #1a110a; 
            border: none; 
            padding: 12px 20px; 
            font-weight: bold; 
            cursor: pointer; 
            border-radius: 5px; 
            transition: 0.3s; 
            margin-top: 15px;
            letter-spacing: 1px;
        }

        button:hover { background: #ffd700; transform: scale(1.05); }

        .error { color: #ff4444; font-size: 0.8em; position: absolute; bottom: 60px; font-style: italic; }

        .back-link {
            position: absolute;
            bottom: -50px;
            color: #8b5a2b;
            text-decoration: none;
            font-size: 0.8em;
        }
        .back-link:hover { color: #ffd700; }
    </style>
</head>
<body>

    <form method="POST" class="lock-box">
        <h2>IDENTIFICATION</h2>
        
        <?php if(isset($erreur)): ?>
            <p class="error"><?php echo $erreur; ?></p>
        <?php endif; ?>
        
        <input type="text" name="pseudo" placeholder="Nom d'inventeur" required>
        <input type="password" name="password" placeholder="Clé vapeur" required>
        
        <button type="submit" name="connexion">DÉVERROUILLER</button>

        <a href="index.php" class="back-link">← Retourner à l'atelier</a>
    </form>

</body>
</html>