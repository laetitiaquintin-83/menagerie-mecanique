<?php
/**
 * ======================================================================
 * config.php - CONFIGURATION CENTRALE DE L'APPLICATION
 * ======================================================================
 * 
 * Ce fichier centralise TOUTE la configuration de l'application.
 * 
 * AVANTAGES :
 * - Une seule source de vérité (DRY principle = Don't Repeat Yourself)
 * - Facile à maintenir et modifier
 * - Sécurité : toutes les constantes au même endroit
 * 
 * ⚠️ SÉCURITÉ IMPORTANTE :
 * - Ce fichier NE doit PAS être accessible via HTTP en production
 * - Ajouter un .htaccess pour bloquer l'accès direct
 * - JAMAIS commiter les vrais identifiants en git (utiliser .env)
 * 
 * ======================================================================
 */

// ===== 1. CONFIGURATION BASE DE DONNÉES =====
// Ces constantes définissent la connexion MySQL
define('DB_HOST', 'localhost');      // Serveur MySQL
define('DB_NAME', 'atelier_chimeres'); // Nom de la base
define('DB_USER', 'root');           // Utilisateur MySQL (root en dev)
define('DB_PASS', '');               // Mot de passe (vide en local Laragon)
define('DB_CHARSET', 'utf8mb4');     // Encodage (UTF-8 avec support emoji)

// ===== 2. CHEMINS DE L'APPLICATION =====
// Utile pour les includes et uploads
define('APP_ROOT', dirname(__FILE__) . '/');  // Racine du projet
define('UPLOAD_DIR', APP_ROOT . 'images/');   // Où stocker les images
define('INCLUDE_DIR', APP_ROOT);              // Où trouver les fichiers

// ===== 3. LIMITES ET VALIDATION D'UPLOADS =====
// Règles de sécurité pour les fichiers uploadés
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);  // 5MB maximum (en bytes)
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',  // MIME type pour JPG
    'image/png',   // MIME type pour PNG
    'image/gif',   // MIME type pour GIF
    'image/webp'   // MIME type pour WebP
]);
define('ALLOWED_EXTENSIONS', [
    'jpg', 'jpeg',  // Extensions JPG
    'png',          // Extension PNG
    'gif',          // Extension GIF
    'webp'          // Extension WebP
]);
// Pourquoi vérifier MIME ET extension ?
// → MIME type bloque les fichiers dangereux (.exe, .php)
// → Extension bloque les contournements (malware.jpg.php)

// ===== 4. PARAMÈTRES DE SÉCURITÉ =====
// Configuration des protections de sécurité
define('SESSION_TIMEOUT', 3600);         // Timeout session : 1 heure
define('CSRF_TOKEN_LIFETIME', 3600);     // Tokens CSRF : 1 heure d'expiration
define('PASSWORD_MIN_LENGTH', 8);        // Longueur minimum mot de passe

// Explications :
// SESSION_TIMEOUT : Force la déconnexion après 1h d'inactivité
// CSRF_TOKEN : Expire pour prévenir rejeu d'attaques
// PASSWORD_MIN_LENGTH : Minimum 8 caractères pour la sécurité

// ===== 5. CONSTANTES D'AFFICHAGE =====
// Format des dates et couleurs du thème
define('DATE_FORMAT', 'd/m/Y à H:i');       // Format long : 07/04/2026 à 14:30
define('DATE_FORMAT_SHORT', 'd/m/Y');       // Format court : 07/04/2026

// Couleurs du thème Steampunk
define('COLOR_DARK', '#1a110a');    // Noir profond (fond)
define('COLOR_LIGHT', '#f4e4bc');   // Couleur papier ancien
define('COLOR_ACCENT', '#d4af37');  // Or (texte principal)
define('COLOR_BROWN', '#8b5a2b');   // Marron (bordures)

// ===== 6. INITIALISATION DE LA BASE DE DONNÉES =====
// Création d'une connexion PDO sécurisée
try {
    // Créer la connexion PDO (PHP Data Objects)
    // PDO = Protection automatique contre SQL injection
    $db = new PDO(
        // Data Source Name (DSN) = adresse BD
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,    // Utilisateur
        DB_PASS,    // Mot de passe
        [
            // Options PDO de sécurité :
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // → Lancer des exceptions si erreur (au lieu de silent fail)
            
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // → Retourner les résultats en tableau associatif
            
            PDO::ATTR_EMULATE_PREPARES => false,
            // → Désactiver l'émulation, utiliser les prepared statements du serveur
            // → C'est CRUCIAL pour la sécurité SQL injection
        ]
    );
    
    // Si on arrive ici, la connexion est réussie ✅
    // La variable $db est maintenant disponible partout dans l'app
    
} catch (PDOException $e) {
    // ERREUR : Impossible de se connecter à la BD
    // PDOException = Exception levée par PDO
    
    // Comportement différent dev vs production
    if (php_sapi_name() === 'cli' || $_ENV['APP_ENV'] === 'dev') {
        // EN DÉVELOPPEMENT : afficher l'erreur (utile pour debug)
        die('Erreur de connexion à la base de données : ' . $e->getMessage());
    }
    
    // EN PRODUCTION : ne PAS afficher l'erreur (risque de sécurité)
    // Enregistrer l'erreur dans les logs serveur à la place
    error_log('Database connection error: ' . $e->getMessage());
    // Afficher un message générique à l'utilisateur
    die('Une erreur est survenue. Veuillez réessayer plus tard.');
}

// ✅ À partir d'ici, $db est disponible globalement
// Chaque page peut faire : require_once 'config.php'; et utiliser $db
?>

