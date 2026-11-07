<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__);
$good = true;
echo "SK Bantog Transparency Portal - Local Setup Check\n";
echo "--------------------------------------------------\n";

function check(string $description, bool $passed, string $details = ''): void
{
    global $good;
    echo ($passed ? '[PASS]' : '[FAIL]') . ' ' . $description . ($details !== '' ? ': ' . $details : '') . "\n";
    if (!$passed) $good = false;
}

check('PHP 8.2+', PHP_VERSION_ID >= 80200, PHP_VERSION);
check('PDO MySQL', extension_loaded('pdo_mysql'));
check('Fileinfo extension', extension_loaded('fileinfo'));
$path = $root . '/config/local.php';
check('Local database configuration', is_file($path), is_file($path) ? 'found' : 'missing');
if (!is_file($path)) {
    echo "\nFix in PowerShell (project root):\n";
    echo "  Copy-Item config\\local.example.php config\\local.php\n";
    echo "  notepad config\\local.php\n";
    echo "Confirm name=sk_bantog_portal and your XAMPP MySQL credentials.\n";
    exit(1);
}
if (!extension_loaded('pdo_mysql')) {
    echo "\nEnable extension=pdo_mysql in your PHP php.ini.\n";
    exit(1);
}
require $root . '/app/bootstrap.php';
try {
    $config = app_config();
    check('Database name', ($config['db']['name'] ?? null) === 'sk_bantog_portal', (string)($config['db']['name'] ?? '(not configured)'));
    $pdo = db();
    check('MySQL connection', true, 'connected');
    $required = ['users','login_attempts','document_categories','documents','annual_budgets','expenditures','projects'];
    $statement = $pdo->query('SHOW TABLES');
    $found = $statement->fetchAll(PDO::FETCH_COLUMN);
    $missing = array_values(array_diff($required, $found));
    check('Seven required tables', count($missing) === 0, count($missing) === 0 ? 'all present' : 'missing '.implode(', ', $missing));
    if (!in_array('users', $missing, true)) {
        $admins = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn();
        check('Administrator account', $admins > 0, $admins > 0 ? $admins . ' active account(s)' : 'not yet created');
        if ($admins === 0) {
            echo "Create one: php bin/create-admin.php admin@example.com\n";
        }
    }
} catch (Throwable $exception) {
    check('MySQL/database access', false, 'connection or query failed');
    error_log((string)$exception);
    echo "Check MySQL is running in XAMPP and verify host, port, name, user and password in config/local.php.\n";
}
echo "\n" . ($good ? 'RESULT: Setup checks passed.' : 'RESULT: Fix the failed checks above.') . "\n";
exit($good ? 0 : 1);
