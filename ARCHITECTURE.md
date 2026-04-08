# 🏗️ ARCHITECTURE - Explications techniques

**Audience** : Développeur junior à l'examen  
**Objectif** : Comprendre comment tout fonctionne ensemble

---

## 📊 Vue d'ensemble

```
┌─────────────────────────────────────────┐
│ UTILISATEUR NAVIGATEUR                  │
│ (index.php, login.php, contact.php)     │
└────────────────┬────────────────────────┘
                 │ HTTP REQUEST
                 ↓
┌─────────────────────────────────────────┐
│ config.php + helpers.php                │
│ (initialisation + fonctions globales)   │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│ PAGE MÉTIER (admin.php, contact.php)    │
│ - Logique métier                        │
│ - Validation                            │
│ - Appels BD                             │
└────────────────┬────────────────────────┘
                 │
                 ↓
┌─────────────────────────────────────────┐
│ MySQL Database                          │
│ (creatures, utilisateurs, commandes)    │
└────────────────┬────────────────────────┘
                 │ Résultats
                 ↓
┌─────────────────────────────────────────┐
│ HTML/CSS RESPONSE                       │
│ (rendu au navigateur)                   │
└─────────────────────────────────────────┘
```

---

## 🔧 Composants clés

### 1️⃣ **config.php** - Cœur de l'app

**Rôle** : Initialiser tout ce qui est nécessaire

**Contient** :

```php
// 1. Constantes (immuables, globales)
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);
define('ALLOWED_MIME_TYPES', [...]);
define('CSRF_TOKEN_LIFETIME', 3600);

// 2. En Initialisation de PDO
$db = new PDO(...);

// 3. Configuration des erreurs
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION;
```

**Avantages** :

- ✅ Une source de vérité
- ✅ Facile à modifier
- ✅ Sécurité centralisée
- ✅ Performances (constantes = plus rapide que variables)

**Exemple d'utilisation** :

```php
<?php
// N'importe quel fichier
require_once 'config.php';

// Maintenant on peut utiliser :
echo MAX_UPLOAD_SIZE;  // ✅ 5242880
$db->query(...);       // ✅ Connexion BD existe
?>
```

---

### 2️⃣ **helpers.php** - Boîte à outils

**Rôle** : Regrouper les fonctions réutilisables

**5 catégories de fonctions** :

#### A. VÉRIFICATION DE SESSION

```php
require_admin_connection(); // Redirect si pas admin
is_admin_connected();       // Retourne bool
```

**Avantage** : Éviter de répéter la vérification dans chaque page

#### B. PROTECTION CSRF

```php
generate_csrf_token();      // Crée token aléatoire stocké en session
verify_csrf_token($token);  // Vérifie token valide + passé expiration
csrf_input();               // Affiche <input hidden> avec token
```

**Avantage** : Protection contre attaques cross-site dans formulaires

#### C. VALIDATION

```php
sanitize_text($text, 100);  // Nettoie + limite longueur
validate_email($email);     // Valide format email
validate_positive_integer($id); // Vérifie nombre positif
```

**Avantage** : Données sûres avant d'utiliser en BD

#### D. UPLOADS

```php
handle_image_upload('image');  // Upload avec 7 vérifications
delete_image_file($path);      // Supprime en toute sécurité
```

**Avantage** : Upload robuste et sécurisé

#### E. MESSAGES FLASH

```php
show_flash_and_clear();    // Récupère message une fois
set_flash_message($msg);   // Stocke pour affichage suivant
```

**Avantage** : Messages temporaires sans disruption

**Exemple complet** :

```php
<?php
require_once 'config.php';
require_once 'helpers.php';

// Vérifier que l'utilisateur est admin
require_admin_connection();

// Valider les données
$email = sanitize_text($_POST['email']);
if (!validate_email($email)) {
    set_flash_message('Email invalide', 'error');
    header('Location: previous_page.php');
    exit();
}

// Tout est valide, on peut utiliser
$db->prepare("INSERT...")->execute([$email]);
?>
```

