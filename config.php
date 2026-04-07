<?php
/**
 * Configuration centrale pour l'Atelier des Chimères
 * IMPORTANT : À protéger en production avec .htaccess
 */

// ===== BASE DE DONNÉES =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'atelier_chimeres');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===== CHEMINS =====
define('APP_ROOT', dirname(__FILE__) . '/');
define('UPLOAD_DIR', APP_ROOT . 'images/');
define('INCLUDE_DIR', APP_ROOT);

// ===== UPLOADS - VALIDATION =====
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB en bytes
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ===== SÉCURITÉ =====
define('SESSION_TIMEOUT', 3600); // 1 heure en secondes
define('CSRF_TOKEN_LIFETIME', 3600);
define('PASSWORD_MIN_LENGTH', 8);

// ===== CONSTANTES D'AFFICHAGE =====
define('DATE_FORMAT', 'd/m/Y à H:i');
define('DATE_FORMAT_SHORT', 'd/m/Y');

// Couleurs du thème (optionnel mais pratique)
define('COLOR_DARK', '#1a110a');
define('COLOR_LIGHT', '#f4e4bc');
define('COLOR_ACCENT', '#d4af37');
define('COLOR_BROWN', '#8b5a2b');

// ===== INITIALISATION DE LA BASE DE DONNÉES =====
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // En développement : afficher l'erreur ; en production : logger seulement
    if (php_sapi_name() === 'cli' || $_ENV['APP_ENV'] === 'dev') {
        die('Erreur de connexion à la base de données : ' . $e->getMessage());
    }
    // En production, enregistrer l'erreur et afficher un message générique
    error_log('Database connection error: ' . $e->getMessage());
    die('Une erreur est survenue. Veuillez réessayer plus tard.');
}
?>
