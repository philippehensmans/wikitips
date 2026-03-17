# News - Manuel d'utilisation

## Table des matières

1. [Introduction](#introduction)
2. [Fonctionnalités](#fonctionnalités)
3. [Architecture technique](#architecture-technique)
4. [Installation](#installation)
   - [Prérequis](#prérequis)
   - [Installation serveur](#installation-serveur)
   - [Installation extension Chrome](#installation-extension-chrome)
5. [Configuration](#configuration)
6. [Utilisation](#utilisation)
   - [Authentification](#authentification)
   - [Gestion des articles](#gestion-des-articles)
   - [Extension Chrome](#extension-chrome)
   - [Analyse IA](#analyse-ia)
   - [Administration](#administration)
7. [Intégration Bluesky](#intégration-bluesky)
   - [Configuration Bluesky](#configuration-bluesky)
   - [Partage manuel](#partage-manuel)
   - [Partage automatique](#partage-automatique)
8. [Newsletter](#newsletter)
   - [Configuration Mailchimp](#configuration-mailchimp)
   - [Inscription des abonnés](#inscription-des-abonnés)
   - [Envoi automatique (Cron)](#envoi-automatique-cron)
   - [Envoi via HTTP (cron-job.org)](#envoi-via-http-cron-joborg)
   - [Logs et suivi](#logs-et-suivi)
   - [Dépannage newsletter](#dépannage-newsletter)
9. [API REST](#api-rest)
10. [Sécurité](#sécurité)
11. [Personnalisation](#personnalisation)
12. [Dépannage](#dépannage)

---

## Introduction

**News** est une application web PHP permettant de collecter, publier et analyser rapidement des informations provenant du web. Elle intègre l'intelligence artificielle Claude d'Anthropic pour fournir une analyse automatique du contenu sous l'angle des droits humains.

### Objectifs

- Capturer facilement du contenu web via une extension Chrome
- Publier des articles avec un style visuel inspiré de Wikipedia
- Analyser automatiquement le contenu selon plusieurs perspectives :
  - Droits civils et politiques
  - Droits économiques, sociaux et culturels
  - Droit international humanitaire
- Permettre la collaboration entre plusieurs éditeurs

---

## Fonctionnalités

### Gestion de contenu

| Fonctionnalité | Description |
|----------------|-------------|
| Création d'articles | Formulaire complet avec titre, contenu, source, catégorie |
| Modification | Édition des articles existants |
| Catégorisation | Organisation par thématiques |
| Pages statiques | Pages éditables (accueil, à propos, etc.) |

### Intelligence artificielle

| Fonctionnalité | Description |
|----------------|-------------|
| Résumé automatique | Synthèse du contenu en quelques phrases |
| Extraction des points clés | Liste des informations principales |
| Analyse droits humains | Évaluation selon les conventions internationales |
| Recension automatique | Génération d'une recension journalistique structurée |
| Recommandations | Suggestions d'actions ou de réflexions |

### Utilisateurs

| Fonctionnalité | Description |
|----------------|-------------|
| Gestion des comptes | Création par les administrateurs uniquement |
| Connexion sécurisée | Sessions PHP avec hash bcrypt |
| Profil utilisateur | Modification des informations personnelles |
| Rôles | Administrateur et éditeur |

### Extension Chrome

| Fonctionnalité | Description |
|----------------|-------------|
| Capture de page | Extraction du titre et contenu |
| Menu contextuel | Clic droit pour analyser |
| Envoi direct | Transfert vers News en un clic |

### Partage social

| Fonctionnalité | Description |
|----------------|-------------|
| Partage WhatsApp | Bouton pour partager un article via WhatsApp |
| Partage Bluesky | Bouton pour partager un article sur Bluesky |
| Partage automatique | Option pour publier automatiquement à la création (Bluesky) |
| Personnalisation | Texte du post modifiable avant envoi |
| Carte de lien | Aperçu riche avec titre et description |

### Newsletter

| Fonctionnalité | Description |
|----------------|-------------|
| Inscription / désinscription | Formulaire public avec double opt-in via Mailchimp |
| Newsletter hebdomadaire | Récapitulatif automatique des articles de la semaine |
| Envoi cron CLI | Script `cron/send-newsletter.php` pour crontab serveur |
| Envoi cron HTTP | Endpoint `cron.php` pour services comme cron-job.org |
| Mode dry-run | Prévisualisation HTML sans envoi réel |
| Protection anti-doublons | Empêche l'envoi multiple dans la même semaine |
| Logs d'envoi | Historique de tous les envois en base de données |

---

## Architecture technique

### Technologies utilisées

| Composant | Technologie | Version |
|-----------|-------------|---------|
| Backend | PHP | 8.0+ |
| Base de données | SQLite | 3.x |
| API | REST/JSON | - |
| Frontend | HTML5/CSS3 | - |
| Extension | Chrome Manifest | V3 |
| IA | API Claude Anthropic | claude-sonnet-4-20250514 |

### Structure des fichiers

```
news/
│
├── config.php                 # Configuration principale
├── config.local.php           # Configuration locale (à créer)
├── .htaccess                  # Configuration Apache
│
├── index.php                  # Page d'accueil
├── article.php                # Affichage d'un article
├── articles.php               # Liste des articles
├── new.php                    # Création d'article
├── edit.php                   # Modification d'article
├── import.php                 # Import d'articles externes
├── edit-page.php              # Modification pages statiques
│
├── categories.php             # Liste des catégories
├── category.php               # Affichage d'une catégorie
├── search.php                 # Recherche d'articles
│
├── login.php                  # Page de connexion
├── logout.php                 # Déconnexion
├── profile.php                # Profil utilisateur
├── users.php                  # Gestion des utilisateurs (admin)
├── share-bluesky.php          # Partage sur Bluesky
├── newsletter.php             # Page d'inscription/désinscription newsletter
├── cron.php                   # Point d'entrée HTTP pour tâches cron
│
├── cron/
│   └── send-newsletter.php    # Script CLI d'envoi de newsletter
│
├── includes/
│   ├── Database.php           # Classe de gestion SQLite
│   ├── Article.php            # Modèle Article (CRUD)
│   ├── Category.php           # Modèle Catégorie
│   ├── Auth.php               # Gestion authentification
│   ├── Page.php               # Gestion pages statiques
│   ├── ClaudeService.php      # Intégration API Claude
│   ├── BlueskyService.php     # Intégration Bluesky (AT Protocol)
│   └── MailchimpService.php   # Intégration Mailchimp (newsletter)
│
├── api/
│   └── index.php              # Routeur API REST
│
├── templates/
│   └── layout.php             # Template principal Wikipedia
│
├── assets/
│   ├── css/
│   │   └── style.css          # Feuille de styles Wikipedia
│   └── images/
│       └── favicon.ico        # Icône du site
│
├── data/
│   ├── news.db            # Base de données SQLite (auto-créée)
│   └── .htaccess              # Protection du dossier
│
└── chrome-extension/
    ├── manifest.json          # Configuration extension
    ├── popup.html             # Interface popup
    ├── popup.js               # Logique popup
    ├── popup.css              # Styles popup
    ├── background.js          # Service worker
    ├── content.js             # Script d'extraction
    └── icons/
        ├── icon16.png
        ├── icon48.png
        └── icon128.png
```

### Schéma de la base de données

#### Table `articles`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire auto-incrémentée |
| title | TEXT | Titre de l'article |
| content | TEXT | Contenu complet |
| summary | TEXT | Résumé généré par Claude |
| source_url | TEXT | URL de la source originale |
| category_id | INTEGER | Clé étrangère vers categories |
| author_id | INTEGER | Clé étrangère vers users |
| created_at | DATETIME | Date de création |
| updated_at | DATETIME | Date de modification |

#### Table `categories`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| name | TEXT | Nom de la catégorie |
| slug | TEXT | Identifiant URL |

#### Table `users`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| username | TEXT | Nom d'utilisateur unique |
| email | TEXT | Adresse email unique |
| password | TEXT | Hash bcrypt du mot de passe |
| role | TEXT | 'admin' ou 'editor' |
| created_at | DATETIME | Date d'inscription |

#### Table `pages`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire |
| slug | TEXT | Identifiant unique (ex: 'home') |
| title | TEXT | Titre de la page |
| content | TEXT | Contenu HTML/Markdown |
| updated_at | DATETIME | Dernière modification |

#### Table `newsletter_logs`

| Colonne | Type | Description |
|---------|------|-------------|
| id | INTEGER | Clé primaire auto-incrémentée |
| campaign_id | TEXT | Identifiant de la campagne Mailchimp |
| article_count | INTEGER | Nombre d'articles inclus dans l'envoi |
| sent_at | DATETIME | Date et heure de l'envoi |
| status | TEXT | Statut : `sent`, `skipped` ou `error` |

---

## Installation

### Prérequis

#### Serveur web

- **PHP** 8.0 ou supérieur
- **Extensions PHP** requises :
  - `pdo_sqlite` - Accès base de données
  - `curl` - Appels API Claude
  - `json` - Traitement JSON
  - `gd` (optionnel) - Génération d'icônes
- **Serveur web** : Apache, Nginx ou équivalent
- **HTTPS** recommandé pour la production

#### Poste client

- **Google Chrome** ou navigateur compatible Chromium
- Accès au mode développeur des extensions

### Installation serveur

#### Étape 1 : Télécharger les fichiers

```bash
# Cloner le dépôt ou télécharger l'archive
git clone <url-du-depot> news

# Ou extraire l'archive
unzip news.zip -d /var/www/html/
```

#### Étape 2 : Configurer les permissions

```bash
# Le dossier doit être accessible en écriture pour SQLite
cd /var/www/html/news
chmod 755 .
chmod 755 data/  # Créer le dossier si nécessaire
```

#### Étape 3 : Créer la configuration locale

Créez le fichier `config.local.php` à la racine :

```php
<?php
/**
 * Configuration locale News
 * Ce fichier n'est pas versionné (ajoutez-le à .gitignore)
 */

// Clé API Anthropic (obligatoire pour l'analyse IA)
// Obtenez-la sur https://console.anthropic.com/
define('CLAUDE_API_KEY', 'sk-ant-api03-votre-cle-ici');

// Clé secrète pour sécuriser l'API REST
// Inventez une chaîne aléatoire complexe
define('API_SECRET_KEY', 'votre-cle-secrete-aleatoire-32-caracteres');

// Chemin de base (si installé dans un sous-répertoire)
// Décommentez et ajustez si nécessaire
// define('BASE_PATH', '/news');
```

#### Étape 4 : Vérifier l'installation

1. Accédez à `https://votresite.com/news/`
2. La base de données est créée automatiquement
3. Connectez-vous avec `admin` / `admin123`
4. **Changez immédiatement le mot de passe admin**

### Installation extension Chrome

#### Étape 1 : Configurer l'extension

Éditez le fichier `chrome-extension/popup.js` :

```javascript
// Ligne ~1-5 : Configurez l'URL de votre installation
const NEWS_URL = 'https://votresite.com/news';

// Ligne ~10 : Même clé que dans config.local.php
const API_KEY = 'votre-cle-secrete-aleatoire-32-caracteres';
```

#### Étape 2 : Charger l'extension

1. Ouvrez Chrome et accédez à `chrome://extensions/`
2. Activez le **Mode développeur** (interrupteur en haut à droite)
3. Cliquez sur **Charger l'extension non empaquetée**
4. Sélectionnez le dossier `chrome-extension/`
5. L'icône News apparaît dans la barre d'outils

#### Étape 3 : Épingler l'extension (optionnel)

1. Cliquez sur l'icône puzzle (extensions) dans Chrome
2. Cliquez sur l'épingle à côté de News

---

## Configuration

### Configuration principale (`config.php`)

Ce fichier contient les paramètres par défaut. **Ne le modifiez pas directement**, utilisez `config.local.php` pour surcharger les valeurs.

```php
<?php
// Paramètres par défaut
define('DB_PATH', __DIR__ . '/data/news.db');
define('CLAUDE_API_KEY', '');  // À définir dans config.local.php
define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
define('API_SECRET_KEY', '');  // À définir dans config.local.php

// Chargement de la configuration locale
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}
```

### Configuration locale (`config.local.php`)

Ce fichier contient vos paramètres spécifiques :

| Constante | Description | Exemple |
|-----------|-------------|---------|
| `CLAUDE_API_KEY` | Clé API Anthropic | `sk-ant-api03-xxx` |
| `API_SECRET_KEY` | Clé secrète API REST | `ma-cle-secrete-123` |
| `BASE_PATH` | Chemin si sous-répertoire | `/news` |
| `SITE_URL` | URL publique du site | `https://monsite.com` |
| `DB_PATH` | Chemin base de données | `/var/data/wiki.db` |
| `MAILCHIMP_API_KEY` | Clé API Mailchimp | `abc123-us21` |
| `MAILCHIMP_LIST_ID` | ID de l'audience Mailchimp | `a1b2c3d4e5` |
| `MAILCHIMP_FROM_NAME` | Nom d'expéditeur (défaut : SITE_NAME) | `News` |
| `MAILCHIMP_NEWSLETTER_TAG` | Tag pour cibler les abonnés (défaut : `newsletter-hebdo`) | `newsletter-hebdo` |
| `NEWSLETTER_DAY` | Jour d'envoi (défaut : `monday`) | `monday` |
| `CRON_SECRET_TOKEN` | Token pour les appels cron HTTP | `a1b2c3...` |

### Obtenir une clé API Anthropic

1. Créez un compte sur [console.anthropic.com](https://console.anthropic.com/)
2. Accédez à **API Keys**
3. Cliquez **Create Key**
4. Copiez la clé (elle ne sera plus affichée)
5. Ajoutez des crédits si nécessaire

---

## Utilisation

### Authentification

#### Première connexion

1. Accédez à la page d'accueil
2. Cliquez sur **Connexion**
3. Utilisez les identifiants par défaut :
   - Utilisateur : `admin`
   - Mot de passe : `admin123`
4. **Important** : Changez immédiatement le mot de passe via **Profil**

#### Créer un utilisateur (administrateurs uniquement)

Il n'y a pas d'inscription publique. Seuls les administrateurs peuvent créer des comptes :

1. Connectez-vous en tant qu'administrateur
2. Cliquez sur **Gérer les utilisateurs** dans le menu
3. Remplissez le formulaire de création :
   - Nom d'utilisateur (unique)
   - Adresse email (unique)
   - Mot de passe (min. 8 caractères)
   - Rôle : `editor` ou `admin`
4. Cliquez sur **Créer l'utilisateur**

#### Modifier son profil

1. Connectez-vous
2. Cliquez sur votre nom d'utilisateur dans l'en-tête
3. Modifiez vos informations :
   - Nom d'utilisateur
   - Adresse email
   - Mot de passe (nécessite le mot de passe actuel)

### Gestion des articles

#### Créer un article manuellement

1. Connectez-vous
2. Cliquez sur **Créer un article**
3. Remplissez le formulaire :
   - **Titre** : Titre de l'article
   - **URL source** : Lien vers la source originale
   - **Catégorie** : Sélectionnez ou créez une catégorie
   - **Contenu** : Corps de l'article
4. Optionnel : Cliquez sur **Analyser avec Claude** pour générer :
   - Un résumé
   - Les points principaux
   - L'analyse des droits humains
5. Cliquez sur **Enregistrer**

#### Modifier un article

1. Ouvrez l'article à modifier
2. Cliquez sur **Modifier** (visible si connecté)
3. Effectuez vos modifications
4. Cliquez sur **Enregistrer**

#### Supprimer un article

La suppression se fait directement en base de données (fonctionnalité admin à implémenter selon vos besoins).

### Extension Chrome

#### Capturer une page web

**Méthode 1 : Via le popup**

1. Naviguez vers la page à capturer
2. Cliquez sur l'icône News
3. Vérifiez le titre et l'URL affichés
4. Cliquez sur **Envoyer vers News**
5. News s'ouvre avec le formulaire pré-rempli

**Méthode 2 : Via le menu contextuel**

1. Faites un clic droit n'importe où sur la page
2. Sélectionnez **Analyser avec News**
3. News s'ouvre automatiquement

#### Résolution des problèmes de l'extension

| Problème | Solution |
|----------|----------|
| Icône grisée | Rechargez l'extension dans `chrome://extensions/` |
| "Clé API invalide" | Vérifiez que `API_KEY` est identique dans popup.js et config.local.php |
| Erreur de connexion | Vérifiez l'URL dans `NEWS_URL` |
| Contenu non capturé | Certains sites bloquent les scripts, copiez manuellement |

### Analyse IA

#### Fonctionnement

L'analyse Claude examine le contenu sous plusieurs angles :

1. **Résumé** : Synthèse concise du contenu
2. **Points principaux** : Liste des informations clés
3. **Analyse des droits humains** :
   - Droits civils et politiques (liberté d'expression, vie privée, etc.)
   - Droits économiques, sociaux et culturels (travail, santé, éducation)
   - Droit international humanitaire (si applicable)
4. **Recommandations** : Actions suggérées

#### Lancer une analyse

1. Dans le formulaire de création/modification
2. Cliquez sur **Analyser avec Claude**
3. Patientez quelques secondes
4. Les champs sont automatiquement remplis
5. Vous pouvez modifier le résultat avant d'enregistrer

#### Recension automatique

La recension est une synthèse journalistique générée automatiquement lors de la première consultation d'un article. Elle comprend :

- **Titre** accrocheur
- **Chapô** (introduction)
- **Sections** avec intertitres
- **Hashtags** pour le partage social
- **Compteur de signes** (environ 4000 signes)

La recension apparaît automatiquement dans la page article, juste après le résumé. Un bouton **Copier** permet de copier le texte pour le réutiliser ailleurs.

> **Note** : La recension est générée une seule fois et stockée en base de données.

### Administration

#### Modifier la page d'accueil

1. Connectez-vous en tant qu'administrateur
2. Accédez à la page d'accueil
3. Cliquez sur **Modifier cette page** (lien en bas)
4. Éditez le contenu HTML
5. Cliquez sur **Enregistrer**

#### Gérer les utilisateurs

Une interface dédiée permet aux administrateurs de gérer les utilisateurs :

1. Connectez-vous en tant qu'administrateur
2. Cliquez sur **Gérer les utilisateurs** dans le menu de navigation
3. Vous pouvez :
   - **Voir** la liste de tous les utilisateurs
   - **Créer** de nouveaux comptes (editor ou admin)
   - **Supprimer** des utilisateurs (sauf vous-même et le dernier admin)
   - **Changer le mot de passe** d'un utilisateur

> **Note** : Vous ne pouvez pas supprimer votre propre compte ni le dernier administrateur.

---

## Intégration Bluesky

News permet de partager automatiquement vos articles sur Bluesky, le réseau social décentralisé basé sur le protocole AT.

### Configuration Bluesky

#### Étape 1 : Créer un App Password

Pour des raisons de sécurité, Bluesky utilise des "App Passwords" plutôt que votre mot de passe principal.

1. Connectez-vous à [bsky.app](https://bsky.app)
2. Allez dans **Settings** (Paramètres)
3. Cliquez sur **App Passwords**
4. Cliquez sur **Add App Password**
5. Donnez un nom (ex: "News")
6. Copiez le mot de passe généré (il ne sera plus affiché)

#### Étape 2 : Configurer News

Ajoutez dans votre fichier `config.local.php` :

```php
<?php
// Configuration Bluesky
define('BLUESKY_IDENTIFIER', 'votre-handle.bsky.social'); // ou votre email
define('BLUESKY_APP_PASSWORD', 'xxxx-xxxx-xxxx-xxxx');    // App Password créé

// Optionnel : activer le partage automatique par défaut
define('BLUESKY_AUTO_SHARE', true);
```

| Paramètre | Description | Exemple |
|-----------|-------------|---------|
| `BLUESKY_IDENTIFIER` | Votre handle Bluesky ou email | `user.bsky.social` |
| `BLUESKY_APP_PASSWORD` | App Password (pas votre vrai mot de passe !) | `abcd-1234-efgh-5678` |
| `BLUESKY_AUTO_SHARE` | Cocher par défaut l'option de partage | `true` ou `false` |

### Partage manuel

Une fois Bluesky configuré, un bouton **🦋 Bluesky** apparaît sur chaque article.

#### Procédure

1. Ouvrez l'article que vous souhaitez partager
2. Cliquez sur le bouton **🦋 Bluesky** dans les actions
3. Une page de prévisualisation s'affiche avec :
   - Le texte du post (modifiable, max 300 caractères)
   - Un aperçu de l'article
4. Modifiez le texte si nécessaire
5. Cliquez sur **Publier sur Bluesky**
6. Vous êtes redirigé vers l'article avec un message de confirmation

#### Format du post

Le post généré automatiquement comprend :

```
📰 Titre de l'article

Résumé de l'article (jusqu'à 200 caractères)...

#DroitsHumains #News
```

Plus une **carte de lien** avec :
- Le titre de l'article
- Une description (extrait du résumé)
- L'URL vers l'article complet

### Partage automatique

Vous pouvez partager automatiquement chaque nouvel article publié sur Bluesky.

#### Option 1 : À la création de l'article

1. Lors de la création d'un article, une option **🦋 Partager sur Bluesky à la publication** apparaît
2. Cochez cette option
3. Sélectionnez le statut **Publié**
4. Cliquez sur **Créer l'article**
5. L'article est créé ET partagé sur Bluesky automatiquement

> **Note** : Le partage automatique ne fonctionne que si l'article est publié (pas en brouillon).

#### Option 2 : Activer par défaut

Pour que l'option soit cochée par défaut sur tous les nouveaux articles :

```php
define('BLUESKY_AUTO_SHARE', true);
```

### Limitations

| Aspect | Limite |
|--------|--------|
| Longueur du texte | 300 caractères maximum |
| Images | Non supportées (carte de lien uniquement) |
| Fréquence | Pas de limite côté News |

### Résolution des problèmes Bluesky

#### "Authentification Bluesky échouée"

**Causes possibles :**
- Handle incorrect (vérifiez l'orthographe)
- App Password expiré ou révoqué
- Utilisation du mot de passe principal au lieu de l'App Password

**Solution :**
1. Vérifiez que `BLUESKY_IDENTIFIER` correspond exactement à votre handle
2. Créez un nouvel App Password sur bsky.app
3. Mettez à jour `BLUESKY_APP_PASSWORD`

#### "Le bouton Bluesky n'apparaît pas"

**Cause :** Bluesky n'est pas configuré.

**Solution :** Vérifiez que `BLUESKY_IDENTIFIER` et `BLUESKY_APP_PASSWORD` sont définis dans `config.local.php`.

#### "Erreur lors de la publication"

**Causes possibles :**
- Texte trop long (> 300 caractères)
- Problème de connexion réseau
- API Bluesky temporairement indisponible

**Solution :**
1. Réduisez la longueur du texte
2. Réessayez plus tard
3. Vérifiez le status de Bluesky sur [status.bsky.app](https://status.bsky.app)

---

## Newsletter

News intègre un système de newsletter hebdomadaire via **Mailchimp**. Chaque semaine, un récapitulatif des articles publiés est envoyé automatiquement aux abonnés inscrits.

### Fonctionnement général

```
1. Les visiteurs s'inscrivent via le formulaire (newsletter.php)
2. Ils reçoivent un email de confirmation (double opt-in)
3. Le tag "newsletter-hebdo" leur est attribué dans Mailchimp
4. Chaque semaine, le cron récupère les articles récents
5. Une campagne Mailchimp est créée et envoyée aux abonnés tagués
6. L'envoi est enregistré dans la table newsletter_logs
```

### Configuration Mailchimp

#### Étape 1 : Créer un compte Mailchimp

1. Inscrivez-vous sur [mailchimp.com](https://mailchimp.com)
2. Créez une **audience** (liste de contacts)

#### Étape 2 : Obtenir la clé API

1. Connectez-vous à Mailchimp
2. Allez dans **Account > Extras > API keys**
3. Cliquez sur **Create A Key**
4. Copiez la clé (format : `xxxxxxxxxxxxxxxx-us21`)

> **Note** : Le suffixe après le tiret (ex: `us21`) est votre datacenter. Il est extrait automatiquement par l'application.

#### Étape 3 : Obtenir l'ID de l'audience

1. Allez dans **Audience > Settings > Audience name and defaults**
2. L'**Audience ID** est affiché en bas de la page

#### Étape 4 : Configurer News

Ajoutez dans votre fichier `config.local.php` :

```php
<?php
// Configuration Mailchimp (obligatoire pour la newsletter)
define('MAILCHIMP_API_KEY', 'votre-api-key-us21');
define('MAILCHIMP_LIST_ID', 'votre-audience-id');

// URL publique du site (obligatoire pour les liens dans la newsletter)
define('SITE_URL', 'https://votre-domaine.com');

// Token secret pour les appels cron HTTP (obligatoire si cron-job.org)
// Générez-le avec : php -r "echo bin2hex(random_bytes(32));"
define('CRON_SECRET_TOKEN', 'votre-token-secret');
```

#### Options avancées

```php
// Nom d'expéditeur (défaut : SITE_NAME)
define('MAILCHIMP_FROM_NAME', 'Mon Site News');

// Tag pour cibler les abonnés newsletter (défaut : 'newsletter-hebdo')
define('MAILCHIMP_NEWSLETTER_TAG', 'newsletter-hebdo');

// Jour d'envoi prévu (défaut : 'monday')
define('NEWSLETTER_DAY', 'monday');
```

| Paramètre | Description | Défaut |
|-----------|-------------|--------|
| `MAILCHIMP_API_KEY` | Clé API Mailchimp | *(vide — obligatoire)* |
| `MAILCHIMP_LIST_ID` | ID de l'audience | *(vide — obligatoire)* |
| `MAILCHIMP_FROM_NAME` | Nom d'expéditeur | Valeur de `SITE_NAME` |
| `MAILCHIMP_NEWSLETTER_TAG` | Tag pour cibler les abonnés | `newsletter-hebdo` |
| `NEWSLETTER_DAY` | Jour d'envoi | `monday` |
| `CRON_SECRET_TOKEN` | Token pour l'endpoint HTTP | *(vide — obligatoire si cron HTTP)* |

### Inscription des abonnés

La page `newsletter.php` fournit un formulaire public d'inscription et de désinscription.

#### Inscription

1. Le visiteur accède à la page newsletter
2. Il remplit le formulaire : email, prénom (optionnel), nom (optionnel)
3. Il clique sur **S'inscrire**
4. Un **email de confirmation** est envoyé (double opt-in)
5. Après confirmation, le tag `newsletter-hebdo` est ajouté au contact dans Mailchimp
6. L'abonné recevra les prochaines newsletters

#### Désinscription

1. Le visiteur accède à la page newsletter
2. Il entre son email dans le formulaire de désinscription
3. Le tag `newsletter-hebdo` est retiré (le contact reste dans l'audience Mailchimp)
4. Il ne recevra plus la newsletter

> **Note** : Seuls les contacts ayant le tag `newsletter-hebdo` reçoivent la newsletter. Il faut au moins un abonné avec ce tag pour que l'envoi fonctionne.

### Envoi automatique (Cron)

Le script `cron/send-newsletter.php` permet d'envoyer la newsletter via un cron serveur (CLI).

#### Configuration du crontab

```bash
# Envoyer la newsletter chaque lundi à 9h
0 9 * * 1 php /chemin/vers/news/cron/send-newsletter.php

# Exemple avec chemin complet vers PHP
0 9 * * 1 /usr/bin/php /var/www/html/news/cron/send-newsletter.php
```

#### Options en ligne de commande

| Option | Description | Exemple |
|--------|-------------|---------|
| `--dry-run` | Aperçu sans envoi (génère un fichier HTML de prévisualisation) | `php send-newsletter.php --dry-run` |
| `--days=N` | Couvrir les N derniers jours au lieu de 7 | `php send-newsletter.php --days=14` |
| `--force` | Envoyer même si une newsletter a déjà été envoyée cette semaine | `php send-newsletter.php --force` |

#### Exemples d'utilisation

```bash
# Test en dry-run (recommandé avant le premier envoi réel)
php /var/www/html/news/cron/send-newsletter.php --dry-run

# Envoi normal (articles des 7 derniers jours)
php /var/www/html/news/cron/send-newsletter.php

# Envoi couvrant les 14 derniers jours
php /var/www/html/news/cron/send-newsletter.php --days=14

# Forcer un renvoi même si déjà envoyée cette semaine
php /var/www/html/news/cron/send-newsletter.php --force
```

#### Comportement du script

1. Vérifie que Mailchimp est configuré
2. Vérifie qu'aucune newsletter n'a été envoyée dans les 7 derniers jours (sauf `--force`)
3. Récupère les articles publiés dans la période
4. S'il n'y a aucun article, enregistre un log `skipped` et s'arrête
5. Construit le HTML de la newsletter avec les articles et leurs catégories
6. En mode `--dry-run` : sauvegarde l'aperçu HTML dans `data/newsletter-preview.html`
7. En mode normal : crée une campagne Mailchimp, y place le contenu, et l'envoie
8. Enregistre le résultat dans `newsletter_logs` (statut `sent` ou `error`)

### Envoi via HTTP (cron-job.org)

Le fichier `cron.php` à la racine est un point d'entrée HTTP permettant de déclencher l'envoi depuis un service externe comme [cron-job.org](https://cron-job.org).

#### URL d'appel

```
https://votre-site.com/news/cron.php?action=newsletter&token=VOTRE_TOKEN
```

#### Paramètres

| Paramètre | Requis | Description |
|-----------|--------|-------------|
| `action` | Oui | Action à exécuter (`newsletter`) |
| `token` | Oui | Token secret (`CRON_SECRET_TOKEN`) |
| `days` | Non | Nombre de jours à couvrir (défaut : 7) |
| `force` | Non | Présence du paramètre = forcer l'envoi |

#### Exemples d'URL

```
# Envoi standard
https://votre-site.com/news/cron.php?action=newsletter&token=abc123

# Envoi couvrant 14 jours
https://votre-site.com/news/cron.php?action=newsletter&token=abc123&days=14

# Forcer l'envoi
https://votre-site.com/news/cron.php?action=newsletter&token=abc123&force
```

#### Sécurité

- Le token est vérifié avec `hash_equals()` (comparaison résistante aux attaques par timing)
- Si `CRON_SECRET_TOKEN` n'est pas défini, l'endpoint renvoie une erreur 500
- Si le token est invalide, l'endpoint renvoie une erreur 403

#### Configuration sur cron-job.org

1. Créez un compte sur [cron-job.org](https://cron-job.org)
2. Ajoutez un nouveau cron job avec l'URL ci-dessus
3. Programmez-le chaque lundi à 9h00 (ou selon votre préférence)
4. Activez les notifications par email pour surveiller le bon fonctionnement

### Logs et suivi

Chaque tentative d'envoi est enregistrée dans la table `newsletter_logs` :

| Statut | Signification |
|--------|---------------|
| `sent` | Newsletter envoyée avec succès |
| `skipped` | Aucun article trouvé pour la période |
| `error` | Erreur lors de l'envoi (voir les logs serveur) |

Le système empêche l'envoi de doublons : si une newsletter avec le statut `sent` existe dans les 7 derniers jours, l'envoi est bloqué (sauf avec `--force` ou `&force`).

### Contenu de la newsletter

La newsletter générée contient :

- Le **nom du site** en en-tête
- La **période couverte** (ex: 10/03 - 17/03/2026)
- La **liste des articles** publiés, chacun avec :
  - Son titre (lien cliquable vers l'article)
  - Sa catégorie
  - Son résumé (si disponible)
  - Sa date de publication
- Un **pied de page** avec un lien de désinscription

### Dépannage newsletter

#### "Mailchimp n'est pas configuré"

**Cause** : `MAILCHIMP_API_KEY` ou `MAILCHIMP_LIST_ID` est vide.

**Solution** : Ajoutez les valeurs dans `config.local.php` :
```php
define('MAILCHIMP_API_KEY', 'votre-api-key-us21');
define('MAILCHIMP_LIST_ID', 'votre-audience-id');
```

#### "SITE_URL pointe vers localhost"

**Cause** : En mode CLI, `SITE_URL` n'est pas défini et tombe sur `http://localhost:8080`. Le service Mailchimp bloque l'envoi pour éviter des liens cassés.

**Solution** : Définissez `SITE_URL` dans `config.local.php` :
```php
define('SITE_URL', 'https://votre-domaine.com');
```

Ou via variable d'environnement :
```bash
export SITE_URL=https://votre-domaine.com
```

#### "Une newsletter a déjà été envoyée cette semaine"

**Cause** : Une newsletter avec le statut `sent` existe dans les 7 derniers jours.

**Solution** : Utilisez `--force` (CLI) ou `&force` (HTTP) pour envoyer quand même.

#### "Le tag newsletter-hebdo n'existe pas"

**Cause** : Aucun abonné n'a encore le tag `newsletter-hebdo`. Le tag est créé automatiquement lors de la première inscription via le formulaire.

**Solution** : Inscrivez au moins un abonné via la page `newsletter.php` avant de lancer l'envoi.

#### "CRON_SECRET_TOKEN non configuré"

**Cause** : Le token n'est pas défini dans `config.local.php` (concerne uniquement l'appel HTTP via `cron.php`).

**Solution** :
```php
// Générez un token aléatoire
// php -r "echo bin2hex(random_bytes(32));"
define('CRON_SECRET_TOKEN', 'votre-token-genere');
```

#### "Token invalide" (erreur 403)

**Cause** : Le token passé dans l'URL ne correspond pas à `CRON_SECRET_TOKEN`.

**Solution** : Vérifiez que le paramètre `token` dans l'URL correspond exactement à la valeur définie dans `config.local.php`.

#### La newsletter est envoyée mais les liens sont cassés

**Cause** : `SITE_URL` et/ou `BASE_PATH` sont mal configurés.

**Solution** :
```php
define('SITE_URL', 'https://votre-domaine.com');
define('BASE_PATH', '/news');  // Si installé dans un sous-répertoire
```

---

## API REST

### Authentification

Toutes les requêtes POST nécessitent une clé API dans l'en-tête :

```
X-API-Key: votre-cle-secrete
```

### Endpoints

#### Vérifier l'état de l'API

```http
GET /api/?action=health
```

**Réponse :**
```json
{
  "status": "ok",
  "timestamp": "2024-01-15T10:30:00+00:00"
}
```

#### Lister les articles

```http
GET /api/?action=articles
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Titre de l'article",
      "summary": "Résumé...",
      "created_at": "2024-01-15 10:30:00"
    }
  ]
}
```

#### Créer un article

```http
POST /api/?action=articles
Content-Type: application/json
X-API-Key: votre-cle-secrete

{
  "title": "Titre",
  "content": "Contenu de l'article",
  "source_url": "https://exemple.com/source",
  "category_id": 1
}
```

#### Analyser du contenu

```http
POST /api/?action=analyze
Content-Type: application/json
X-API-Key: votre-cle-secrete

{
  "content": "Texte à analyser..."
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "summary": "Résumé du contenu...",
    "main_points": ["Point 1", "Point 2"],
    "human_rights_analysis": {
      "civil_political": "Analyse...",
      "economic_social_cultural": "Analyse...",
      "international_humanitarian": "Analyse...",
      "recommendations": ["Recommandation 1"]
    }
  }
}
```

#### Lister les catégories

```http
GET /api/?action=categories
```

---

## Sécurité

### Mesures implémentées

| Mesure | Description |
|--------|-------------|
| Hash bcrypt | Mots de passe hashés avec `password_hash()` |
| Sessions PHP | Authentification par session sécurisée |
| Clé API | Protection des endpoints POST |
| Validation | Nettoyage des entrées utilisateur |
| HTTPS | Recommandé pour la production |

### Recommandations

1. **Changez le mot de passe admin** dès la première connexion
2. **Utilisez HTTPS** en production
3. **Protégez `config.local.php`** :
   ```apache
   # .htaccess
   <Files "config.local.php">
       Require all denied
   </Files>
   ```
4. **Sauvegardez la base de données** régulièrement
5. **Limitez les permissions** du dossier `data/`

### Fichiers sensibles

| Fichier | Action |
|---------|--------|
| `config.local.php` | Bloquer l'accès HTTP |
| `data/news.db` | Bloquer l'accès HTTP |
| `.git/` | Bloquer l'accès HTTP |

---

## Personnalisation

### Modifier le style

Éditez `css/style.css` pour personnaliser l'apparence :

```css
/* Couleur principale */
:root {
    --wiki-blue: #0645ad;
    --wiki-visited: #0b0080;
}

/* En-tête */
.wiki-header {
    background: #f6f6f6;
    border-bottom: 1px solid #a7d7f9;
}
```

### Modifier le prompt Claude

Éditez `includes/ClaudeService.php` pour ajuster l'analyse :

```php
private function buildPrompt(string $content): string
{
    return "Analysez le contenu suivant...

    Votre prompt personnalisé ici...

    Contenu : {$content}";
}
```

### Ajouter des catégories

Les catégories sont créées automatiquement. Pour en ajouter manuellement :

```sql
INSERT INTO categories (name, slug) VALUES ('Nouvelle catégorie', 'nouvelle-categorie');
```

### Modifier le template

Éditez `templates/layout.php` pour modifier la structure des pages.

---

## Dépannage

### Problèmes courants

#### "Base de données non trouvée"

**Cause** : Le dossier `data/` n'existe pas ou n'est pas accessible en écriture.

**Solution** :
```bash
mkdir -p data
chmod 755 data
```

#### "Clé API Claude invalide"

**Cause** : La clé `CLAUDE_API_KEY` est incorrecte ou expirée.

**Solution** :
1. Vérifiez la clé sur [console.anthropic.com](https://console.anthropic.com/)
2. Assurez-vous qu'elle commence par `sk-ant-api03-`
3. Vérifiez les crédits disponibles

#### "Erreur 404 sur les liens"

**Cause** : Installation dans un sous-répertoire non configuré.

**Solution** : Ajoutez dans `config.local.php` :
```php
define('BASE_PATH', '/nom-du-sous-repertoire');
```

#### "Extension Chrome : icône manquante"

**Cause** : Les fichiers PNG n'existent pas.

**Solution** :
```bash
php generate-icons.php
```

#### "Erreur de connexion API : JSON invalide"

**Cause** : Le serveur ne supporte pas la réécriture d'URL.

**Solution** : Utilisez le format `?action=xxx` au lieu de `/api/xxx`

### Logs et debug

Pour activer le mode debug, ajoutez dans `config.local.php` :

```php
define('DEBUG_MODE', true);
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Support

Pour signaler un bug ou demander de l'aide :
1. Vérifiez d'abord cette documentation
2. Consultez les logs du serveur web
3. Ouvrez une issue sur le dépôt GitHub

---

## Licence

News est distribué sous licence MIT.

---

*Documentation mise à jour le 17 mars 2026*
