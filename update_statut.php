<?php
/**
 * ======================================================================
 * update_statut.php - TRAITEMENT DU CHANGEMENT DE STATUT
 * ======================================================================
 * Fichier traitement pour mettre à jour le statut d'une commande
 * 
 * POST params:
 * - id: l'ID de la commande
 * - nouveau_statut: le nouveau statut
 * - type: 'devis' ou 'boutique'
 */

session_start();
require_once 'config.php';
require_once 'helpers.php';

// Vérifier que l'utilisateur est admin
require_admin_connection();

// Vérifier la requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

// Vérifier CSRF
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash_message('Token CSRF invalide', 'error');
    header('Location: commandes.php');
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$nouveau_statut = $_POST['nouveau_statut'] ?? '';
$type = $_POST['type'] ?? 'boutique';

// Valider les données
if ($id <= 0 || empty($nouveau_statut) || !in_array($type, ['boutique', 'devis'])) {
    set_flash_message('Données invalides', 'error');
    header('Location: commandes.php');
    exit();
}

// Mettre à jour
if (update_commande_statut($id, $nouveau_statut, $type)) {
    set_flash_message('Statut mis à jour avec succès ✅', 'success');
} else {
    set_flash_message('Erreur lors de la mise à jour', 'error');
}

header('Location: commandes.php');
exit();
?>
