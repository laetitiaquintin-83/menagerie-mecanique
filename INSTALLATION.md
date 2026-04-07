# 📥 INSTALLATION - Guide complet

**Durée** : 10-15 minutes  
**Difficulté** : Facile

---

## ✅ Prérequis

Avant de commencer, installer :

| Logiciel | Version | Lien |
|----------|---------|------|
| **Laragon** ou **XAMPP** | Récente | https://laragon.org |
| **PHP** | 7.4+ | Inclus dans Laragon |
| **MySQL** | 5.7+ | Inclus dans Laragon |
| **Git** (optionnel) | Récent | https://git-scm.com |

**Vérifier que ça fonctionne** :
```bash
# Ouvrir terminal et taper:
php -v          # Doit afficher PHP 7.4+
mysql --version # Doit afficher MySQL 5.7+
```

---

## 📦 ÉTAPE 1 : Récupérer le projet

### Option A : Via Git (recommandé)
```bash
cd C:\laragon\www
git clone https://github.com/votre-username/Menagerie_Mecanique.git
cd Menagerie_Mecanique
```

### Option B : Télécharger le ZIP
1. Cliquer "Code" → "Download ZIP"
2. Extraire dans `C:\laragon\www\`
3. Renommer dossier en `Menagerie_Mecanique`

### Vérifier structure
```
C:\laragon\www\Menagerie_Mecanique\
├── config.php           ✅ Configuration centrale
├── helpers.php          ✅ Fonctions réutilisables
├── index.php            ✅ Accueil
├── login.php            ✅ Page identification
├── admin.php            ✅ Forge (création créatures)
├── contact.php          ✅ Formulaire commandes
├── commandes.php        ✅ Page admin commandes
├── details.php          ✅ Détails créature
├── delete.php           ✅ Suppression créature
├── delete_commande.php  ✅ Suppression commande
├── traitement_contact.php
├── logout.php
├── images/              ✅ Dossier uploads
├── chimeres.sql         ✅ Base de données
├── TESTS.md             ✅ Scénarios de test
├── INSTALLATION.md      ✅ Ce fichier
└── ARCHITECTURE.md      ✅ Explications architecture
```

---

## 🗄️ ÉTAPE 2 : Créer la base de données

### 2.1 : Ouvrir Laragon

**Laragon** (recommandé):
1. Cliquer "Start" pour lancer serveurs
2. Cliquer "MySQL" → "Open Terminal"
3. Tapez : `mysql -u root` (Enter)

```bash
# Vous êtes dans MySQL console
mysql>
```

### 2.2 : Créer la base de données

```sql
-- Créer la BD
CREATE DATABASE atelier_chimeres;

-- Utiliser la BD
USE atelier_chimeres;

-- Importer les tables
-- Copier-coller tout le contenu de chimeres.sql
```

**Ou directement importer le fichier** :
```bash
mysql -u root atelier_chimeres < chimeres.sql
```

### 2.3 : Vérifier la BD

```sql
-- Voir les tables créées
SHOW TABLES;

-- Voir les colonnes de creatures
DESCRIBE creatures;

-- Voir un échantillon de créatures
SELECT * FROM creatures LIMIT 3;
```

**Résultat attendu** ✅ :
```
5 tables : utilisateurs, creatures, commandes_speciales, ...
```

---

## 👤 ÉTAPE 3 : Créer un utilisateur admin

### 3.1 : Générer un password haché

En terminal PHP :
```bash
php -r "echo password_hash('admin123', PASSWORD_BCRYPT);"
```

**Résultat** (exemple) :
```
$2y$10$rK7Zd.KL9JM2jH5x8pQ4ZOZ5s.6t.9w.2m.1c.4v.7b.0d.3f
```

### 3.2 : Insérer l'utilisateur

Dans MySQL :
```sql
USE atelier_chimeres;

INSERT INTO utilisateurs (nom_utilisateur, mot_de_passe, email) VALUES (
    'admin',
    '$2y$10$rK7Zd.KL9JM2jH5x8pQ4ZOZ5s.6t.9w.2m.1c.4v.7b.0d.3f',
    'admin@atelier.local'
);

