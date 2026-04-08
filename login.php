<?php 
session_start(); 
require_once 'config.php';
require_once 'helpers.php';

$error = null;

// Si déjà connecté, rediriger
if (is_admin_connected()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connexion'])) {
    // Vérifier CSRF
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Requête invalide. Veuillez réessayer.';
    } else {
        // Valider les champs
        $pseudo = isset($_POST['pseudo']) ? trim($_POST['pseudo']) : null;
        $password = isset($_POST['password']) ? $_POST['password'] : null;
        
        if (!$pseudo || !$password) {
            $error = 'Tous les champs sont requis.';
        } else {
            try {
                // Chercher l'utilisateur
                $check = $db->prepare("SELECT * FROM utilisateurs WHERE nom_utilisateur = ?");
                $check->execute([$pseudo]);
                $user = $check->fetch(PDO::FETCH_ASSOC);
                
                // Vérifier le password
                if ($user && password_verify($password, $user['mot_de_passe'])) {
                    // Succès - régénérer ID de session pour éviter fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['admin'] = true;
                    $_SESSION['nom_admin'] = $user['nom_utilisateur'];
                    $_SESSION['login_time'] = time();
                    
                    // Logger la connexion
                    error_log('Admin login: ' . $user['nom_utilisateur']);
                    
                    header('Location: index.php');
                    exit();
                } else {
                    // Erreur générique pour prévenir l'enumération d'utilisateurs
                    $error = 'Accès refusé. Mécanisme bloqué.';
                    // Logger la tentative failed
                    error_log('Failed login attempt for user: ' . htmlspecialchars($pseudo));
                }
            } catch (PDOException $e) {
                error_log('Database error during login: ' . $e->getMessage());
                $error = 'Une erreur est survenue. Veuillez réessayer.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Identification - L'Atelier</title>
    <link rel="stylesheet" href="style.css">
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

        h2 { color: #ffd700; font-size: 1.2em; margin-bottom: 10px; letter-spacing: 3px; text-transform: uppercase; }
        
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
            font-family: 'Georgia', serif;
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

        .error { 
            color: #ff4444; 
            font-size: 0.85em; 
            text-align: center;
            margin-bottom: 15px;
            font-style: italic; 
            max-width: 280px;
        }

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
        
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        
        <?php csrf_input(); ?>
        
        <input type="text" name="pseudo" placeholder="Nom d'inventeur" required maxlength="50" autocomplete="username">
        <input type="password" name="password" placeholder="Clé vapeur" required autocomplete="current-password">
        
        <button type="submit" name="connexion">DÉVERROUILLER</button>

        <a href="index.php" class="back-link">← Retourner à l'atelier</a>
    </form>

</body>
</html>
    </form>

</body>
</html>