---

## 📄 Architecture des pages

### Pattern général d'une page

```php
<?php
// 1. INITIALISATION
session_start();
require_once 'config.php';
require_once 'helpers.php';

// 2. VÉRIFICATION ACCÈS
require_admin_connection();  // Si page admin

// 3. LOGIQUE MÉTIER
$data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traiter formulaire
    // Valider, insérer, rediriger
}

// 4. RÉCUPÉRER DONNÉES POUR AFFICHAGE
$creatures = $db->query("SELECT * FROM creatures")->fetchAll();
$flash = show_flash_and_clear();
?>
<!DOCTYPE html>
<html>
<body>
    <!-- 5. AFFICHAGE -->
    <?php foreach ($creatures as $c): ?>
        <div><?= htmlspecialchars($c['nom']) ?></div>
    <?php endforeach; ?>
</body>
</html>
```

**5 du pattern** :

1. Initialiser
2. Vérifier accès
3. Logique métier (POST handling)
4. Récupérer données (SELECT)
5. Affichage HTML

---

## 🔐 Couches de sécurité

### Niveau 1 : Validation input

```php
// USER INPUT (ne JAMAIS faire confiance)
$email = $_POST['email'];

// VALIDATION
if (!validate_email($email)) {
    // Rejeter
}

// SANITIZATION
$clean_email = sanitize_text($email);

// MAINTENANT on peut utiliser
```

### Niveau 2 : Protection SQL (PDO)

```php
// ❌ DANGEREUX (SQL injection possible)
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
$db->query($query);

// ✅ SÛR (Prepared statement)
$query = "SELECT * FROM users WHERE email = ?";
$db->prepare($query)->execute([$email]);
// PDO échappe automatiquement
```

### Niveau 3 : Protection Formulaires (CSRF)

```php
// Dans formulaire HTML
<form method="POST">
    <?php csrf_input(); ?>  <!-- Token caché -->
    <input name="data">
</form>

// Dans traitement
if (!verify_csrf_token($_POST['csrf_token'])) {
    // Rejeter formulaire
}
```

### Niveau 4 : Protection Uploads

```php
// Vérifications cascadantes :
1. Erreur upload serveur (UPLOAD_ERR_*)
2. Taille fichier (MAX_UPLOAD_SIZE)
3. Extension (.jpg, .png seulement)
4. MIME type (vrai type du fichier)
5. Renommer aléatoirement (éviter collisions)

// Result : impossible d'uploader malware
```

### Niveau 5 : Escaping Output

```php
// Avant d'afficher en HTML
$nom = $_POST['nom'];  // "Jean<script>alert()</script>"

// SANS escaping : script s'exécute ❌
echo $nom;

// AVEC escaping : affichage sûr ✅
echo htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
// Affiche : "Jean&lt;script&gt;alert()&lt;/script&gt;"
```

---

## 🗄️ Structure de la base de données

```sql
-- TABLE : utilisateurs (comptes admin)
CREATE TABLE utilisateurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_utilisateur VARCHAR(50),
    mot_de_passe VARCHAR(255),  -- bcrypt haché
    email VARCHAR(100),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- TABLE : creatures (les automates)
CREATE TABLE creatures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100),
    categorie VARCHAR(50),  -- Explorateur, Mécanicien, Colosse
    prix INT,
    description TEXT,       -- Longue description
    image_path VARCHAR(255), -- Chemin vers image (ex: images/1704123456_a1b2c3d4.jpg)
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- TABLE : commandes_speciales (demandes clients)
CREATE TABLE commandes_speciales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom_client VARCHAR(100),
    email_client VARCHAR(100),
    type_chimere VARCHAR(50),    -- Type de mécanisme demandé
    description_projet TEXT,     -- Description détaillée
    budget_estime INT,           -- Budget proposé
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Requêtes courantes** :

```sql
-- Récupérer toutes les créatures
SELECT * FROM creatures ORDER BY date_creation DESC;

