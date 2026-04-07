<?php
/**
 * helpers.php - Fonctions réutilisables pour l'Atelier
 */

// ===== VÉRIFICATION DE SESSION =====
/**
 * Vérifie que l'utilisateur est admin, sinon le redirige
 */
function require_admin_connection() {
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function is_admin_connected() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// ===== GESTION DES TOKENS CSRF =====
/**
 * Génère et stocke un token CSRF dans la session
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité du token CSRF
 */
function verify_csrf_token($token) {
    // Vérifier que le token existe et correspond
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    
    // Vérifier que le token n'est pas expiré
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_token']);
        return false;
    }
    
    return true;
}

/**
 * Affiche un input hidden avec le token CSRF
 */
function csrf_input() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ===== VALIDATION ET SÉCURITÉ =====
/**
 * Nettoie et valide une chaîne de texte
 */
function sanitize_text($text, $max_length = null) {
    $text = trim($text);
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    if ($max_length && strlen($text) > $max_length) {
        $text = substr($text, 0, $max_length);
    }
    
    return $text;
}

/**
 * Valide une adresse email basiquement
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un entier positif
 */
function validate_positive_integer($value) {
    return is_numeric($value) && intval($value) > 0;
}

// ===== UPLOADS DE FICHIERS =====
/**
 * Valide et traite l'upload d'un fichier image
 * Retourne le chemin du fichier si succès, sinon null avec erreur en $_SESSION['upload_error']
 */
function handle_image_upload($file_input_name) {
    if (!isset($_FILES[$file_input_name])) {
        $_SESSION['upload_error'] = 'Aucun fichier fourni';
        return null;
    }
    
    $file = $_FILES[$file_input_name];
    
    // Vérifier les erreurs d'upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la limite du serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la limite du formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a pas pu être complètement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier fourni',
            UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier',
            UPLOAD_ERR_EXTENSION => 'Extension interdite',
        ];
        $_SESSION['upload_error'] = $errors[$file['error']] ?? 'Erreur inconnue';
        return null;
    }
    
    // Vérifier la taille
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $_SESSION['upload_error'] = 'Le fichier dépasse 5 MB';
        return null;
    }
    
    // Vérifier l'extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $_SESSION['upload_error'] = 'Format non autorisé. Utilisez : JPG, PNG, GIF, WebP';
        return null;
    }
    
    // Vérifier le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
        $_SESSION['upload_error'] = 'Type de fichier invalide';
        return null;
    }
    
    // Créer le répertoire s'il n'existe pas
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    // Générer un nom unique et sécurisé
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    
    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $_SESSION['upload_error'] = 'Impossible de sauvegarder le fichier';
        return null;
    }
    
    // Succès
    unset($_SESSION['upload_error']);
    return 'images/' . $filename; // Chemin relatif pour affichage
}

/**
 * Supprime un fichier image en toute sécurité
 */
function delete_image_file($filepath) {
    // Vérifier que le chemin resterait dans le répertoire images
    $real_path = realpath(UPLOAD_DIR . '/' . basename($filepath));
    $upload_real = realpath(UPLOAD_DIR);
    
    if ($real_path && strpos($real_path, $upload_real) === 0 && file_exists($real_path)) {
        @unlink($real_path);
        return true;
    }
    
    return false;
}

// ===== MESSAGES ET FEEDBACK =====
/**
 * Affiche un message flash (message unique à l'affichage)
 */
function show_flash_and_clear() {
    $msg = null;
    $type = 'info';
    
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
    
    return ['message' => $msg, 'type' => $type];
}

/**
 * Stocke un message flash pour l'affichage suivant
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

?>
