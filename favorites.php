<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// Récupérer les favoris
$favorites = get_user_favorites();
$flash = show_flash_and_clear();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris - L'Atelier des Chimères</title>
    <link rel="stylesheet" href="style.css">
    <script src="toast.js"></script>
    <style>
        body { background-color: #1a110a; color: #d4af37; text-align: center; font-family: 'Georgia', serif; margin: 0; padding: 20px; }
        .favorites-container { max-width: 1200px; margin: 0 auto; }
        .header-favorites { background: rgba(43, 24, 16, 0.95); padding: 30px; border: 2px solid #8b5a2b; border-radius: 10px; margin-bottom: 30px; text-align: center; }
        .header-favorites h1 { color: #ffd700; margin: 0; font-size: 2em; }
        .header-favorites p { opacity: 0.8; margin: 10px 0 0 0; }
        .empty-favorites { text-align: center; padding: 60px 20px; }
        .empty-favorites p { color: #8b5a2b; font-size: 1.2em; }
        .btn-retour { display: inline-block; background: #8b5a2b; color: #f4e4bc; padding: 10px 20px; border-radius: 5px; text-decoration: none; font-weight: bold; }
        .grille-creatures { display: flex; justify-content: center; gap: 25px; flex-wrap: wrap; }
        .carte-animal { width: 30%; min-width: 300px; background-color: rgba(43, 24, 16, 0.95); padding: 20px; border-radius: 15px; border: 1px solid #8b5a2b; transition: 0.4s; position: relative; }
        .carte-animal:hover { transform: scale(1.02); box-shadow: 0 0 20px rgba(212, 175, 55, 0.3); }
        .image-container { width: 100%; height: 300px; overflow: hidden; border: 2px solid #d4af37; border-radius: 10px; background: #000; }
        .image-chimere { width: 100%; height: 100%; object-fit: cover; }
        .prix-tag { font-size: 24px; font-weight: bold; color: #ffd700; margin: 10px 0; }
        .favorite-btn { 
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 1.8em;
            cursor: pointer;
            padding: 5px;
            opacity: 0.85;
            transition: all 0.3s;
            color: #ff69b4;
            z-index: 10;
        }
        .favorite-btn:hover { opacity: 1; }
        .btn-action { padding: 8px 18px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; }
        .stats-favoris { background: rgba(139, 90, 43, 0.3); padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .stats-favoris p { margin: 5px 0; color: #d4af37; }
    </style>
</head>
<body>
    <div class="favorites-container">
        <div class="header-favorites">
            <h1>❤️ Mes Créatures Préférées</h1>
            <p>Votre collection de chimères adorées</p>
            <a href="index.php" class="btn-retour" style="margin-top: 15px;">← Retour à la boutique</a>
        </div>
        
        <?php if (!empty($flash['message'])): ?>
            <div style="background: <?= $flash['type'] === 'success' ? '#90ee90' : '#ff6b6b' ?>; color: <?= $flash['type'] === 'success' ? '#1a110a' : 'white' ?>; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($favorites)): ?>
            <div class="empty-favorites">
                <p style="font-size: 3em; margin-bottom: 20px;">🤍</p>
                <p>Vous n'avez pas encore de créatures favorites.</p>
                <p style="opacity: 0.7; margin-top: 10px;">Cliquez sur le cœur sur les cartes pour ajouter vos préférées !</p>
                <a href="index.php" class="btn-retour" style="margin-top: 20px;">Découvrir les créatures</a>
            </div>
        <?php else: ?>
            <div class="stats-favoris">
                <p>📊 Vous avez <strong><?= count($favorites) ?></strong> créature<?= count($favorites) > 1 ? 's' : '' ?> en favoris</p>
            </div>
            
            <div class="grille-creatures">
                <?php foreach ($favorites as $animal): ?>
                    <div class="carte-animal">
                        <button class="favorite-btn" data-creature-id="<?= $animal['id'] ?>" title="Retirer des favoris">
                            ❤️
                        </button>
                        
                        <div class="image-container">
                            <img src="<?= htmlspecialchars($animal['image_path']) ?>" class="image-chimere" alt="<?= htmlspecialchars($animal['nom']) ?>">
                        </div>
                        
                        <h2><?= htmlspecialchars(html_entity_decode($animal['nom'], ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></h2>
                        <p style="color: #f4e4bc; height: 60px; overflow: hidden;">
                            <?= htmlspecialchars(substr($animal['description'], 0, 100)) ?>...
                        </p>
                        <div class="prix-tag"><?= number_format($animal['prix'], 0, '.', ' ') ?> 🟡</div>
                        
                        <div style="margin-top: 20px; display: flex; justify-content: center; gap: 10px;">
                            <a href="details.php?id=<?= $animal['id'] ?>" class="btn-action" style="background: #8b5a2b; color: #f4e4bc;">🛠️ Détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons favoris
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const creatureId = this.getAttribute('data-creature-id');
                const btn = this;
                const card = btn.closest('.carte-animal');
                
                fetch('toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        creature_id: creatureId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (!data.is_favorite) {
                            // Retirer l'animation et supprimer la carte
                            card.style.animation = 'slideOut 0.3s ease-out forwards';
                            setTimeout(() => {
                                card.remove();
                                // Recharger si plus de favoris
                                const remaining = document.querySelectorAll('.carte-animal').length;
                                if (remaining === 0) {
                                    location.reload();
                                }
                            }, 300);
                            showToast('Retiré des favoris', 'info');
                        } else {
                            btn.textContent = '❤️';
                            showToast('❤️ Ajouté aux favoris', 'success');
                        }
                    } else {
                        showToast('Erreur: ' + (data.error || 'Inconnue'), 'error');
                    }
                })
                .catch(err => {
                    showToast('Erreur réseau: ' + err.message, 'error');
                });
            });
        });
    });
    </script>

</body>
</html>