-- Rechercher une créature
SELECT * FROM creatures WHERE nom LIKE '%robot%';

-- Créer une créature
INSERT INTO creatures (nom, categorie, prix, description, image_path)
VALUES (?, ?, ?, ?, ?);

-- Supprimer une créature
DELETE FROM creatures WHERE id = ?;

-- Récupérer commandes non traitées
SELECT * FROM commandes_speciales ORDER BY date_demande DESC;
```

---

## 🔄 Flux d'une action (exemple : créer créature)

### Scénario : Admin crée "Automate Gardien"

```
1. ACCÈS PAGE
   └─ admin.php load
   └─ require_admin_connection() ✅ (admin connecté)
   └─ Affiche formulaire avec token CSRF

2. UTILISATEUR REMPLIT FORMULAIRE
   ┌─ Nom: "Automate Gardien"
   ├─ Catégorie: "Sécurité"
   ├─ Prix: 750
   ├─ Description: "Robot de garde..."
   ├─ Image: automate.jpg (2MB)
   └─ Token CSRF: généré automatiquement

3. SUBMISSION (POST /admin.php)
   ├─ Récupérer $_POST['nom'] = "Automate Gardien"
   ├─ Vérifier CSRF: verify_csrf_token($_POST['csrf_token']) ✅
   ├─ Valider email: validate_positive_integer($prix) ✅
   ├─ Nettoyer: $nom = sanitize_text($nom, 100) ✅
   └─ Uploader image:
       ├─ Vérifier taille : 2MB < 5MB ✅
       ├─ Vérifier extension : .jpg ✅
       ├─ Vérifier MIME : image/jpeg ✅
       ├─ Renommer fichier : "1704123456_a1b2c3d4.jpg"
       └─ Déplacer vers images/

4. INSÉRER EN BASE DE DONNÉES
   ├─ Préparer query: "INSERT INTO creatures (...) VALUES (?, ?, ?, ?, ?)"
   ├─ Exécuter avec paramètres : [$nom, $cat, $prix, $desc, $image_path]
   └─ PDO échappe automatiquement ✅

5. FEEDBACK UTILISATEUR
   ├─ set_flash_message("Chimère créée !", "success")
   └─ header('Location: index.php')  // Redirection

6. AFFICHAGE RÉSULTAT
   ├─ index.php charge
   ├─ show_flash_and_clear() affiche message ✅
   ├─ SELECT creatures affiche la nouvelle
   └─ Utilisateur voit : message + nouvelle créature
