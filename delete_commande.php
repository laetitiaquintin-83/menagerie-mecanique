<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// PROTECTION : Seule l'admin peut supprimer
require_admin_connection();

// Accepter seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: commandes.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('Requête invalide. Veuillez réessayer.', 'error');
    header('Location: commandes.php');
    exit();
}

// Valider l'ID
if (!isset($_POST['id']) || !validate_positive_integer($_POST['id'])) {
    set_flash_message('Commande invalide.', 'error');
    header('Location: commandes.php');
    exit();
}

$id = intval($_POST['id']);

try {
    // Supprimer la commande
    $req = $db->prepare("DELETE FROM commandes_speciales WHERE id = ?");
    $req->execute([$id]);
    
    set_flash_message('Commande archivée avec succès.', 'success');
} catch (PDOException $e) {
    error_log('Erreur suppression commande: ' . $e->getMessage());
    set_flash_message('Erreur lors de l\'archivage.', 'error');
}

header('Location: commandes.php');
exit();
?>