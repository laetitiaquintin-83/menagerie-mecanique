<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// PROTECTION : Vérifier que l'admin est connecté
require_admin_connection();

// Accepter seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// Vérifier le CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('Requête invalide.', 'error');
    header('Location: index.php');
    exit();
}

// Valider l'ID
if (!isset($_POST['id']) || !validate_positive_integer($_POST['id'])) {
    set_flash_message('Chimère invalide.', 'error');
    header('Location: index.php');
    exit();
}

$id = intval($_POST['id']);

try {
    // Récupérer l'image avant suppression
    $req = $db->prepare("SELECT image_path FROM creatures WHERE id = ?");
    $req->execute([$id]);
    $creature = $req->fetch(PDO::FETCH_ASSOC);
    
    if ($creature && $creature['image_path']) {
        delete_image_file($creature['image_path']);
    }
    
    // Supprimer la créature
    $req = $db->prepare("DELETE FROM creatures WHERE id = ?");
    $req->execute([$id]);
    
    set_flash_message('Chimère supprimée avec succès.', 'success');
} catch (PDOException $e) {
    error_log('Erreur suppression créature: ' . $e->getMessage());
    set_flash_message('Erreur lors de la suppression.', 'error');
}

header('Location: index.php');
exit();
?>