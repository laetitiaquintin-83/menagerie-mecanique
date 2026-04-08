<?php
/**
 * ======================================================================
 * helpers.php - LIBRAIRIE DE FONCTIONS RÉUTILISABLES
 * ======================================================================
 * 
 * Ce fichier contient ALL les fonctions utilitaires de l'application.
 * 
 * AVANTAGES :
 * - DRY (Don't Repeat Yourself) : pas de code en double
 * - Facile à tester unitairement
 * - Changements centralisés : modifier une logique = modifier UN endroit
 * - Réutilisable : une fonction = utilisée partout
 * 
 * ORGANISATION :
 * 1. Vérification de session (protect pages)
 * 2. Gestion des tokens CSRF (sécurité formulaires)
 * 3. Validation des données (input sanitization)
 * 4. Uploads de fichiers (sécurité stricte)
 * 5. Messages de feedback (UX améliorée)
 * 
 * ======================================================================
 */

// ===== 1. VÉRIFICATION DE SESSION =====

/**
 * Vérifie que l'utilisateur est admin, sinon le redirige vers login
 * 
 * UTILISATION :
 * <?php require_once 'helpers.php'; require_admin_connection(); ?>
 * 
 * Si pas admin : redirect à login.php et exit
 * Si admin : continue l'exécution de la page
 * 
 * PROTÈGE CONTRE : accès non-autorisé aux pages admin
 */
function require_admin_connection() {
    // Vérifier que la session a la clé 'admin' ET qu'elle est TRUE (strictement)
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        // Pas autorisé → redirection et stop
        header('Location: login.php');
        exit(); // CRUCIAL : arrêter l'exécution du script
    }
    // Si on arrive ici, l'utilisateur est admin ✅
}

/**
 * Vérifie si l'utilisateur est connecté (retourne boolean)
 * 
 * UTILISATION :
 * <?php if (is_admin_connected()) { echo 'Admin connecté'; } ?>
 * 
 * DIFFÉRENCE avec require_admin_connection :
 * - require_admin : force redirection si pas admin
 * - is_admin : retourne TRUE/FALSE, laisse la décision au code
 */
function is_admin_connected() {
    return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
}

// ===== 2. GESTION DES TOKENS CSRF =====

/**
 * CSRF = Cross-Site Request Forgery
 * = Attaque où un site malveillant force utilisateur à faire des actions
 * 
 * EXEMPLE D'ATTAQUE :
 * 1. Utilisateur connecté à atelier.local
 * 2. Visite malveillant.com
 * 3. malveillant.com envoie POST caché à atelier.local/delete.php?id=1
 * 4. Création supprimée sans permission !
 * 
 * PROTECTION :
 * = Token secret généré par le serveur, stocké en session
 * = Token inclus dans chaque formulaire
 * = Serveur vérifie token avant de traiter
 * = Attaquant NE PEUT PAS connaître le token !
 */

/**
 * Génère et stocke un token CSRF dans la session
 * 
 * COMMENT ÇA MARCHE :
 * 1. Vérifie si token existe déjà en session
 * 2. Si NON : générer un nouveau token aléatoire
 *    - bin2hex(random_bytes(32)) = 64 caractères impossibles à deviner
 * 3. Stocker le token + timestamp de création
 * 4. Retourner le token
 * 
 * UTILISATION dans formulaire :
 * <form method="POST">
 *     <?php csrf_input(); ?>
 *     ...
 * </form>
 */
