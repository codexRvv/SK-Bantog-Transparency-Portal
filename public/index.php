<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $page = (string) ($_GET['page'] ?? 'home');
    if ($page === 'download') {
        public_download((int) ($_GET['id'] ?? 0));
    }
    if (!in_array($page, ['home','dashboard','budget','documents','reports','projects','about'], true)) {
        http_response_code(404);
        $page = 'notfound';
    }
    $year = clean_year($_GET['year'] ?? date('Y'));
    $stats = overview($year);
    $categories = sql('SELECT id,slug,name,description FROM document_categories ORDER BY sort_order')->fetchAll();
    $latest = sql("SELECT d.id,d.title,d.description,d.fiscal_year,d.published_at,d.file_size,c.name AS category_name
        FROM documents d JOIN document_categories c ON c.id=d.category_id
        WHERE d.status='published' ORDER BY d.published_at DESC,d.id DESC LIMIT 5")->fetchAll();
    $documentTotal = (int) sql("SELECT COUNT(*) FROM documents WHERE status='published'")->fetchColumn();
    $yearOptions = sql("SELECT DISTINCT fiscal_year FROM documents WHERE status='published'
        UNION SELECT fiscal_year FROM annual_budgets UNION SELECT fiscal_year FROM projects WHERE is_published=1
        ORDER BY fiscal_year DESC")->fetchAll(PDO::FETCH_COLUMN);

    $documents = [];
    $selectedCategory = (int) ($_GET['category'] ?? 0);
    $search = substr(trim((string) ($_GET['q'] ?? '')), 0, 100);
    if (in_array($page, ['documents','reports'], true)) {
        $where = ["d.status='published'"];
        $params = [];
        if ($page === 'reports') {
            $where[] = "c.slug='quarterly-reports'";
        } elseif ($selectedCategory > 0) {
            $where[] = 'd.category_id=?';
            $params[] = $selectedCategory;
        }
        if (($_GET['year'] ?? '') !== 'all') {
            $where[] = 'd.fiscal_year=?';
            $params[] = $year;
        }
        if ($search !== '') {
            $where[] = '(d.title LIKE ? OR d.description LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        $documents = sql('SELECT d.id,d.title,d.description,d.fiscal_year,d.quarter,d.file_size,d.published_at,c.name AS category_name
            FROM documents d JOIN document_categories c ON c.id=d.category_id
            WHERE ' . implode(' AND ', $where) . ' ORDER BY d.published_at DESC,d.id DESC LIMIT 200', $params)->fetchAll();
    }

    $expenditures = [];
    if ($page === 'budget' || $page === 'dashboard') {
        $expenditures = sql("SELECT title,transaction_date,description,amount FROM expenditures
            WHERE status='published' AND fiscal_year=? ORDER BY transaction_date DESC,id DESC LIMIT 100", [$year])->fetchAll();
    }
    $projects = [];
    if ($page === 'projects' || $page === 'home') {
        $projects = sql('SELECT id,title,description,fiscal_year,amount,status,started_on,completed_on
            FROM projects WHERE is_published=1 ORDER BY fiscal_year DESC,id DESC LIMIT 30')->fetchAll();
    }
    $budgetDocs = [];
    if ($page === 'budget') {
        $budgetDocs = sql("SELECT d.id,d.title,d.published_at,d.fiscal_year FROM documents d
            JOIN document_categories c ON c.id=d.category_id
            WHERE d.status='published' AND c.slug='annual-budget' AND d.fiscal_year=?
            ORDER BY d.published_at DESC", [$year])->fetchAll();
    }
    require dirname(__DIR__) . '/app/views/portal.php';
} catch (Throwable $exception) {
    render_failure($exception);
}
