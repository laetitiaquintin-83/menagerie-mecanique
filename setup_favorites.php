<?php
require_once 'config.php';

try {
    // Créer la table si elle n'existe pas
    $db->exec("
        CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_session VARCHAR(255) NOT NULL,
            creature_id INT NOT NULL,
            date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_fav (user_session, creature_id),
            FOREIGN KEY (creature_id) REFERENCES creatures(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo '✅ Table favorites créée/vérifiée avec succès<br>';
    echo '<strong style="color: green;">✅ Base de données mise à jour!</strong>';
} catch (Exception $e) {
    echo '❌ Erreur: ' . $e->getMessage();
}
?>
