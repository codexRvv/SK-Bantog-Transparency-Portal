<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$projectRoot = dirname(__DIR__);
if (!is_file($projectRoot . '/config/local.php')) {
    fwrite(STDERR, "Database configuration missing. From your project folder run:\n");
    fwrite(STDERR, "  Copy-Item config\\local.example.php config\\local.php\n");
    fwrite(STDERR, "  notepad config\\local.php\n");
    fwrite(STDERR, "  php bin/check-setup.php\n");
    exit(1);
}

require $projectRoot . '/app/bootstrap.php';

try {
    $email = strtolower(trim((string) ($argv[1] ?? '')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        exit("Usage: php bin/create-admin.php admin@example.com\n");
    }
    if (sql('SELECT id FROM users WHERE email=?', [$email])->fetchColumn()) {
        exit("This email is already registered. Use an unused email address.\n");
    }
    echo "Administrator display name: ";
    $name = trim((string) fgets(STDIN));
    echo "Administrator password (minimum 12 characters; input is visible): ";
    $password = rtrim((string) fgets(STDIN), "\r\n");
    if ($name === '' || strlen($password) < 12) {
        exit("Name required and password must contain at least 12 characters.\n");
    }
    sql("INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')", [
        $name, $email, password_hash($password, PASSWORD_DEFAULT)
    ]);
    echo "Administrator account created. Never share this password.\n";
} catch (Throwable $exception) {
    error_log((string) $exception);
    exit("Unable to create administrator. Run php bin/check-setup.php to diagnose the connection.\n");
}
