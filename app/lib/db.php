<?php
/**
 * Thin PDO wrapper that works with MySQL/MariaDB (cPanel) and SQLite.
 * Table names are written as {name} in SQL and get the configured prefix.
 */
final class DB
{
    /** @var PDO|null */
    private static $pdo = null;
    private static $prefix = '';
    private static $driver = 'mysql';

    /** Bump when schema() gains tables or columns; upgrade() applies them to live sites. */
    const SCHEMA_VERSION = 3;

    public static function connect(?array $config = null): PDO
    {
        if ($config === null) {
            if (self::$pdo) {
                return self::$pdo;
            }
            $config = (array)cfg('db', []);
        }
        $driver = $config['driver'] ?? 'mysql';
        $prefix = (string)($config['prefix'] ?? '');
        if (!preg_match('/^[A-Za-z0-9_]*$/', $prefix)) {
            throw new RuntimeException('Invalid table prefix.');
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        if ($driver === 'sqlite') {
            $path = (string)($config['path'] ?? (ST_ROOT . '/storage/site.sqlite'));
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA journal_mode = WAL');
            $pdo->exec('PRAGMA busy_timeout = 5000');
        } else {
            $driver = 'mysql';
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'] ?? 'localhost',
                (int)($config['port'] ?? 3306),
                $config['name'] ?? ''
            );
            $pdo = new PDO($dsn, (string)($config['user'] ?? ''), (string)($config['pass'] ?? ''), $options);
            $pdo->exec("SET NAMES utf8mb4");
        }
        self::$pdo = $pdo;
        self::$prefix = $prefix;
        self::$driver = $driver;
        return $pdo;
    }

    public static function driver(): string
    {
        self::connect();
        return self::$driver;
    }

    public static function table(string $name): string
    {
        return self::$prefix . $name;
    }

    private static function sql(string $sql): string
    {
        return preg_replace_callback('/\{([a-z_]+)\}/', function ($m) {
            return self::$prefix . $m[1];
        }, $sql);
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $pdo = self::connect();
        $stmt = $pdo->prepare(self::sql($sql));
        $stmt->execute($params);
        return $stmt;
    }

