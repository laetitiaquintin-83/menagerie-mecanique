# 🧪 TESTS DE L'APPLICATION - Scénarios d'examen

**Version** : 2.0  
**Date** : 7 avril 2026

Ce document liste **tous les scénarios de test** à effectuer pour valider l'application.

---

## 📋 Checklist de PRÉPARATION

Avant de commencer les tests, vérifier :
- [ ] Base de données importée (`chimeres.sql`)
- [ ] Un utilisateur admin créé
- [ ] Laragon/XAMPP en marche
- [ ] Dossier `images/` existe et est writable
- [ ] Pas d'erreur PHP (vérifier error_log)

---

## 🔐 **TESTS 1-2 : AUTHENTIFICATION**

### Test 1.1 : Login valide
**Objective** : Utilisateur admin peut se connecter

**Étapes** :
1. Aller sur `login.php`
2. Rentrer username admin + password correct
3. Cliquer "DÉVERROUILLER"

**Résultat attendu** ✅ :
- Redirection vers `index.php`
- Bouton "Déconnexion" visible (en haut à droite)
- Message "Bienvenue, Maîtresse [nom]" visible
- Bouton "CONSULTER LE COURRIER" visible
- Bouton "Accéder à la Forge" visible

**Ce qu'on teste** : Authentification + session + redirection

---

### Test 1.2 : Login avec password faux
**Objective** : Rejet de password incorrect

**Étapes** :
1. Aller sur `login.php`
2. Rentrer username admin + password FAUX
3. Cliquer "DÉVERROUILLER"

**Résultat attendu** ✅ :
- Rester sur `login.php`
- Message d'erreur : "Accès refusé. Mécanisme bloqué."
- Pas de déconnexion précédente (message générique)

**Ce qu'on teste** :
- Rejet de wrong password
- Message d'erreur générique (prévention énumération)
- Pas de distinction "user existe ou non"

---

### Test 1.3 : Login avec username inexistant
**Objective** : Rejet d'utilisateur inexistant

**Étapes** :
1. Aller sur `login.php`
2. Rentrer username "inexistant_xyz" + any password
3. Cliquer "DÉVERROUILLER"

**Résultat attendu** ✅ :
- Rester sur `login.php`
- Message d'erreur : "Accès refusé. Mécanisme bloqué."
- Même message que test 1.2 (pas de différence)

**Ce qu'on teste** :
- Prévention de l'énumération d'utilisateurs
- Impossible de deviner prénoms d'admins

---

### Test 1.4 : Accès admin.php sans login
**Objective** : Page admin protégée

**Étapes** :
1. Ouvrir url directement : `admin.php`
2. Pas connecté

**Résultat attendu** ✅ :
- Redirection vers `login.php`
- Pas accès aux formulaires

**Ce qu'on teste** :
- Protection des pages admin
- Fonction `require_admin_connection()`

---

## 📤 **TESTS 3-5 : UPLOAD D'IMAGES**

### Test 3.1 : Upload image valide
**Objective** : Uploader une image JPG valide

**Étapes** :
1. Login en tant qu'admin
2. Cliquer "Accéder à la Forge"
3. Remplir formulaire :
   - Nom : "Automate Test"
   - Catégorie : "Explorateur"
   - Prix : 500
   - Description : "Test upload"
   - Image : choisir un fichier JPG/PNG valide
4. Cliquer "SCELLER LA CRÉATION"

**Résultat attendu** ✅ :
- Redirection vers `index.php`
- Message flash : "Chimère créée avec succès !"
- Nouvelle créature visible sur l'index
- Image affichée correctement
- Fichier image stocké dans `images/` avec nom aléatoire

**Ce qu'on teste** :
- Upload valide fonctionne
- CSRF token validé
- Fichier bien nommé (timestamp + random)
- Message flash affiché

---

### Test 3.2 : Upload image trop gros (> 5MB)
**Objective** : Refus fichier > 5MB

**Étapes** :
1. Rester sur admin (ou relister)
2. Créer un fichier TEST.jpg de 10MB
3. Essayer de l'uploader

**Résultat attendu** ❌ :
- Rester sur `admin.php`
- Message d'erreur : "Le fichier dépasse 5 MB"
- Pas d'enregistrement en base
- Pas de fichier stocké

**Ce qu'on teste** :
- MAX_UPLOAD_SIZE respecté
- Validation de taille côté serveur

---

### Test 3.3 : Upload fichier .exe ou .php
**Objective** : Refus fichier malveillant

**Étapes** :
1. Sur admin.php
2. Créer un fichier test.exe vide
3. Le renommer en "image.exe"
4. Essayer de l'uploader