function generate_csrf_token() {
    // Vérifier que le token n'existe pas déjà
    if (!isset($_SESSION['csrf_token'])) {
        // Générer 32 bytes aléatoires
        // bin2hex() = convertir en hexadécimal (64 caractères)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Stocker le moment de création pour pouvoir l'expirer
        $_SESSION['csrf_token_time'] = time();
    }
    
    // Retourner le token existant (ou nouvellement créé)
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité du token CSRF
 * 
 * VÉRIFICATIONS :
 * 1. Token fourni existe ET correspond au token en session
 * 2. Token n'a pas expiré (CSRF_TOKEN_LIFETIME = 3600 secondes)
 * 
 * RETOURNE :
 * - TRUE : token valide ✅ → traiter la requête
 * - FALSE : token invalide ❌ → rejeter la requête
 * 
 * UTILISATION :
 * <?php
 *   if ($_POST['csrf_token'] && verify_csrf_token($_POST['csrf_token'])) {
 *       // Traiter le formulaire
 *   } else {
 *       // Rejeter la requête
 *   }
 * ?>
 */
function verify_csrf_token($token) {
    // VÉRIFICATION 1 : Token existe ET correspond
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        // Token invalide ou absent
        return false;
    }
    
    // VÉRIFICATION 2 : Token n'a pas expiré
    // Calculer le temps écoulé depuis la création
    $elapsed = time() - $_SESSION['csrf_token_time'];
    
    if ($elapsed > CSRF_TOKEN_LIFETIME) {
        // Token trop vieux (plus de 1h) → supprimer et rejeter
        unset($_SESSION['csrf_token']);
        return false;
    }
    
    // Si on arrive ici, token est VALIDE ✅
    return true;
}

/**
 * Affiche un input hidden contenant le token CSRF
 * 
 * IMPORTANT : À ajouter dans CHAQUE formulaire
 * 
 * HTML généré :
 * <input type="hidden" name="csrf_token" value="a1b2c3d4...">
 * 
 * UTILISATION dans formulaire :
 * <form method="POST">
 *     <?php csrf_input(); ?>  ← Ajoute le token automatiquement
 *     <input name="email">
 *     <button>Envoyer</button>
 * </form>
 */
