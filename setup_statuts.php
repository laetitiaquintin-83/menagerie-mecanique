<?php
require_once 'config.php';

try {
    // Vérifier et ajouter statut à commandes (boutique)
    $checkCmd = $db->query("SHOW COLUMNS FROM commandes LIKE 'statut'");
    if ($checkCmd->rowCount() == 0) {
        $db->exec("ALTER TABLE commandes ADD COLUMN statut VARCHAR(50) DEFAULT 'en_attente'");
        echo '✅ Colonne statut ajoutée à commandes<br>';
    } else {
        echo '✅ Colonne statut déjà présente dans commandes<br>';
    }
    
    // Vérifier et ajouter statut à commandes_speciales (devis)
    $checkSpec = $db->query("SHOW COLUMNS FROM commandes_speciales LIKE 'statut'");
    if ($checkSpec->rowCount() == 0) {
        $db->exec("ALTER TABLE commandes_speciales ADD COLUMN statut VARCHAR(50) DEFAULT 'en_attente'");
        echo '✅ Colonne statut ajoutée à commandes_speciales<br>';
    } else {
        echo '✅ Colonne statut déjà présente dans commandes_speciales<br>';
    }
    
    echo '<br><strong>✅ Base de données mise à jour avec succès!</strong>';
} catch (Exception $e) {
    echo '❌ Erreur: ' . $e->getMessage();
}
?>
