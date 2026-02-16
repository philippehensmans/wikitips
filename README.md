# News - Veille Droits Humains

Application PHP de publication rapide d'informations avec analyse automatique sous l'angle des droits humains grâce à Claude AI.

## Fonctionnalités

- **Analyse automatique** : Le contenu est analysé par Claude AI pour identifier les points d'attention concernant les droits humains
- **Interface style Wikipedia** : Design épuré et familier pour une navigation facile
- **Extension Chrome** : Capturez du contenu depuis n'importe quelle page web en un clic
- **Import manuel** : Collez du texte ou des URLs pour les analyser
- **Édition complète** : Modifiez les articles avant publication
- **Catégorisation** : Articles classés par domaines des droits humains

## Catégories d'analyse

- **Droits civils et politiques** : libertés fondamentales, droit de vote, liberté d'expression
- **Droits économiques, sociaux et culturels** : droit au travail, à la santé, à l'éducation
- **Droit international humanitaire** : Conventions de Genève, protection des civils
- **Droits des réfugiés** : Convention de 1951, protection internationale
- **Droits des enfants** : Convention des droits de l'enfant
- **Droits des femmes** : CEDAW, égalité des genres
- **Non-discrimination** : égalité, lutte contre les discriminations

## Installation

### Prérequis

- PHP 8.0+ avec extensions SQLite et cURL
- Serveur web Apache ou nginx
- Clé API Anthropic (Claude)

### Configuration

1. Clonez le repository :
```bash
git clone <repository-url>
cd news
```

2. Configurez votre clé API Claude :
```bash
# Option 1 : Variable d'environnement (recommandé)
export CLAUDE_API_KEY="votre-clé-api"

# Option 2 : Modifier config.php
# Remplacez 'YOUR_API_KEY_HERE' par votre clé
```

3. Configurez la clé secrète pour l'API (extension Chrome) :
```bash
export API_SECRET_KEY="votre-clé-secrète"
```

4. Démarrez le serveur PHP :
```bash
php -S localhost:8080
```

5. Ouvrez http://localhost:8080 dans votre navigateur

### Structure des fichiers

```
news/
├── api/                    # API REST
│   └── index.php
├── assets/
│   ├── css/style.css       # Styles Wikipedia-like
│   └── js/app.js           # JavaScript client
├── chrome-extension/       # Extension Chrome
│   ├── manifest.json
│   ├── popup.html/js
│   ├── background.js
│   └── content.js
├── data/                   # Base de données SQLite (créée automatiquement)
├── includes/               # Classes PHP
│   ├── Article.php
│   ├── Category.php
│   ├── ClaudeService.php
│   └── Database.php
├── templates/
│   └── layout.php          # Template principal
├── config.php              # Configuration
├── index.php               # Page d'accueil
├── article.php             # Affichage article
├── articles.php            # Liste des articles
├── categories.php          # Liste des catégories
├── category.php            # Affichage catégorie
├── edit.php                # Édition d'article
├── import.php              # Import et analyse
├── new.php                 # Création d'article
└── search.php              # Recherche
```

## Extension Chrome

### Installation

1. Ouvrez Chrome et allez à `chrome://extensions/`
2. Activez le "Mode développeur"
3. Cliquez sur "Charger l'extension non empaquetée"
4. Sélectionnez le dossier `chrome-extension`

### Configuration

1. Cliquez sur l'icône de l'extension
2. Entrez l'URL de votre serveur News (ex: `http://localhost:8080`)
3. Entrez votre clé API secrète (définie dans `config.php`)

### Utilisation

**Méthode 1 : Popup**
1. Sélectionnez du texte sur une page web
2. Cliquez sur l'icône de l'extension
3. Cliquez sur "Capturer la sélection"
4. Cliquez sur "Analyser et envoyer"

**Méthode 2 : Menu contextuel**
1. Sélectionnez du texte sur une page web
2. Clic droit → "Analyser avec News"

## API REST

### Endpoints

```
GET    /api/articles          # Liste des articles
GET    /api/articles/:id      # Détail d'un article
POST   /api/articles          # Créer un article
PUT    /api/articles/:id      # Modifier un article
DELETE /api/articles/:id      # Supprimer un article

GET    /api/categories        # Liste des catégories
GET    /api/categories/:slug  # Détail d'une catégorie

POST   /api/analyze           # Analyser du contenu via Claude
```

### Exemple d'appel API

```bash
curl -X POST http://localhost:8080/api/analyze \
  -H "Content-Type: application/json" \
  -H "X-API-Key: votre-clé-secrète" \
  -d '{
    "content": "Texte à analyser...",
    "source_url": "https://example.com/article",
    "create_article": true
  }'
```

## Licence

MIT License

## Notes

- Les analyses sont générées par IA et doivent être vérifiées avant publication
- La base de données SQLite est créée automatiquement au premier accès
- Les catégories sont pré-créées au démarrage