function csrf_input() {
    $token = generate_csrf_token();
    // htmlspecialchars = prévenir l'injection de caractères spéciaux
    // Exemple : " onclick="alert('hack')" "
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ===== 3. VALIDATION ET SÉCURITÉ =====

/**
 * SÉCURITÉ DES DONNÉES UTILISATEUR
 * 
 * LES TROIS NIVEAUX :
 * 1. SERVER-SIDE VALIDATION : vérifier que les données sont correctes
 * 2. SANITIZATION : nettoyer les données pour éviter injections
 * 3. ESCAPING : convertir caractères spéciaux en HTML entities
 * 
 * Exemple dangereux :
 * Utilisateur rentre : <script>alert('hack')</script>
 * Sans protection : script s'exécute dans le navigateur
 * Avec escaping : s'affiche comme texte normal
 */

/**
 * Nettoie et valide une chaîne de texte
 * 
 * ÉTAPES :
 * 1. trim() : supprimer espaces avant/après
 * 2. htmlspecialchars() : convertir caractères spéciaux
 *    Exemples : < devient &lt; / > devient &gt; / " devient &quot;
 * 3. Truncate si dépasseLongueur max
 * 
 * UTILISATION :
 * $nom = sanitize_text($_POST['nom'], 100); // Max 100 caractères
 * 
 * PARAMÈTRES :
 * - $text : chaîne à nettoyer
 * - $max_length : longueur maximum (optionnel)
 * 
 * RETOURNE : chaîne nettoyée et safe
 */
function sanitize_text($text, $max_length = null) {
    // 1. Supprimer les espaces inutiles
    $text = trim($text);
    
    // 2. Échapper les caractères HTML spéciaux
    // ENT_QUOTES = échappe guillemets simples ET doubles
    // UTF-8 = supportl'encodage international
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    
    // 3. Limiter la longueur si demandé
    if ($max_length && strlen($text) > $max_length) {
        $text = substr($text, 0, $max_length);
    }
    
    return $text;
}

/**
 * Valide une adresse email
 * 
 * UTILISE : filter_var avec FILTER_VALIDATE_EMAIL
 * = Fonction PHP standard pour valider emails
 * 
 * RETOURNE :
 * - TRUE : email valide
 * - FALSE : email invalide
 * 
 * UTILISATION :
 * if (validate_email($_POST['email'])) {
 *     // Email valide
 * } else {
 *     // Email invalide → afficher erreur
 * }
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide qu'une valeur est un entier positif
 * 
 * CAS D'USAGE : vérifier les IDs
 * Exemple : delete.php?id=5
 * = Vérifier que id est bien un nombre positif
 * 
 * DOUBLE VÉRIFICATION :
 * 1. is_numeric() : c'est un nombre ?
 * 2. intval() > 0 : convertir en entier et vérifier positif
 * 
 * RETOURNE :
 * - TRUE : valeur positif
 * - FALSE : 0, négatif, ou pas un nombre
 */
function validate_positive_integer($value) {
    return is_numeric($value) && intval($value) > 0;
}

// ===== 4. UPLOADS DE FICHIERS =====

/**
 * SÉCURITÉ DES UPLOADS = CRITIQUE
 * 
 * MENACES COURANTES :
 * 1. Uploader un virus (.exe, .php)
 * 2. Uploader un fichier de 500MB pour bouffer le serveur
 * 3. Uploader un fichier nommé "../../index.php" (path traversal)
 * 4. Trick : nommer fichier "malware.php.jpg" (exécuté comme PHP)
 * 
 * NOTRE DÉFENSE :
 * 1. Vérifier les erreurs d'upload (serveur)
 * 2. Vérifier la taille (MAX_UPLOAD_SIZE)
 * 3. Vérifier l'extension (.jpg, .png uniquement)
 * 4. Vérifier le MIME type (vrai type du fichier)
 * 5. Renommer le fichier (random name avoid collisions)
 * 6. Stocker HORS du webroot (si possible)
 */

/**
 * Valide et traite l'upload d'une image
 * 
 * RETOURNE :
 * - STRING : chemin du fichier stocké (ex: "images/1704123456_a1b2c3d4.jpg")
 * - NULL : erreur → message en $_SESSION['upload_error']
 * 
 * UTILISATION dans le formulaire :
 * if ($_FILES['image']) {
 *     $path = handle_image_upload('image');
 *     if ($path) {
 *         // Succès : $path contient le chemin
 *     } else {
 *         // Erreur : afficher $_SESSION['upload_error']
 *     }
 * }
 */
function handle_image_upload($file_input_name) {
    // ÉTAPE 1 : Vérifier que le formulaire a envoyé un fichier
    if (!isset($_FILES[$file_input_name])) {
        $_SESSION['upload_error'] = 'Aucun fichier fourni';
        return null;
    }
    
    $file = $_FILES[$file_input_name];
    
    // ÉTAPE 2 : Vérifier les erreurs d'upload PHP
    if ($file['error'] !== UPLOAD_ERR_OK) {
        // Tableau de messages d'erreur PHP
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
    
    // ÉTAPE 3 : Vérifier la taille
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $_SESSION['upload_error'] = 'Le fichier dépasse 5 MB';
        return null;
    }
    
    // ÉTAPE 4 : Vérifier l'extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $_SESSION['upload_error'] = 'Format non autorisé. Utilisez : JPG, PNG, GIF, WebP';
        return null;
    }
    
    // ÉTAPE 5 : Vérifier le MIME type (vrai type du fichier)
    // MIME = type MIME décidé par le système, pas par l'extension
    // Exemple : .exe aura MIME type "application/x-dosexec"
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']); // Analyser le vrai contenu
    finfo_close($finfo);
    
    if (!in_array($mime, ALLOWED_MIME_TYPES)) {
        $_SESSION['upload_error'] = 'Type de fichier invalide';
        return null;
    }
    
    // ÉTAPE 6 : Créer le répertoire s'il n'existe pas
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
        // 0755 = permissions : utilisateur=rwx, groupe=rx, autres=rx
    }
    
    // ÉTAPE 7 : Générer un nom unique et sécurisé
    // time() = timestamp UNIX (ex: 1704123456)
    // bin2hex(random_bytes(8)) = 16 caractères random (ex: a1b2c3d4e5f6g7h8)
    // Pourquoi random ? Éviter les collisions de noms si 2 uploads en même temps
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    
    // ÉTAPE 8 : Déplacer le fichier du répertoire temporaire vers UPLOAD_DIR
    // Fonction de sécurité PHP : déplace UNIQUEMENT si upload valide
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $_SESSION['upload_error'] = 'Impossible de sauvegarder le fichier';
        return null;
    }
    
    // ÉTAPE 9 : Succès ! Nettoyer l'erreur et retourner le chemin
    unset($_SESSION['upload_error']);
    // Retourner chemin relatif (pour utiliser dans <img src="">)
    return 'images/' . $filename;
}