-- Vérifier
SELECT * FROM utilisateurs;
```

**Identifiants de login** :
- Username : `admin`
- Password : `admin123`

---

## 🚀 ÉTAPE 4 : Démarrer l'application

### 4.1 : Lancer les serveurs Laragon

1. Ouvrir Laragon
2. Cliquer "Start"

**Vérifier** :
- Apache : 🟢 (vert)
- MySQL : 🟢 (vert)

### 4.2 : Accéder l'application

1. Ouvrir navigateur
2. Aller à : `http://localhost/Menagerie_Mecanique`
3. Vous devez voir la page d'accueil

---

## 🧪 ÉTAPE 5 : Tester le login

### 5.1 : Accès admin

1. Cliquer 🗝️ "Accès" (en haut à droite)
2. Rentrer :
   - Username : `admin`
   - Password : `admin123`
3. Cliquer "DÉVERROUILLER"

**Résultat attendu** ✅ :
- Redirection vers accueil
- Message : "Bienvenue, Maîtresse admin"
- Boutons admin visibles

### 5.2 : Si erreur de connection

**Erreur** : "Erreur de connexion à la base de données"

**Solutions** :
1. Vérifier MySQL est lancé (Laragon Start)
2. Vérifier `config.php` :
   - DB_HOST = `localhost` ✅
   - DB_NAME = `atelier_chimeres` ✅
   - DB_USER = `root` ✅
   - DB_PASS = `` (vide) ✅

3. Vérifier la BD existe :
   ```bash
   mysql -u root
   SHOW DATABASES;  # Doit avoir 'atelier_chimeres'
   ```

---

## 📁 ÉTAPE 6 : Configuration de sakaurity (optionnel)

### 6.1 : Créer .htaccess

Créer fichier `.htaccess` à la racine du projet :

```apache
<FilesMatch "config\.php$|\.git|\.env">
    Deny from all
</FilesMatch>

php_flag display_errors Off
php_flag log_errors On
php_value error_log /var/log/php_errors.log
```

### 6.2 : Protéger dossier images

Créer `.htaccess` dans `images/` :

```apache
<FilesMatch "\.php$|\.html$|\.js$">
    Deny from all
</FilesMatch>
```

---

## ✅ CHECKLIST FINALE

- [ ] Laragon/XAMPP en marche
- [ ] Base de données `atelier_chimeres` créée
- [ ] Utilisateur `admin` créé avec password hasché
- [ ] Dossier `images/` accessible et writable
- [ ] Application accessible via `http://localhost/Menagerie_Mecanique`
- [ ] Login fonctionne (admin/admin123)
- [ ] Pages admin accessibles
- [ ] Pas d'erreur PHP (vérifier logs)

---

## 🐛 DÉPANNAGE

### Problème : Port 80 déjà utilisé

**Symptôme** : Apache refuse de démarrer

**Solution** :
```bash
netstat -ano | findstr :80  # Voir qui utilise le port
taskkill /PID 1234 /F       # Tuer le process
```

---

### Problème : MySQL crash sur création BD

**Symptôme** : Erreur "Can't create database"

**Solution** :
1. Vérifier que MySQL est bien lancé
2. Vérifier les permissions sur `C:\laragon\data\mysql`

---

### Problème : Fichiers CSS/images ne chargent pas

**Symptôme** : Pas de style, images cassées

**Solution** :
1. Vérifier chemins relatifs dans les fichiers
2. Vérifier dossiers existent :
   - `/images/` existe ✅
   - `.git/` existe (si cloné) ✅

---

## 📚 Prochaines étapes

Une fois l'installation réussie :

1. **Lire ARCHITECTURE.md** pour comprendre structure
2. **Exécuter tests dans TESTS.md** pour valider
3. **Consulter les commentaires** dans `config.php` et `helpers.php`
4. **Pratiquer** en créant créatures, commandes, etc.

---

## 💬 Besoin d'aide ?

- Consulter les commentaires dans les fichiers `.php`
- Lire `ARCHITECTURE.md` pour explications
- Vérifier `error_log` sur le serveur
- Chercher l'erreur exact dans Devtools (F12)

---

**Installation terminée ? 🎉**  
→ Passer aux TESTS.md et ARCHITECTURE.md !
