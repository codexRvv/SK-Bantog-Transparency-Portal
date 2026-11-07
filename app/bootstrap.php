<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('sk_bantog_portal');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function app_config(): array
{
    static $configuration = null;
    if ($configuration === null) {
        $file = dirname(__DIR__) . '/config/local.php';
        if (!is_file($file)) {
            throw new RuntimeException('Configuration missing. Copy config/local.example.php to config/local.php.');
        }
        $configuration = require $file;
    }
    return $configuration;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $db = app_config()['db'];
        $dsn = 'mysql:host=' . $db['host'] . ';port=' . (int) $db['port'] . ';dbname=' . $db['name'] . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $db['user'], $db['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function sql(string $query, array $params = []): PDOStatement
{
    $statement = db()->prepare($query);
    $statement->execute($params);
    return $statement;
}

function e(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cash(int|float|string|null $amount): string
{
    return '₱' . number_format((float) ($amount ?? 0), 2);
}

function clean_year(mixed $value): int
{
    $year = filter_var($value, FILTER_VALIDATE_INT);
    return $year && $year >= 2020 && $year <= 2100 ? $year : (int) date('Y');
}

function portal_url(string $page = 'home', array $query = []): string
{
    return 'index.php?' . http_build_query(array_merge(['page' => $page], $query));
}

function jump(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function token(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(token()) . '">';
}

function verify_csrf(): void
{
    if (!hash_equals(token(), (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(419);
        exit('Your session expired or the request was invalid. Please go back and reload the form.');
    }
}

function flash(string $message, string $kind = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'kind' => $kind];
}

function take_flash(): ?array
{
    $result = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $result;
}

function signed_in(): ?array
{
    $id = filter_var($_SESSION['uid'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        return null;
    }
    $user = sql('SELECT id,name,email,role FROM users WHERE id=? AND active=1', [$id])->fetch();
    if (!$user) {
        unset($_SESSION['uid']);
        return null;
    }
    return $user;
}

function require_admin(): array
{
    $user = signed_in();
    if (!$user) {
        jump('index.php?view=login');
    }
    return $user;
}

function may_publish(array $user): bool
{
    return in_array($user['role'], ['admin', 'publisher'], true);
}

function overview(int $year): array
{
    $budget = sql('SELECT approved_amount,remarks,updated_at FROM annual_budgets WHERE fiscal_year=?', [$year])->fetch();
    $spent = sql("SELECT COALESCE(SUM(amount),0) AS total FROM expenditures WHERE fiscal_year=? AND status='published'", [$year])->fetchColumn();
    $documents = (int) sql("SELECT COUNT(*) FROM documents WHERE status='published' AND fiscal_year=?", [$year])->fetchColumn();
    $projects = (int) sql('SELECT COUNT(*) FROM projects WHERE is_published=1 AND fiscal_year=?', [$year])->fetchColumn();
    $approved = $budget ? (float) $budget['approved_amount'] : null;
    return [
        'approved' => $approved,
        'spent' => (float) $spent,
        'balance' => $approved === null ? null : $approved - (float) $spent,
        'utilization' => $approved !== null && $approved > 0 ? min(100, (float) $spent / $approved * 100) : 0,
        'documents' => $documents,
        'projects' => $projects,
        'budget' => $budget,
    ];
}

function public_download(int $id): never
{
    $doc = sql('SELECT * FROM documents WHERE id=?', [$id])->fetch();
    if (!$doc || ($doc['status'] !== 'published' && !signed_in())) {
        http_response_code(404);
        exit('Document unavailable.');
    }
    $path = dirname(__DIR__) . '/storage/uploads/' . basename($doc['stored_name']);
    if (!is_file($path)) {
        http_response_code(404);
        exit('File unavailable.');
    }
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $doc['original_name']);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, no-store');
    session_write_close();
    readfile($path);
    exit;
}

function render_failure(Throwable $exception): never
{
    error_log((string) $exception);
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><title>Portal unavailable</title>';
    echo '<body style="font-family:system-ui;margin:10vh auto;max-width:40rem;padding:1rem"><h1>Portal setup or service required</h1>';
    echo '<p>Please check the database connection and installation instructions. Details have been recorded in the server log.</p></body></html>';
    exit;
}