/**
 * Supprime un fichier image de façon sécurisée
 * 
 * SÉCURITÉ : vérifier qu'on supprime que des fichiers dans UPLOAD_DIR
 * = Prévenir path traversal : ../../etc/passwd
 * 
 * PARAMÈTRE :
 * - $filepath : chemin du fichier (ex: "images/1704123456_a1b2c3d4.jpg")
 * 
 * RETOURNE :
 * - TRUE : fichier supprimé ✅
 * - FALSE : fichier pas trouvé ou pas autorisé ❌
 */
function delete_image_file($filepath) {
    // Sécurité : vérifier que le chemin reste dans UPLOAD_DIR
    // realpath() = chemain absolu réel (évite symlinks malveillants)
    $real_path = realpath(UPLOAD_DIR . '/' . basename($filepath));
    $upload_real = realpath(UPLOAD_DIR);
    
    // Vérifications :
    // 1. $real_path existe (fichier vrai)
    // 2. strpos($real_path, $upload_real) === 0 (chemin commence par UPLOAD_DIR)
    // 3. file_exists($real_path) (fichier existe vraiment)
    if ($real_path && strpos($real_path, $upload_real) === 0 && file_exists($real_path)) {
        @unlink($real_path); // @ = suppprimer les warnings PHP
        return true;
    }
    
    // Fichier non autorisé ou pas trouvé
    return false;
}

// ===== 5. MESSAGES ET FEEDBACK UTILISATEUR =====

/**
 * FLASH MESSAGE = message affiché UNE FOIS puis supprimé
 * 
 * FLUX :
 * 1. page1.php : set_flash_message("Succès !")
 * 2. Redirection vers page2.php
 * 3. page2.php : show_flash_and_clear() affiche et supprime
 * 4. Rechargement page2.php : le message a disparu
 * 
 * UTILITÉ : afficher messages temporaires sans être intrusif
 * Exemple : "Créature supprimée avec succès" après action
 */

/**
 * Affiche le message flash et le supprime
 * 
 * RETOURNE : tableau avec ['message' => '...', 'type' => 'success']
 * 
 * UTILISATION dans la page :
 * <?php
 *   $flash = show_flash_and_clear();
 *   if ($flash['message']) {
 *       echo '<div class="alert-' . $flash['type'] . '">' . $flash['message'] . '</div>';
 *   }
 * ?>
 */
