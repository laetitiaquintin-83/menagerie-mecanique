<?php
/**
 * ======================================================================
 * toggle_favorite.php - GESTION DES FAVORIS AJAX
 * ======================================================================
 * Fichier traitement pour ajouter/retirer des favoris
 * 
 * POST params (JSON):
 * - creature_id: l'ID de la créature
 */

session_start();
require_once 'config.php';
require_once 'helpers.php';

header('Content-Type: application/json');

// Vérifier la requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['creature_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID créature manquant']);
    exit();
}

$creature_id = (int)$data['creature_id'];

try {
    // Vérifier si c'est un favori (toggle)
    if (is_favorite($creature_id)) {
        remove_favorite($creature_id);
        echo json_encode([
            'success' => true,
            'message' => 'Retiré des favoris',
            'is_favorite' => false,
            'icon' => '🤍'
        ]);
    } else {
        add_favorite($creature_id);
        echo json_encode([
            'success' => true,
            'message' => 'Ajouté aux favoris',
            'is_favorite' => true,
            'icon' => '❤️'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
