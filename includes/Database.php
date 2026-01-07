<?php
/**
 * Classe de gestion de la base de données SQLite
 */
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbDir = dirname(DB_PATH);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . DB_PATH);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->initTables();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO {
        return $this->pdo;
    }

    private function initTables(): void {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                source_url TEXT,
                source_content TEXT,
                summary TEXT,
                main_points TEXT,
                human_rights_analysis TEXT,
                content TEXT,
                status TEXT DEFAULT 'draft',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT UNIQUE NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                description TEXT
            )
        ");

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS article_categories (
                article_id INTEGER,
                category_id INTEGER,
                PRIMARY KEY (article_id, category_id),
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )
        ");

        // Insérer les catégories par défaut si elles n'existent pas
        $defaultCategories = [
            ['Droits civils et politiques', 'droits-civils-politiques', 'Libertés fondamentales, droit de vote, liberté d\'expression...'],
            ['Droits économiques et sociaux', 'droits-economiques-sociaux', 'Droit au travail, à la santé, à l\'éducation...'],
            ['Droits culturels', 'droits-culturels', 'Droit à la culture, aux pratiques culturelles...'],
            ['Droit international humanitaire', 'droit-humanitaire', 'Conventions de Genève, protection des civils...'],
            ['Droits des réfugiés', 'droits-refugies', 'Convention de 1951, protection internationale...'],
            ['Droits des enfants', 'droits-enfants', 'Convention des droits de l\'enfant...'],
            ['Droits des femmes', 'droits-femmes', 'CEDAW, égalité des genres...'],
            ['Non-discrimination', 'non-discrimination', 'Égalité, lutte contre les discriminations...']
        ];

        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            $stmt->execute($cat);
        }
    }
}