function show_flash_and_clear() {
    $msg = null;
    $type = 'info'; // Type par défaut
    
    // Vérifier si un message flash existe
    if (isset($_SESSION['flash_message'])) {
        // Récupérer le message
        $msg = $_SESSION['flash_message'];
        
        // Récupérer le type (success, error, warning, info)
        $type = $_SESSION['flash_type'] ?? 'info';
        
        // Supprimer le message (il a été affiché une fois)
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
    
    // Retourner le message et son type au code appelant
    return ['message' => $msg, 'type' => $type];
}

/**
 * ===============================================
 * GESTION DES STATUTS DE COMMANDE
 * ===============================================
 */

/**
 * Liste des statuts disponibles
 */
function get_statuts_options() {
    return [
        'en_attente' => ['label' => '⏳ En attente', 'color' => '#ffd700'],
        'confirmee' => ['label' => '✅ Confirmée', 'color' => '#90ee90'],
        'expedie' => ['label' => '📦 Expédiée', 'color' => '#87ceeb'],
        'livree' => ['label' => '🎉 Livrée', 'color' => '#32cd32'],
        'annulee' => ['label' => '❌ Annulée', 'color' => '#ff6b6b']
    ];
}

/**
 * Obtient le label et couleur d'un statut
 */
function get_statut_display($statut) {
    $statuts = get_statuts_options();
    return $statuts[$statut] ?? ['label' => $statut, 'color' => '#d4af37'];
}

/**
 * Met à jour le statut d'une commande
 */
function update_commande_statut($id, $nouveau_statut, $type = 'boutique') {
    global $db;
    
    try {
        // Valider le StatutSolver
        $statuts_valides = array_keys(get_statuts_options());
        if (!in_array($nouveau_statut, $statuts_valides)) {
            return false;
        }
        
        $table = ($type === 'devis') ? 'commandes_speciales' : 'commandes';
        $id_column = ($type === 'devis') ? 'id' : 'id';
        
        $stmt = $db->prepare("UPDATE $table SET statut = ? WHERE id = ?");
        return $stmt->execute([$nouveau_statut, $id]);
    } catch (Exception $e) {
        error_log('Erreur statut: ' . $e->getMessage());
        return false;
    }
}

/**
 * ===============================================
 * GESTION DES FAVORIS
 * ===============================================
 */

/**
 * Obtient l'ID de session utilisateur unique
 */
function get_user_id() {
    if (is_admin_connected()) {
        return 'admin_' . session_id();
    }
    return 'user_' . session_id();
}

/**
 * Ajoute une créature aux favoris
 */
function add_favorite($creature_id) {
    global $db;
    
    try {
        $user_id = get_user_id();
        $creature_id = (int)$creature_id;
        
        $stmt = $db->prepare("INSERT IGNORE INTO favorites (user_session, creature_id) VALUES (?, ?)");
        return $stmt->execute([$user_id, $creature_id]);
    } catch (Exception $e) {
        error_log('Erreur ajout favori: ' . $e->getMessage());
        return false;
    }
}

/**
 * Supprime une créature des favoris
 */
function remove_favorite($creature_id) {
    global $db;
    
    try {
        $user_id = get_user_id();
        $creature_id = (int)$creature_id;
        
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_session = ? AND creature_id = ?");
        return $stmt->execute([$user_id, $creature_id]);
    } catch (Exception $e) {
        error_log('Erreur suppression favori: ' . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si une créature est favorite
 */
function is_favorite($creature_id) {
    global $db;
    
    try {
        $user_id = get_user_id();
        $creature_id = (int)$creature_id;
        
        $stmt = $db->prepare("SELECT id FROM favorites WHERE user_session = ? AND creature_id = ?");
        $stmt->execute([$user_id, $creature_id]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Erreur vérifi favori: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtient tous les favoris de l'utilisateur
 */
function get_user_favorites() {
    global $db;
    
    try {
        $user_id = get_user_id();
        
        $stmt = $db->prepare("
            SELECT c.* FROM creatures c
            INNER JOIN favorites f ON c.id = f.creature_id
            WHERE f.user_session = ?
            ORDER BY f.date_ajout DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erreur récupération favoris: ' . $e->getMessage());
        return [];
    }
}

/**
 * Ajoute un bouton cœur pour un favori (HTML + JS)
 */
function render_favorite_button($creature_id) {
    $is_fav = is_favorite($creature_id);
    $class = $is_fav ? 'favorite-btn active' : 'favorite-btn';
    $icon = $is_fav ? '❤️' : '🤍';
    
    return "
        <button class='$class' data-creature-id='$creature_id' title='Ajouter/Retirer des favoris' style='
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            padding: 5px;
            opacity: 0.8;
            transition: opacity 0.3s;
        '>
            $icon
        </button>
    ";
}

/**
 * Stocke un message flash pour l'affichage suivant
 * 
 * PARAMÈTRES :
 * - $message : texte à afficher
 * - $type : 'info', 'success', 'error', 'warning'
 * 
 * UTILISATION :
 * <?php
 *   set_flash_message('Créature créée !', 'success');
 *   header('Location: index.php');
 * ?>
 * 
 * Puis sur index.php, appeler show_flash_and_clear() pour afficher
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

?>