**Résultat attendu** ❌ :
- Rester sur `admin.php`
- Message d'erreur : "Format non autorisé"
- Pas de fichier stocké en `images/`

**Ce qu'on teste** :
- MIME type check bloque .exe
- Extension check bloque .exe
- Sécurité des uploads

---

### Test 3.4 : Upload fichier JPG avec extension changée
**Objective** : Refus contournement d'extension

**Étapes** :
1. Prendre un vrai fichier image.jpg
2. Le renommer en "image.exe"
3. Essayer de l'uploader

**Résultat attendu** ❌ :
- Rester sur `admin.php`
- Message d'erreur : "Format non autorisé"

**Ce qu'on teste** :
- Extension check bloque .exe
- Même si MIME type est image/jpeg

---

### Test 3.5 : Upload sans token CSRF
**Objective** : Refus sans protection CSRF

**Étapes** :
1. Ouvrir DevTools (F12)
2. Sur `admin.php`, chercher `<input name="csrf_token">`
3. Modifier la valeur aléatoirement avant submit
4. Soumettre formulaire avec CSRF invalide

**Résultat attendu** ❌ :
- Rejecter avec message d'erreur
- Pas d'upload

**Ce qu'on teste** :
- Validation CSRF token
- Prévention CSRF attack

---

## ✉️ **TESTS 6-8 : FORMULAIRE CONTACT**

### Test 6.1 : Envoyer commande valide
**Objective** : Commande stockée en BD

**Étapes** :
1. Aller sur `contact.php`
2. Remplir formulaire :
   - Nom : "Jean Dupont"
   - Email : "jean@example.com"
   - Type : "Compagnon"
   - Projet : "Je veux un automate de voyage..."
   - Budget : 2000
3. Cliquer "SCELLER LA LETTRE"

**Résultat attendu** ✅ :
- Redirection vers `index.php`
- Message flash : "Votre commande a été déposée..."
- Enregistrement visible dans `commandes.php`

**Ce qu'on teste** :
- Formulaire contact fonctionne
- Validation données
- CSRF protection
- Message flash

---

### Test 6.2 : Commande avec email invalide
**Objective** : Validation email côté server

**Étapes** :
1. Sur contact.php
2. Rentrer email : "pas_un_email"
3. Soumettre

**Résultat attendu** ❌ :
- Rester sur `contact.php`
- Message d'erreur
- Pas d'enregistrement

**Ce qu'on teste** :
- Email validation
- Fonction `validate_email()`

---

### Test 6.3 : Commande avec type non-autorisé
**Objective** : Injection type bloquée

**Étapes** :
1. Devtools : modifier valeur `<select name="type">` en "HACK"
2. Soumettre formulaire

**Résultat attendu** ❌ :
- Rejecter la commande
- Message d'erreur
- Inspection SQL : type ne doit pas être "HACK"

**Ce qu'on teste** :
- Whitelist de types autorisés
- Prévention injection dans bases données

---

## 🗑️ **TESTS 9-10 : SUPPRESSION**

### Test 9.1 : Supprimer une créature (admin)
**Objective** : Suppression créature + image

**Étapes** :
1. Login admin
2. Sur `index.php`, cliquer 🗑️ "Démonter" une créature (admin only)
3. Confirmer dans popup

**Résultat attendu** ✅ :
- Créature disparaît de l'accueil
- Image supprimée de `images/`
- Message flash : "Chimère supprimée"
- Vérifier BD : id n'existe plus

**Ce qu'on teste** :
- Suppression créature
- Suppression image associée
- Fonction `delete_image_file()`

---

### Test 9.2 : Archiver une commande
**Objective** : Archivage commande

**Étapes** :
1. Login admin
2. Aller sur `commandes.php`
3. Cliquer 🗑️ "Classer l'affaire"
4. Confirmer

**Résultat attendu** ✅ :
- Commande disparaît
- Message flash : "Commande archivée avec succès"
- Vérifier BD : enregistrement supprimé

**Ce qu'on teste** :
- Suppression via POST (pas GET)
- CSRF token requis
- Message flash

---

## 🧬 **TESTS 11-12 : SÉCURITÉ SQL**

### Test 11.1 : SQL Injection sur recherche
**Objective** : PDO protège contre injection

**Étapes** :
1. Sur `index.php`
2. Recherche : `" OR 1=1 --`
3. Observer résultats

**Résultat attendu** ✅ :
- Traité comme texte littéral
- Recherche "` OR 1=1 --`" littéralement
- Pas de contourner la requête SQL

