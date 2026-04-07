<?php
session_start();
require_once 'config.php';
require_once 'helpers.php';

// Accepter seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit();
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    set_flash_message('Requête invalide. Veuillez réessayer.', 'error');
    header('Location: contact.php');
    exit();
}

// Valider et nettoyer les données
$nom = isset($_POST['nom']) ? sanitize_text($_POST['nom'], 100) : null;
$email = isset($_POST['email']) ? trim($_POST['email']) : null;
$type = isset($_POST['type']) ? sanitize_text($_POST['type']) : null;
$projet = isset($_POST['projet']) ? sanitize_text($_POST['projet'], 5000) : null;
$budget = isset($_POST['budget']) ? intval($_POST['budget']) : 0;

// Types acceptés
$types_autorises = ['Compagnon', 'Securite', 'Domestique', 'Inclassable'];

// Vérifier les champs requis
if (!$nom || !$email || !$type || !$projet) {
    set_flash_message('Tous les champs requis doivent être remplis.', 'error');
    header('Location: contact.php');
    exit();
}

// Valider l'email
if (!validate_email($email)) {
    set_flash_message('Adresse email invalide.', 'error');
    header('Location: contact.php');
    exit();
}

// Valider le type (prévention injection)
if (!in_array($type, $types_autorises)) {
    set_flash_message('Type de mécanisme invalide.', 'error');
    header('Location: contact.php');
    exit();
}

// Valider le budget (doit être positif s'il existe)
if ($budget < 0) {
    set_flash_message('Le budget doit être positif.', 'error');
    header('Location: contact.php');
    exit();
}

// Insérer la commande
try {
    $ins = $db->prepare(
        "INSERT INTO commandes_speciales 
        (nom_client, email_client, type_chimere, description_projet, budget_estime) 
        VALUES (?, ?, ?, ?, ?)"
    );
    $ins->execute([$nom, $email, $type, $projet, $budget]);
    
    set_flash_message('Votre commande a été déposée sur le bureau de l\'Inventrice !', 'success');
    header('Location: index.php');
    exit();
    
} catch (PDOException $e) {
    error_log('Erreur insertion commande: ' . $e->getMessage());
    set_flash_message('Une erreur est survenue. Veuillez réessayer plus tard.', 'error');
    header('Location: contact.php');
    exit();
}
?>