```

---

## 📊 Diagramme des fichiers

```
FICHIERS PUBLICS (accessibles navigateur)
├─ index.php              → Accueil + grille créatures
├─ login.php              → Formulaire login
├─ logout.php             → Déconnexion
├─ details.php?id=1       → Détails créature
├─ contact.php            → Formulaire commande
├─ traitement_contact.php → Traitement (POST)
│
FICHIERS ADMIN PROTÉGÉS (besoin session admin)
├─ admin.php              → Création créatures
├─ commandes.php          → Affichage commandes
├─ delete.php             → Suppression créature (POST)
├─ delete_commande.php    → Suppression commande (POST)
│
FICHIERS SYSTEM (inclus partout)
├─ config.php             → Initialisation + constantes
├─ helpers.php            → Fonctions réutilisables
├─ connexion.php          → Wrapper legacy (deprecated)
├─ auth.php               → Vérification session (legacy)
│
DOSSIERS
├─ images/                → Images uploadées
├─ .git/                  → Historique git
└─ chimeres.sql           → Export base de données
```

---

## 🎯 Bonnes pratiques appliquées

| Pratique                        | Exemple dans le code                   | Avantage                 |
| ------------------------------- | -------------------------------------- | ------------------------ |
| **DRY** (Don't Repeat Yourself) | helpers.php regroupe fonctions         | Pas de duplication       |
| **Séparation responsabilités**  | config.php ≠ pages métier              | Code plus lisible        |
| **Prepared statements** (PDO)   | `$db->prepare()->execute()`            | SQL injection impossible |
| **Validation entrée**           | `validate_email()`                     | Données cohérentes       |
| **Escaping sortie**             | `htmlspecialchars()`                   | XSS impossible           |
| **Token CSRF**                  | `csrf_input()` + `verify_csrf_token()` | Formulaires safe         |
| **Gestion erreurs**             | try/catch autour BD                    | Pas d'exposition infos   |
| **Logging**                     | `error_log()`                          | Audit et debug           |
| **Constantes**                  | `define('MAX_...')`                    | Plus rapide + safe       |
| **Messages flash**              | `set_flash_message()`                  | UX fluide                |

---

## 🔍 Checklist pour l'examen oral

Pouvoir expliquer :

- ✅ **Architecture globale** : config → helpers → pages → BD
- ✅ **Pourquoi 5 couches de sécurité** : validation → sanitization → PDO → CSRF → escaping
- ✅ **Rôle config.php** : initialisation, constantes, connexion BD
- ✅ **Rôle helpers.php** : réutilisabilité, DRY principle
- ✅ **Flux créer créature** : formulaire → validation → upload → BD → affichage
- ✅ **Protection CSRF** : token généré → vérifié → rejeté si invalide
- ✅ **Protection uploads** : 4 vérifications (taille, extension, MIME, renommage)
- ✅ **SQL injection** : pourquoi PDO sauve la vie
- ✅ **XSS prevention** : htmlspecialchars() convertit < en &lt;
- ✅ **Session sécurisée** : regenerate après login, vérification stricte

---

## 💡 Exemples clés à retenir

### Exemple 1 : Comment fonctionne PDO

```php
// Avant (vulnérable)
$query = "SELECT * FROM users WHERE email = '" . $email . "'";
// Attaque: $email = "' OR '1'='1"
// Query devient: SELECT * FROM users WHERE email = '' OR '1'='1'
// → Retourne TOUS les users !

// Après (sûr)
$query = "SELECT * FROM users WHERE email = ?";
$stmt = $db->prepare($query);
$stmt->execute([$email]);
// PDO échappe tout automatiquement ✅
```

### Exemple 2 : Comment fonctionne CSRF token

```php
// 1. Page affiche formulaire
<?php csrf_input(); ?>  // Affiche: <input name="csrf_token" value="a1b2c3...">

// 2. Attaquant tente d'envoyer formulaire d'autre site
// - Il connait le nom du champ ("csrf_token")
// - Mais il NE connait PAS la valeur (aléatoire + en session)

// 3. Serveur vérifie
if (!verify_csrf_token($_POST['csrf_token'])) {
    // Attaque bloquée ! ✅
}
```

### Exemple 3 : Comment fonctionne messages flash

```php
// Page 1 : set_flash_message
set_flash_message("Succès !", "success");
header('Location: page2.php');
exit();

// Page 2 : show_flash_and_clear
$flash = show_flash_and_clear();
if ($flash['message']) {
    echo "<div class='alert-{$flash['type']}'>{$flash['message']}</div>";
}
// Message affiché UNE FOIS, puis supprimé de session

// Page 2 rechargée manuellement (F5)
$flash = show_flash_and_clear(); // $flash['message'] == null (déjà affichée)
```

---

## 🚀 Points clés pour la soutenance

**À pouvoir expliquer en 2-3 minutes** :

> "Mon app utilise une architecture en couches :
>
> - config.php centralise configuration et connexion BD
> - helpers.php regroupe les fonctions réutilisables
> - Chaque page métier suit le même pattern : initialiser → valider → traiter → afficher
> - 5 couches de sécurité : validation input → sanitization → PDO → CSRF → escaping output
> - Tout erreur est loggée et utilisateur reçoit message générique (pas exposition d'infos)
> - Messages flash pour feedback utilisateur sans disruption
> - Design pattern DRY (Don't Repeat Yourself) : pas de code en double"

---

**Vous comprenez le projet ? 🎉**  
→ Passer aux TESTS.md pour valider que tout marche !
