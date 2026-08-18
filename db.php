<?php
/**
 * Database connection and self-applying schema.
 *
 * There is no migrate step: the schema below is created if missing and patched
 * on every request through guarded ALTERs, exactly like the tobwai codebase.
 * SQLite is used locally, MySQL in production; only the primary-key syntax and
 * the table suffix differ, so one set of DDL covers both.
 */

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'sqlite') {
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
    } else {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        $pdo->exec("SET time_zone = '+07:00'");
    }

    db_migrate($pdo);
    return $pdo;
}

function db_is_sqlite(): bool
{
    return DB_DRIVER === 'sqlite';
}

/** Rewrite the portable DDL tokens for the driver in use. */
function ddl(string $sql): string
{
    if (db_is_sqlite()) {
        return str_replace(
            ['{PK}', '{SUFFIX}', '{LONGTEXT}'],
            ['INTEGER PRIMARY KEY AUTOINCREMENT', '', 'TEXT'],
            $sql
        );
    }
    return str_replace(
        ['{PK}', '{SUFFIX}', '{LONGTEXT}'],
        [
            'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            'LONGTEXT',
        ],
        $sql
    );
}

function table_exists(PDO $pdo, string $table): bool
{
    try {
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    try {
        $pdo->query("SELECT `$column` FROM `$table` LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/** Add a column only when it is not already there. */
function add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (table_exists($pdo, $table) && !column_exists($pdo, $table, $column)) {
        try {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        } catch (PDOException $e) {
            // A parallel request may have added it a moment ago.
        }
    }
}

function db_migrate(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $tables = [

        'users' => "CREATE TABLE IF NOT EXISTS users (
            id {PK},
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'editor',
            avatar VARCHAR(255) DEFAULT NULL,
            phone VARCHAR(40) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            last_login_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'settings' => "CREATE TABLE IF NOT EXISTS settings (
            k VARCHAR(80) NOT NULL PRIMARY KEY,
            v {LONGTEXT}
        ){SUFFIX}",

        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id {PK},
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active'
        ){SUFFIX}",

        'services' => "CREATE TABLE IF NOT EXISTS services (
            id {PK},
            title VARCHAR(160) NOT NULL,
            slug VARCHAR(180) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            description TEXT,
            icon VARCHAR(60) DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published'
        ){SUFFIX}",

        'albums' => "CREATE TABLE IF NOT EXISTS albums (
            id {PK},
            type VARCHAR(20) NOT NULL DEFAULT 'photo',
            title VARCHAR(200) NOT NULL,
            slug VARCHAR(220) NOT NULL,
            description TEXT,
            category_id INT DEFAULT NULL,
            cover_photo_id INT DEFAULT NULL,
            cover_image VARCHAR(255) DEFAULT NULL,
            event_date DATE DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            access VARCHAR(20) NOT NULL DEFAULT 'public',
            access_code VARCHAR(60) DEFAULT NULL,
            allow_download INT NOT NULL DEFAULT 1,
            is_featured INT NOT NULL DEFAULT 0,
            views INT NOT NULL DEFAULT 0,
            downloads INT NOT NULL DEFAULT 0,
            shares INT NOT NULL DEFAULT 0,
            saves INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'folders' => "CREATE TABLE IF NOT EXISTS folders (
            id {PK},
            album_id INT NOT NULL,
            name VARCHAR(160) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'photos' => "CREATE TABLE IF NOT EXISTS photos (
            id {PK},
            album_id INT NOT NULL,
            folder_id INT DEFAULT NULL,
            filename VARCHAR(255) NOT NULL,
            orig_name VARCHAR(255) NOT NULL,
            ext VARCHAR(10) NOT NULL DEFAULT 'jpg',
            mime VARCHAR(80) NOT NULL DEFAULT 'image/jpeg',
            bytes INT NOT NULL DEFAULT 0,
            width INT NOT NULL DEFAULT 0,
            height INT NOT NULL DEFAULT 0,
            caption VARCHAR(255) DEFAULT NULL,
            taken_at DATETIME DEFAULT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            downloads INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'videos' => "CREATE TABLE IF NOT EXISTS videos (
            id {PK},
            album_id INT DEFAULT NULL,
            title VARCHAR(200) NOT NULL,
            provider VARCHAR(20) NOT NULL DEFAULT 'youtube',
            url VARCHAR(500) DEFAULT NULL,
            filename VARCHAR(255) DEFAULT NULL,
            thumb VARCHAR(255) DEFAULT NULL,
            duration VARCHAR(20) DEFAULT NULL,
            bytes INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'articles' => "CREATE TABLE IF NOT EXISTS articles (
            id {PK},
            title VARCHAR(220) NOT NULL,
            slug VARCHAR(240) NOT NULL,
            excerpt TEXT,
            body {LONGTEXT},
            cover VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            views INT NOT NULL DEFAULT 0,
            published_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'reviews' => "CREATE TABLE IF NOT EXISTS reviews (
            id {PK},
            name VARCHAR(120) NOT NULL,
            role VARCHAR(120) DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            rating INT NOT NULL DEFAULT 5,
            body TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'faqs' => "CREATE TABLE IF NOT EXISTS faqs (
            id {PK},
            question VARCHAR(255) NOT NULL,
            answer TEXT,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published'
        ){SUFFIX}",

        'banners' => "CREATE TABLE IF NOT EXISTS banners (
            id {PK},
            title VARCHAR(200) DEFAULT NULL,
            subtitle VARCHAR(255) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            link VARCHAR(500) DEFAULT NULL,
            position VARCHAR(40) NOT NULL DEFAULT 'hero',
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'published'
        ){SUFFIX}",

        'pages' => "CREATE TABLE IF NOT EXISTS pages (
            id {PK},
            slug VARCHAR(120) NOT NULL,
            title VARCHAR(220) NOT NULL,
            body {LONGTEXT},
            meta_description VARCHAR(300) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'menus' => "CREATE TABLE IF NOT EXISTS menus (
            id {PK},
            label VARCHAR(120) NOT NULL,
            url VARCHAR(300) NOT NULL,
            location VARCHAR(40) NOT NULL DEFAULT 'header',
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active'
        ){SUFFIX}",

        'messages' => "CREATE TABLE IF NOT EXISTS messages (
            id {PK},
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(60) DEFAULT NULL,
            email VARCHAR(190) DEFAULT NULL,
            job_type VARCHAR(120) DEFAULT NULL,
            event_date DATE DEFAULT NULL,
            detail TEXT,
            source VARCHAR(40) NOT NULL DEFAULT 'web',
            status VARCHAR(20) NOT NULL DEFAULT 'new',
            admin_note TEXT,
            ip VARCHAR(60) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            read_at DATETIME DEFAULT NULL
        ){SUFFIX}",

        'autoreplies' => "CREATE TABLE IF NOT EXISTS autoreplies (
            id {PK},
            keyword VARCHAR(190) NOT NULL,
            reply TEXT,
            match_type VARCHAR(20) NOT NULL DEFAULT 'contains',
            channel VARCHAR(20) NOT NULL DEFAULT 'all',
            hits INT NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'visits' => "CREATE TABLE IF NOT EXISTS visits (
            id {PK},
            path VARCHAR(300) NOT NULL,
            album_id INT DEFAULT NULL,
            ip_hash VARCHAR(64) DEFAULT NULL,
            referer VARCHAR(400) DEFAULT NULL,
            ua VARCHAR(300) DEFAULT NULL,
            day DATE DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'email_log' => "CREATE TABLE IF NOT EXISTS email_log (
            id {PK},
            to_email VARCHAR(190) NOT NULL,
            subject VARCHAR(300) NOT NULL,
            template VARCHAR(80) DEFAULT NULL,
            body {LONGTEXT},
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            error VARCHAR(400) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",

        'activity_log' => "CREATE TABLE IF NOT EXISTS activity_log (
            id {PK},
            user_id INT DEFAULT NULL,
            user_name VARCHAR(120) DEFAULT NULL,
            action VARCHAR(80) NOT NULL,
            detail VARCHAR(400) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ){SUFFIX}",
    ];

    foreach ($tables as $sql) {
        $pdo->exec(ddl($sql));
    }

    // Indexes worth having once a real studio fills these tables.
    $indexes = [
        'CREATE INDEX IF NOT EXISTS idx_photos_album ON photos (album_id)',
        'CREATE INDEX IF NOT EXISTS idx_photos_folder ON photos (folder_id)',
        'CREATE INDEX IF NOT EXISTS idx_albums_status ON albums (status)',
        'CREATE INDEX IF NOT EXISTS idx_albums_slug ON albums (slug)',
        'CREATE INDEX IF NOT EXISTS idx_visits_day ON visits (day)',
        'CREATE INDEX IF NOT EXISTS idx_messages_status ON messages (status)',
        'CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users (email)',
    ];
    foreach ($indexes as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // MySQL below 8.0.29 has no IF NOT EXISTS for indexes; a duplicate is harmless.
        }
    }

    // Columns added after the first release go here, guarded.
    add_column($pdo, 'albums', 'client_name', "VARCHAR(160) DEFAULT NULL");
    add_column($pdo, 'photos', 'is_cover', "INT NOT NULL DEFAULT 0");

    db_seed($pdo);
}

/** First-run content, so the site is never empty on a fresh install. */
function db_seed(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    require_once __DIR__ . '/seed.php';
    seed_everything($pdo);
}
