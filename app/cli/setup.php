<?php
/**
 * Command-line installer (local development / SSH):
 *   php php-site/app/cli/setup.php --sqlite --user=admin --pass=secret123456
 *   php php-site/app/cli/setup.php --mysql --host=localhost --db=NAME --dbuser=U --dbpass=P --user=admin --pass=...
 */
if (PHP_SAPI !== 'cli') { exit; }
define('ST_INSTALLER', true);
define('ST_CLI', true);
require dirname(__DIR__) . '/bootstrap.php';

$o = getopt('', ['sqlite', 'mysql', 'host::', 'db::', 'dbuser::', 'dbpass::', 'prefix::', 'user:', 'pass:', 'force']);
if (is_file(ST_APP . '/config.php') && !isset($o['force'])) {
    fwrite(STDERR, "Already installed (app/config.php exists). Use --force to reinstall.\n");
    exit(1);
}
if (empty($o['user']) || empty($o['pass']) || strlen($o['pass']) < 10) {
    fwrite(STDERR, "Usage: --sqlite|--mysql --user=NAME --pass=PASSWORD(min 10)\n");
    exit(1);
}
$prefix = $o['prefix'] ?? 'st_';
$db = isset($o['mysql'])
    ? ['driver' => 'mysql', 'host' => $o['host'] ?? 'localhost', 'port' => 3306, 'name' => $o['db'] ?? '', 'user' => $o['dbuser'] ?? '', 'pass' => $o['dbpass'] ?? '', 'prefix' => $prefix]
    : ['driver' => 'sqlite', 'path' => ST_ROOT . '/storage/site.sqlite', 'prefix' => $prefix];
DB::connect($db);
DB::migrate();
DB::exec('DELETE FROM {users} WHERE username = ?', [$o['user']]);
DB::insert('users', ['username' => $o['user'], 'email' => '', 'name' => 'Admin', 'password_hash' => password_hash($o['pass'], PASSWORD_DEFAULT), 'role' => 'admin', 'created_at' => now()]);
$config = ['db' => $db, 'app_key' => random_token(32), 'timezone' => 'Asia/Kolkata', 'debug' => true];
$php = "<?php\nreturn " . var_export($config, true) . ";\n";
$php = str_replace("'" . addslashes(ST_ROOT), "ST_ROOT . '", $php);
file_put_contents(ST_APP . '/config.php', $php);
echo "Installed. Log in at /admin/ as {$o['user']}\n";