**Ce qu'on teste** :
- PDO prepared statements
- Protection contre SQL injection

---

### Test 11.2 : XSS Prevention (input malveillant)
**Objective** : Caractères spéciaux échappés

**Étapes** :
1. Sur contact.php
2. Nom : `<script>alert('hack')</script>`
3. Soumettre
4. Vérifier dans commandes.php

**Résultat attendu** ✅ :
- `<script>` affiché comme texte, pas exécuté
- Affichage : `&lt;script&gt;alert('hack')&lt;/script&gt;`

**Ce qu'on teste** :
- Fonction `htmlspecialchars()`
- Protection XSS

---

## ✔️ **TESTS 13-14 : MESSAGES FLASH**

### Test 13.1 : Flash message s'affiche une fois
**Objective** : Message flash disparaît après premier affichage

**Étapes** :
1. Soumettre contact valide
2. Voir message flash sur index
3. Rafraîchir la page F5

**Résultat attendu** ✅ :
- Message visible après redirection
- Message DISPARU après F5

**Ce qu'on teste** :
- Fonction `show_flash_and_clear()`
- Message unique pour un affichage

---

### Test 13.2 : Flash messages multiples types
**Objective** : Types d'erreurs différentes

**Étapes** :
1. Success : créer créature → voir flash vert
2. Error : uploader trop gros → voir flash rouge
3. Info : autre action

**Résultat attendu** ✅ :
- Couleurs différentes par type
- Style adapté : green=success, red=error

**Ce qu'on teste** :
- Gestion types messages
- Affichage CSS par type

---

## 🔄 **TESTS 15-16 : ACCÈS PAGES**

### Test 15.1 : Page details.php avec ID valide
**Objective** : Affichage détails créature

**Étapes** :
1. index.php → cliquer créature
2. ou accéder directement `details.php?id=1`

**Résultat attendu** ✅ :
- Page charge correctement
- Image, nom, description, prix visibles
- Bouton "PASSER COMMANDE" accessible

**Ce qu'on teste** :
- Récupération créature par ID
- Affichage détails

---

### Test 15.2 : details.php avec ID invalide
**Objective** : Protection énumération

**Étapes** :
1. Accéder `details.php?id=99999` (n'existe pas)
2. ou `details.php?id=abc` (pas un nombre)

**Résultat attendu** ✅ :
- Redirection vers `index.php`
- Pas d'erreur affichée
- Pas de révélation "ID inexistant"

**Ce qu'on teste** :
- Validation ID
- Prévention énumération
- Fonction `validate_positive_integer()`

---

## 📊 **TESTS 17-18 : RECHERCHE ET FILTRE**

### Test 17.1 : Recherche par nom
**Objective** : Recherche fonctionne

**Étapes** :
1. index.php
2. Recherche : "Explorateur" (ou partie du nom)
3. Observer résultats

**Résultat attendu** ✅ :
- Seules créatures contenant le mot affichées
- Les autres cachées

**Ce qu'on teste** :
- Requête LIKE sécurisée (PDO)
- Filtre par nom

---

### Test 17.2 : Filtre par catégorie
**Objective** : Filtre catégorie fonctionne

**Étapes** :
1. index.php
2. Cliquer "Explorateurs"
3. Observer résultats

**Résultat attendu** ✅ :
- Seulement créatures "Explorateur"
- Les autres filtrées

**Ce qu'on teste** :
- Filtre par catégorie
- SELECT WHERE catégorie

---

## 🧪 **RÉSUMÉ FINAL**

**Total tests** : 18 scénarios  
**Couverture** :
- ✅ Authentification : 4 tests
- ✅ Uploads : 5 tests
- ✅ Formulaires : 3 tests
- ✅ Suppression : 2 tests
- ✅ Sécurité : 2 tests
- ✅ Messages : 2 tests
- ✅ Accès : 2 tests

**Tous les tests réussis ? 🎉**
→ Application prête pour l'examen !

---

## 🎓 **DÉFENSE ORALE** - Points clés à pouvoir expliquer

1. **Pourquoi PDO ?** → SQL injection, prepared statements
2. **Pourquoi CSRF tokens ?** → Protection formulaires cross-site
3. **Pourquoi valider MIME ET extension ?** → Sécurité uploads
4. **Comment marche session ?** → Regenerate après login, validationstrict
5. **Pourquoi messages génériques login ?** → Prévention énumération users
6. **Comment sanitize les inputs ?** → htmlspecialchars, trim, limites
7. **Pourquoi centraliser config.php ?** → DRY, maintenance, sécurité

---

**Bonne chance pour l'examen ! 🚀**