    public static function row(string $sql, array $params = []): ?array
    {
        $r = self::query($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = [])
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function exec(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO {%s} (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', array_fill(0, count($cols), '?'))
        );
        self::query($sql, array_values($data));
        return (int)self::connect()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $params = []): int
    {
        $sets = [];
        foreach (array_keys($data) as $col) {
            $sets[] = $col . ' = ?';
        }
        $sql = sprintf('UPDATE {%s} SET %s WHERE %s', $table, implode(', ', $sets), $where);
        return self::exec($sql, array_merge(array_values($data), $params));
    }

    public static function transaction(callable $fn)
    {
        $pdo = self::connect();
        $pdo->beginTransaction();
        try {
            $result = $fn();
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** CREATE TABLE statements for the active driver. */
    public static function schema(string $driver): array
    {
        $isMy = $driver !== 'sqlite';
        $pk = $isMy ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $big = $isMy ? 'MEDIUMTEXT' : 'TEXT';
        $tail = $isMy ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $p = self::$prefix;

        return [
            "CREATE TABLE IF NOT EXISTS {$p}users (
                id $pk,
                username VARCHAR(64) NOT NULL UNIQUE,
                email VARCHAR(190) NOT NULL DEFAULT '',
                name VARCHAR(120) NOT NULL DEFAULT '',
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'admin',
                created_at DATETIME NOT NULL,
                last_login_at DATETIME NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}content_docs (
                doc VARCHAR(120) NOT NULL PRIMARY KEY,
                draft $big NULL,
                published $big NULL,
                updated_at DATETIME NULL,
                updated_by INT NULL,
                published_at DATETIME NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}revisions (
                id $pk,
                doc VARCHAR(120) NOT NULL,
                data $big NOT NULL,
                note VARCHAR(190) NOT NULL DEFAULT '',
                user_id INT NULL,
                created_at DATETIME NOT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}media (
                id $pk,
                path VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL DEFAULT '',
                mime VARCHAR(100) NOT NULL DEFAULT '',
                size INT NOT NULL DEFAULT 0,
                width INT NULL,
                height INT NULL,
                user_id INT NULL,
                created_at DATETIME NOT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}leads (
                id $pk,
                name VARCHAR(190) NOT NULL DEFAULT '',
                email VARCHAR(190) NOT NULL DEFAULT '',
                phone VARCHAR(64) NOT NULL DEFAULT '',
                company VARCHAR(190) NOT NULL DEFAULT '',
                service VARCHAR(190) NOT NULL DEFAULT '',
                message TEXT NOT NULL,
                page VARCHAR(255) NOT NULL DEFAULT '',
                ip VARCHAR(64) NOT NULL DEFAULT '',
                user_agent VARCHAR(255) NOT NULL DEFAULT '',
                is_read INT NOT NULL DEFAULT 0,
                visitor_id VARCHAR(40) NOT NULL DEFAULT '',
                session_id VARCHAR(40) NOT NULL DEFAULT '',
                spam INT NOT NULL DEFAULT 0,
                spam_reasons VARCHAR(255) NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}settings (
                skey VARCHAR(100) NOT NULL PRIMARY KEY,
                svalue TEXT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}login_attempts (
                id $pk,
                ip VARCHAR(64) NOT NULL,
                username VARCHAR(64) NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL
            )$tail",
            // ---- Visitor analytics (app/lib/analytics.php) ----
            "CREATE TABLE IF NOT EXISTS {$p}an_visitors (
                id $pk,
                vid VARCHAR(40) NOT NULL UNIQUE,
                first_seen DATETIME NOT NULL,
                last_seen DATETIME NOT NULL,
                sessions INT NOT NULL DEFAULT 0,
                pageviews INT NOT NULL DEFAULT 0,
                clicks INT NOT NULL DEFAULT 0,
                engaged INT NOT NULL DEFAULT 0,
                first_source VARCHAR(60) NOT NULL DEFAULT '',
                first_medium VARCHAR(30) NOT NULL DEFAULT '',
                first_referrer VARCHAR(255) NOT NULL DEFAULT '',
                first_landing VARCHAR(255) NOT NULL DEFAULT '',
                device VARCHAR(20) NOT NULL DEFAULT '',
                os VARCHAR(40) NOT NULL DEFAULT '',
                browser VARCHAR(40) NOT NULL DEFAULT '',
                country VARCHAR(2) NOT NULL DEFAULT '',
                region VARCHAR(80) NOT NULL DEFAULT '',
                city VARCHAR(80) NOT NULL DEFAULT '',
                lead_id INT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}an_sessions (
                id $pk,
                sid VARCHAR(40) NOT NULL UNIQUE,
                vid VARCHAR(40) NOT NULL,
                started_at DATETIME NOT NULL,
                last_at DATETIME NOT NULL,
                duration INT NOT NULL DEFAULT 0,
                pageviews INT NOT NULL DEFAULT 0,
                clicks INT NOT NULL DEFAULT 0,
                engaged INT NOT NULL DEFAULT 0,
                is_bot INT NOT NULL DEFAULT 0,
                bot_reason VARCHAR(60) NOT NULL DEFAULT '',
                is_new INT NOT NULL DEFAULT 0,
                visit_no INT NOT NULL DEFAULT 1,
                landing VARCHAR(255) NOT NULL DEFAULT '',
                exit_path VARCHAR(255) NOT NULL DEFAULT '',
                referrer VARCHAR(255) NOT NULL DEFAULT '',
                source VARCHAR(60) NOT NULL DEFAULT '',
                medium VARCHAR(30) NOT NULL DEFAULT '',
                campaign VARCHAR(120) NOT NULL DEFAULT '',
                term VARCHAR(120) NOT NULL DEFAULT '',
                device VARCHAR(20) NOT NULL DEFAULT '',
                os VARCHAR(40) NOT NULL DEFAULT '',
                browser VARCHAR(40) NOT NULL DEFAULT '',
                screen VARCHAR(20) NOT NULL DEFAULT '',
                lang VARCHAR(20) NOT NULL DEFAULT '',
                tz VARCHAR(60) NOT NULL DEFAULT '',
                country VARCHAR(2) NOT NULL DEFAULT '',
                region VARCHAR(80) NOT NULL DEFAULT '',
                city VARCHAR(80) NOT NULL DEFAULT '',
                ip_masked VARCHAR(64) NOT NULL DEFAULT '',
                lead_id INT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}an_events (
                id $pk,
                sid VARCHAR(40) NOT NULL,
                vid VARCHAR(40) NOT NULL,
                pid VARCHAR(20) NOT NULL DEFAULT '',
                type VARCHAR(12) NOT NULL,
                path VARCHAR(255) NOT NULL DEFAULT '',
                title VARCHAR(190) NOT NULL DEFAULT '',
                label VARCHAR(190) NOT NULL DEFAULT '',
                target VARCHAR(255) NOT NULL DEFAULT '',
                kind VARCHAR(20) NOT NULL DEFAULT '',
                seconds INT NOT NULL DEFAULT 0,
                scroll INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL
            )$tail",
            "CREATE TABLE IF NOT EXISTS {$p}an_geo (
                ip_key VARCHAR(64) NOT NULL PRIMARY KEY,
                country VARCHAR(2) NOT NULL DEFAULT '',
                region VARCHAR(80) NOT NULL DEFAULT '',
                city VARCHAR(80) NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL
            )$tail",
            "CREATE INDEX {$p}idx_revisions_doc ON {$p}revisions (doc)",
            "CREATE INDEX {$p}idx_leads_created ON {$p}leads (created_at)",
            "CREATE INDEX {$p}idx_attempts_ip ON {$p}login_attempts (ip, created_at)",
            "CREATE INDEX {$p}idx_leads_visitor ON {$p}leads (visitor_id)",
            "CREATE INDEX {$p}idx_an_vis_first ON {$p}an_visitors (first_seen)",
            "CREATE INDEX {$p}idx_an_ses_start ON {$p}an_sessions (started_at)",
            "CREATE INDEX {$p}idx_an_ses_vid ON {$p}an_sessions (vid)",
            "CREATE INDEX {$p}idx_an_ses_last ON {$p}an_sessions (last_at)",
            "CREATE INDEX {$p}idx_an_ses_net ON {$p}an_sessions (ip_masked, started_at)",
            "CREATE INDEX {$p}idx_an_ev_sid ON {$p}an_events (sid)",
            "CREATE INDEX {$p}idx_an_ev_time ON {$p}an_events (created_at)",
            "CREATE INDEX {$p}idx_an_ev_pid ON {$p}an_events (pid)",
        ];
    }

