<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commandes Spéciales - L'Atelier</title>
    <style>
        body { background: #1a110a; color: #d4af37; font-family: 'Georgia', serif; padding: 50px; }
        .form-container { 
            max-width: 700px; margin: 0 auto; background: #f4e4bc; 
            padding: 40px; border: 5px solid #8b5a2b; color: #2b1810;
            box-shadow: 10px 10px 0px #3b2313;
        }
        h1 { border-bottom: 2px solid #2b1810; text-align: center; }
        input, textarea, select { 
            width: 100%; padding: 10px; margin: 10px 0; 
            border: 1px solid #8b5a2b; background: rgba(255,255,255,0.5);
            box-sizing: border-box;
        }
        .btn-envoyer { 
            background: #2b1810; color: #f4e4bc; border: none; 
            padding: 15px; width: 100%; cursor: pointer; font-weight: bold;
        }
        .btn-envoyer:hover { background: #8b5a2b; }
    </style>
</head>
<body>

<div class="form-container">
    <h1>✉️ Passer Commande</h1>
    <p>Décrivez-moi l'automate que vous souhaitez que je forge pour vous...</p>
    
    <form action="traitement_contact.php" method="POST">
        <input type="text" name="nom" placeholder="Votre Nom ou Titre" required>
        <input type="email" name="email" placeholder="Votre adresse de courrier" required>
        
        <label>Type de mécanisme souhaité :</label>
        <select name="type">
            <option value="Compagnon">Compagnon de Voyage</option>
            <option value="Securite">Automate de Sécurité</option>
            <option value="Domestique">Majordome de Salon</option>
            <option value="Inclassable">Inclassable & Mystérieux</option>
        </select>
        
        <textarea name="projet" rows="6" placeholder="Décrivez les fonctions, les métaux (cuivre, laiton, acier) et l'utilité de la chimère..." required></textarea>
        
        <input type="number" name="budget" placeholder="Budget prévu (Pièces d'or)">
        
        <button type="submit" class="btn-envoyer">SCELLER LA LETTRE ET L'ENVOYER</button>
    </form>
</div>

</body>
</html>