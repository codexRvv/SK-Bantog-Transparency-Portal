<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';

function valid_year(mixed $value): int
{
    $year = filter_var($value, FILTER_VALIDATE_INT);
    if ($year === false || $year < 2020 || $year > 2100) {
        throw new DomainException('Enter a fiscal year between 2020 and 2100.');
    }
    return (int) $year;
}

function valid_amount(mixed $value, bool $allowZero = false): string
{
    $amount = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($amount === false || !is_finite((float)$amount) || ($allowZero ? $amount < 0 : $amount <= 0) || $amount > 999999999999.99) {
        throw new DomainException('Enter a valid amount in Philippine pesos.');
    }
    return number_format((float)$amount, 2, '.', '');
}

function required_text(string $key, int $length = 200): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($value === '' || strlen($value) > $length) {
        throw new DomainException('Please enter ' . str_replace('_', ' ', $key) . ' (maximum ' . $length . ' characters).');
    }
    return $value;
}

try {
    $view = (string) ($_GET['view'] ?? 'overview');
    if (!in_array($view, ['login','overview','upload','documents','budget','projects'], true)) {
        $view = 'overview';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        try {
            if ($action === 'login') {
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) > 500) {
                    throw new DomainException('Incorrect email or password.');
                }
                $failed = (int) sql("SELECT COUNT(*) FROM login_attempts WHERE success=0
                    AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
                    AND (ip_hash=? OR email=?)", [$ipHash, $email])->fetchColumn();
                if ($failed >= 5) {
                    throw new DomainException('Too many unsuccessful attempts. Please try again after 15 minutes.');
                }
                $found = sql('SELECT id,password_hash FROM users WHERE email=? AND active=1', [$email])->fetch();
                $ok = $found && password_verify($password, $found['password_hash']);
                sql('INSERT INTO login_attempts (email,ip_hash,success) VALUES (?,?,?)', [$email,$ipHash,$ok ? 1 : 0]);
                if (!$ok) {
                    throw new DomainException('Incorrect email or password.');
                }
                session_regenerate_id(true);
                $_SESSION['uid'] = (int) $found['id'];
                sql('UPDATE users SET last_login_at=NOW() WHERE id=?', [$found['id']]);
                jump('index.php?view=overview');
            }

            if ($action === 'logout') {
                unset($_SESSION['uid'], $_SESSION['csrf']);
                session_regenerate_id(true);
                jump('index.php?view=login');
            }

            $operator = require_admin();

            if ($action === 'upload') {
                $title = required_text('title');
                $description = trim((string) ($_POST['description'] ?? ''));
                if (strlen($description) > 5000) throw new DomainException('Description is too long.');
                $year = valid_year($_POST['fiscal_year'] ?? null);
                $category = (int) ($_POST['category_id'] ?? 0);
                if (!sql('SELECT id FROM document_categories WHERE id=?', [$category])->fetchColumn()) {
                    throw new DomainException('Select a valid document category.');
                }
                $quarter = ($_POST['quarter'] ?? '') === '' ? null : filter_var($_POST['quarter'], FILTER_VALIDATE_INT);
                if ($quarter !== null && ($quarter === false || $quarter < 1 || $quarter > 4)) {
                    throw new DomainException('Quarter must be between 1 and 4.');
                }
                $file = $_FILES['pdf'] ?? null;
                if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
                    throw new DomainException('Upload a valid PDF document. Check the server upload limit.');
                }
                $size = filesize($file['tmp_name']);
                if ($size === false || $size < 6 || $size > 12 * 1024 * 1024) {
                    throw new DomainException('PDF must be 12 MB or smaller.');
                }
                $name = substr(basename((string) $file['name']), 0, 240);
                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
                    throw new DomainException('Only .pdf files are accepted.');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                $handle = fopen($file['tmp_name'], 'rb');
                $signature = $handle ? fread($handle, 5) : '';
                if ($handle) fclose($handle);
                if ($mime !== 'application/pdf' || $signature !== '%PDF-') {
                    throw new DomainException('The uploaded file is not recognized as a PDF.');
                }
                $folder = dirname(__DIR__, 2) . '/storage/uploads';
                if (!is_dir($folder) && !mkdir($folder, 0700, true)) {
                    throw new RuntimeException('Upload storage is not writable.');
                }
                $stored = bin2hex(random_bytes(24)) . '.pdf';
                $path = $folder . '/' . $stored;
                if (!move_uploaded_file($file['tmp_name'], $path)) {
                    throw new RuntimeException('Upload storage could not accept the file.');
                }
                try {
                    sql("INSERT INTO documents (category_id,title,description,fiscal_year,quarter,original_name,stored_name,mime_type,file_size,uploaded_by)
                        VALUES (?,?,?,?,?,?,?,?,?,?)", [$category,$title,$description,$year,$quarter,$name,$stored,'application/pdf',$size,$operator['id']]);
                } catch (Throwable $exception) {
                    unlink($path);
                    throw $exception;
                }
                flash('Document uploaded as a draft. A publisher can review and publish it.');
                jump('index.php?view=documents');
            }

            if ($action === 'document_status') {
                if (!may_publish($operator)) throw new DomainException('Only an administrator or publisher can change visibility.');
                $id = (int) ($_POST['id'] ?? 0);
                $status = (string) ($_POST['status'] ?? '');
                if ($id <= 0 || !in_array($status, ['draft','published','archived'], true)) {
                    throw new DomainException('Invalid document action.');
                }
                sql("UPDATE documents SET status=?,published_at=CASE WHEN ?='published' THEN COALESCE(published_at,NOW()) ELSE NULL END
                    WHERE id=?", [$status,$status,$id]);
                flash('Document status updated to ' . $status . '.');
                jump('index.php?view=documents');
            }

            if ($action === 'save_budget') {
                if (!may_publish($operator)) throw new DomainException('Publishing access is required.');
                $year = valid_year($_POST['fiscal_year'] ?? null);
                $amount = valid_amount($_POST['approved_amount'] ?? null, true);
                $remarks = trim((string) ($_POST['remarks'] ?? ''));
                if (strlen($remarks) > 5000) throw new DomainException('Remarks are too long.');
                sql('INSERT INTO annual_budgets (fiscal_year,approved_amount,remarks,updated_by) VALUES (?,?,?,?)
                    ON DUPLICATE KEY UPDATE approved_amount=VALUES(approved_amount),remarks=VALUES(remarks),
                    updated_by=VALUES(updated_by)', [$year,$amount,$remarks,$operator['id']]);
                flash('Approved budget saved for FY ' . $year . '.');
                jump('index.php?view=budget&year=' . $year);
            }

            if ($action === 'add_expense') {
                if (!may_publish($operator)) throw new DomainException('Publishing access is required.');
                $year = valid_year($_POST['fiscal_year'] ?? null);
                $title = required_text('title');
                $amount = valid_amount($_POST['amount'] ?? null);
                $date = (string) ($_POST['transaction_date'] ?? '');
                $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if (!$parsed || $parsed->format('Y-m-d') !== $date) {
                    throw new DomainException('Enter a valid transaction date.');
                }
                $description = trim((string) ($_POST['description'] ?? ''));
                if (strlen($description) > 5000) throw new DomainException('Description is too long.');
                $status = ($_POST['publish'] ?? '') === '1' ? 'published' : 'draft';
                sql('INSERT INTO expenditures (fiscal_year,transaction_date,title,amount,description,status,recorded_by)
                    VALUES (?,?,?,?,?,?,?)', [$year,$date,$title,$amount,$description,$status,$operator['id']]);
                flash('Expenditure recorded as ' . $status . '.');
                jump('index.php?view=budget&year=' . $year);
            }

            if ($action === 'expense_status') {
                if (!may_publish($operator)) throw new DomainException('Publishing access is required.');
                $id = (int) ($_POST['id'] ?? 0);
                $status = (string) ($_POST['status'] ?? '');
                if ($id <= 0 || !in_array($status, ['published','draft'], true)) throw new DomainException('Invalid expense action.');
                sql('UPDATE expenditures SET status=? WHERE id=?', [$status,$id]);
                flash('Expenditure visibility updated.');
                jump('index.php?view=budget');
            }

            if ($action === 'add_project') {
                $title = required_text('title');
                $description = required_text('description', 5000);
                $year = valid_year($_POST['fiscal_year'] ?? null);
                $amount = trim((string) ($_POST['amount'] ?? '')) === '' ? null : valid_amount($_POST['amount'],true);
                $status = (string) ($_POST['status'] ?? 'planned');
                if (!in_array($status, ['planned','ongoing','completed','on-hold'], true)) throw new DomainException('Invalid project status.');
                $published = may_publish($operator) && ($_POST['publish'] ?? '') === '1' ? 1 : 0;
                sql('INSERT INTO projects (title,description,fiscal_year,amount,status,is_published,created_by)
                    VALUES (?,?,?,?,?,?,?)', [$title,$description,$year,$amount,$status,$published,$operator['id']]);
                flash('Project added' . ($published ? ' and published.' : ' as a draft.'));
                jump('index.php?view=projects');
            }

            if ($action === 'project_status') {
                if (!may_publish($operator)) throw new DomainException('Publishing access is required.');
                $id = (int) ($_POST['id'] ?? 0);
                $status = (string) ($_POST['status'] ?? '');
                $published = ($_POST['publish'] ?? '') === '1' ? 1 : 0;
                if ($id <= 0 || !in_array($status, ['planned','ongoing','completed','on-hold'], true)) {
                    throw new DomainException('Invalid project action.');
                }
                sql('UPDATE projects SET status=?,is_published=? WHERE id=?', [$status,$published,$id]);
                flash('Project updated.');
                jump('index.php?view=projects');
            }
            throw new DomainException('Unknown action.');
        } catch (DomainException $exception) {
            flash($exception->getMessage(), 'error');
            jump('index.php?view=' . $view);
        }
    }

    if ($view === 'login') {
        if (signed_in()) jump('index.php?view=overview');
        $operator = null;
    } else {
        $operator = require_admin();
    }
    $year = clean_year($_GET['year'] ?? date('Y'));
    $categories = $view === 'upload' ? sql('SELECT id,name,description FROM document_categories ORDER BY sort_order')->fetchAll() : [];
    $recentDocuments = in_array($view, ['overview','documents'], true)
        ? sql('SELECT d.id,d.title,d.fiscal_year,d.status,d.original_name,d.created_at,d.published_at,c.name AS category_name,u.name AS uploader
          FROM documents d JOIN document_categories c ON c.id=d.category_id JOIN users u ON u.id=d.uploaded_by
          ORDER BY d.created_at DESC,d.id DESC LIMIT 100')->fetchAll() : [];
    $documentCounts = ['draft'=>0,'published'=>0,'archived'=>0];
    if ($view === 'overview') {
        foreach(sql('SELECT status,COUNT(*) AS total FROM documents GROUP BY status')->fetchAll() as $r) {
            $documentCounts[$r['status']] = (int) $r['total'];
        }
    }
    $stats = $view === 'overview' || $view === 'budget' ? overview($year) : [];
    $expenses = $view === 'budget' ? sql('SELECT e.*,u.name AS recorder FROM expenditures e
        JOIN users u ON u.id=e.recorded_by WHERE fiscal_year=? ORDER BY transaction_date DESC,id DESC LIMIT 100', [$year])->fetchAll() : [];
    $projectRows = $view === 'projects' ? sql('SELECT * FROM projects ORDER BY created_at DESC,id DESC LIMIT 100')->fetchAll() : [];
    require dirname(__DIR__, 2) . '/app/views/admin.php';
} catch (Throwable $exception) {
    render_failure($exception);
}