    /**
     * Bring an existing install up to SCHEMA_VERSION: creates missing tables and
     * adds new columns to old ones. Runs once per version (bootstrap calls it).
     */
    public static function upgrade(): void
    {
        if ((int)Settings::get('schema_version', '1') >= self::SCHEMA_VERSION) {
            return;
        }
        try {
            self::migrate();
            self::addColumns('leads', [
                'visitor_id' => "VARCHAR(40) NOT NULL DEFAULT ''",
                'session_id' => "VARCHAR(40) NOT NULL DEFAULT ''",
                'spam' => 'INT NOT NULL DEFAULT 0',
                'spam_reasons' => "VARCHAR(255) NOT NULL DEFAULT ''",
            ]);
            self::migrate(); // again, for indexes on the columns just added
            Settings::set('schema_version', (string)self::SCHEMA_VERSION);
        } catch (Throwable $e) {
            error_log('Database upgrade failed: ' . $e->getMessage());
        }
    }

    /** Column names of a table. */
    public static function columns(string $table): array
    {
        $t = self::$prefix . $table;
        if (self::driver() === 'sqlite') {
            return array_column(self::connect()->query("PRAGMA table_info($t)")->fetchAll(), 'name');
        }
        return array_column(self::connect()->query("SHOW COLUMNS FROM `$t`")->fetchAll(), 'Field');
    }

    private static function addColumns(string $table, array $columns): void
    {
        $existing = self::columns($table);
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $existing, true)) {
                self::connect()->exec('ALTER TABLE ' . self::$prefix . $table . ' ADD COLUMN ' . $name . ' ' . $definition);
            }
        }
    }

    public static function migrate(): void
    {
        self::connect();
        foreach (self::schema(self::$driver) as $sql) {
            try {
                self::connect()->exec($sql);
            } catch (PDOException $e) {
                // Indexes already exist on re-run — safe to ignore.
                if (stripos($sql, 'CREATE INDEX') !== 0) {
                    throw $e;
                }
            }
        }
    }
}

/** Key/value site settings stored in the settings table. */
final class Settings
{
    private static $cache = null;

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (DB::all('SELECT skey, svalue FROM {settings}') as $r) {
                    self::$cache[$r['skey']] = $r['svalue'];
                }
            } catch (Throwable $e) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    public static function get(string $key, $default = '')
    {
        $all = self::all();
        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        if (DB::value('SELECT COUNT(*) FROM {settings} WHERE skey = ?', [$key])) {
            DB::exec('UPDATE {settings} SET svalue = ? WHERE skey = ?', [$value, $key]);
        } else {
            DB::insert('settings', ['skey' => $key, 'svalue' => $value]);
        }
        self::$cache = null;
    }
}
