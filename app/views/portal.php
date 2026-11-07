<?php
declare(strict_types=1);

$pageNames = [
 'home' => 'Home', 'dashboard' => 'Financial Dashboard', 'budget' => 'Annual Budget',
 'documents' => 'Public Documents', 'reports' => 'Quarterly Reports',
 'projects' => 'Programs & Projects', 'about' => 'About', 'notfound' => 'Page not found'
];
$label = $pageNames[$page] ?? 'Portal';
$yearString = (string) $year;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="description" content="SK Barangay Bantog Budget Transparency Portal — public budget records, reports, projects and downloadable documents.">
  <title><?= e($label) ?> | SK Barangay Bantog</title>
  <link rel="stylesheet" href="assets/css/site.css">
  <script defer src="assets/js/site.js"></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
  <div class="shell header-inner">
    <a class="brand" href="<?= e(portal_url()) ?>" aria-label="SK Barangay Bantog home">
      <img class="brand-logo" src="assets/images/sk-bantog-logo.webp" width="50" height="50" alt="SK Barangay Bantog logo">
      <span class="brand-label"><strong>SK BARANGAY BANTOG</strong><small>BUDGET TRANSPARENCY PORTAL · TARLAC CITY</small></span>
    </a>
    <button class="menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="main-nav">Menu <span aria-hidden="true">☰</span></button>
    <nav class="site-nav" id="main-nav" aria-label="Main navigation">
      <a class="<?= $page === 'home' ? 'active' : '' ?>" href="<?= e(portal_url()) ?>">Home</a>
      <a class="<?= $page === 'dashboard' ? 'active' : '' ?>" href="<?= e(portal_url('dashboard')) ?>">Dashboard</a>
      <a class="<?= $page === 'budget' ? 'active' : '' ?>" href="<?= e(portal_url('budget')) ?>">Annual Budget</a>
      <a class="<?= $page === 'documents' || $page === 'reports' ? 'active' : '' ?>" href="<?= e(portal_url('documents')) ?>">Documents</a>
      <a class="<?= $page === 'projects' ? 'active' : '' ?>" href="<?= e(portal_url('projects')) ?>">Projects</a>
      <a class="nav-admin" href="admin/index.php?view=login">Officials sign in <span aria-hidden="true">↗</span></a>
    </nav>
  </div>
</header>
<main id="main">
<?php if ($page === 'home'): ?>
  <section class="hero">
    <div class="shell hero-grid">
      <div class="hero-copy">
        <div class="hero-identity"><span class="hero-crest"><img src="assets/images/sk-bantog-logo.webp" width="66" height="66" alt=""></span><div><span class="eyebrow light"><span class="pulse-dot"></span> SANGGUNIANG KABATAAN · BARANGAY BANTOG</span><small>TARLAC CITY, PHILIPPINES · FISCAL YEAR <?= $year ?></small></div></div>
        <h1>Transparency<br>you can <span>trust.</span></h1>
        <p>Welcome to the Sangguniang Kabataan Budget Transparency Portal of Barangay Bantog, Tarlac City. Explore published SK budgets, expenses, documents and youth development initiatives in one accessible space.</p>
        <div class="hero-actions">
          <a class="btn btn-white" href="<?= e(portal_url('dashboard')) ?>">Explore dashboard <span aria-hidden="true">↗</span></a>
          <a class="btn btn-glass" href="<?= e(portal_url('documents')) ?>">Browse public records <span aria-hidden="true">→</span></a>
        </div>
        <div class="hero-trust"><span>TRANSPARENCY</span><span>ACCOUNTABILITY</span><span>PARTICIPATION</span></div>
      </div>
      <div class="hero-panel">
        <div class="panel-top"><span class="icon-disc"><img src="assets/images/sk-bantog-logo.webp" width="49" height="49" alt=""></span><span class="panel-tag">FY <?= $year ?> FINANCIAL SUMMARY</span></div>
        <div class="stat-label">Approved Annual Budget</div>
        <div class="hero-amount"><?= $stats['approved'] === null ? '<span class="unpublished">Awaiting publication</span>' : e(cash($stats['approved'])) ?></div>
        <div class="panel-divider"></div>
        <div class="panel-metric"><span>Reported expenditures</span><strong><?= e(cash($stats['spent'])) ?></strong></div>
        <div class="panel-metric"><span>Remaining balance</span><strong class="<?= $stats['balance'] !== null && $stats['balance'] < 0 ? 'negative' : 'positive' ?>"><?= $stats['balance'] === null ? '—' : e(cash($stats['balance'])) ?></strong></div>
        <div class="panel-metric"><span>Budget utilization</span><strong><?= $stats['approved'] === null ? '—' : number_format($stats['utilization'], 1) . '%' ?></strong></div>
        <progress value="<?= e((string) $stats['utilization']) ?>" max="100" aria-label="Budget utilization"></progress>
        <div class="panel-note">Figures appear only after authorized publication. <a href="<?= e(portal_url('budget')) ?>">View details →</a></div>
      </div>
    </div>
    <div class="hero-art hero-art-one" aria-hidden="true"></div><div class="hero-art hero-art-two" aria-hidden="true"></div>
  </section>
  <section class="snapshot">
    <div class="shell snapshot-grid">
      <div><span class="snapshot-icon">▣</span><strong><?= $documentTotal ?></strong><span>Public documents</span></div>
      <div><span class="snapshot-icon">◉</span><strong><?= count($projects) ?></strong><span>Visible projects</span></div>
      <div><span class="snapshot-icon">⌁</span><strong><?= $year ?></strong><span>Current fiscal year</span></div>
      <div><span class="snapshot-icon">↗</span><strong>Open</strong><span>Public access</span></div>
    </div>
  </section>
  <section class="section shell">
    <div class="section-heading"><div><span class="overline">EXPLORE THE PORTAL</span><h2>Information that belongs to the public.</h2><p>Find the records and updates you need without creating an account.</p></div></div>
    <div class="feature-grid">
      <a class="feature-card" href="<?= e(portal_url('budget')) ?>"><span class="feature-icon blue">₱</span><span class="feature-index">01 / FINANCIALS</span><h3>Annual SK Budget</h3><p>Approved appropriations, utilization and reported disbursements.</p><span class="feature-link">View annual budget →</span></a>
      <a class="feature-card" href="<?= e(portal_url('documents',['category'=>2,'year'=>'all'])) ?>"><span class="feature-icon mint">◎</span><span class="feature-index">02 / ALLOCATIONS</span><h3>Specific Purpose Fund</h3><p>Published supporting documents on dedicated funding and assistance.</p><span class="feature-link">Explore fund records →</span></a>
      <a class="feature-card" href="<?= e(portal_url('reports',['year'=>'all'])) ?>"><span class="feature-icon amber">▤</span><span class="feature-index">03 / ACCOUNTABILITY</span><h3>Quarterly Reports</h3><p>Read and download submitted quarterly financial disclosures.</p><span class="feature-link">Browse reports →</span></a>
      <a class="feature-card" href="<?= e(portal_url('projects')) ?>"><span class="feature-icon violet">✓</span><span class="feature-index">04 / IMPACT</span><h3>Programs & Projects</h3><p>Follow youth-centered programs and their reported status.</p><span class="feature-link">See our initiatives →</span></a>
    </div>
  </section>
  <section class="section section-soft"><div class="shell">
    <div class="section-heading inline-heading"><div><span class="overline">PUBLIC LIBRARY</span><h2>Recently published documents</h2><p>Official records become accessible here after an authorized publisher makes them public.</p></div><a class="text-link" href="<?= e(portal_url('documents',['year'=>'all'])) ?>">View all documents →</a></div>
    <?php if (!$latest): ?><div class="empty"><span class="empty-icon">▤</span><h3>No documents published yet</h3><p>Authorized SK officials can upload and publish approved records from the administration workspace.</p></div>
    <?php else: ?><div class="doc-list">
      <?php foreach ($latest as $doc): ?><article class="doc-row"><div class="doc-symbol">PDF</div><div class="doc-details"><strong><?= e($doc['title']) ?></strong><span><?= e($doc['category_name']) ?> · FY <?= (int) $doc['fiscal_year'] ?> · <?= e(date('M j, Y',strtotime($doc['published_at']))) ?></span></div><a class="btn btn-outline btn-sm" href="<?= e(portal_url('download',['id'=>(int)$doc['id']])) ?>">Download ↓</a></article><?php endforeach; ?>
    </div><?php endif; ?>
  </div></section>
  <section class="cta shell"><div><span class="overline">OUR COMMITMENT</span><h2>Every peso deserves accountability.</h2><p>We aim to make SK records clear, accessible and useful to every resident of Barangay Bantog.</p></div><a class="btn btn-white" href="<?= e(portal_url('about')) ?>">Learn about this portal ↗</a></section>

<?php elseif ($page === 'dashboard'): ?>
<section class="page-banner"><div class="shell"><span class="overline">PUBLIC FINANCIAL OVERVIEW</span><h1>Budget Dashboard</h1><p>A transparent snapshot of published SK financial records for fiscal year <?= $year ?>.</p></div></section>
<div class="shell inner-content">
  <form method="get" class="filter-form slim"><input type="hidden" name="page" value="dashboard"><label for="dash-year">Fiscal year</label><input id="dash-year" name="year" type="number" min="2020" max="2100" value="<?= $year ?>"><button class="btn btn-primary" type="submit">Apply</button></form>
  <div class="metric-grid">
    <div class="metric-card"><span>APPROVED BUDGET</span><strong><?= $stats['approved'] === null ? 'Not published' : e(cash($stats['approved'])) ?></strong><small>Fiscal year <?= $year ?></small></div>
    <div class="metric-card"><span>REPORTED EXPENDITURES</span><strong><?= e(cash($stats['spent'])) ?></strong><small>Published financial entries</small></div>
    <div class="metric-card"><span>REMAINING BALANCE</span><strong><?= $stats['balance'] === null ? '—' : e(cash($stats['balance'])) ?></strong><small>Approved budget less reported expenses</small></div>
    <div class="metric-card"><span>PUBLIC RECORDS</span><strong><?= $stats['documents'] ?></strong><small>Published in <?= $year ?></small></div>
  </div>
  <div class="two-panels">
    <div class="content-panel"><span class="overline">BUDGET UTILIZATION</span><h2>How much has been used?</h2><?php if ($stats['approved'] === null): ?><p>Approved budget data has not been published for this fiscal year.</p><?php else: ?><div class="util-number"><?= number_format($stats['utilization'],1) ?>%</div><progress value="<?= e((string)$stats['utilization']) ?>" max="100" aria-label="Percent utilized"></progress><p>Based on published expenditure entries. Values over 100% are capped on the visual indicator only.</p><?php endif; ?></div>
    <div class="content-panel"><span class="overline">AT A GLANCE</span><h2>Published records</h2><div class="detail-line"><span>Fiscal year</span><strong><?= $year ?></strong></div><div class="detail-line"><span>Projects</span><strong><?= $stats['projects'] ?></strong></div><div class="detail-line"><span>Documents</span><strong><?= $stats['documents'] ?></strong></div><div class="detail-line"><span>Expenditure entries shown</span><strong><?= count($expenditures) ?></strong></div><a class="text-link" href="<?= e(portal_url('budget',['year'=>$year])) ?>">View full budget details →</a></div>
  </div>
</div>

<?php elseif ($page === 'budget'): ?>
<section class="page-banner"><div class="shell"><span class="overline">FINANCIAL TRANSPARENCY</span><h1>Annual SK Budget</h1><p>Approved appropriations and documented expenses, organized by fiscal year.</p></div></section>
<div class="shell inner-content">
  <form method="get" class="filter-form slim"><input type="hidden" name="page" value="budget"><label for="budget-year">Fiscal year</label><input id="budget-year" name="year" type="number" min="2020" max="2100" value="<?= $year ?>"><button type="submit" class="btn btn-primary">Apply</button></form>
  <div class="metric-grid three">
    <div class="metric-card"><span>APPROVED ALLOCATION</span><strong><?= $stats['approved'] === null ? 'Not published' : e(cash($stats['approved'])) ?></strong></div>
    <div class="metric-card"><span>PUBLISHED EXPENDITURES</span><strong><?= e(cash($stats['spent'])) ?></strong></div>
    <div class="metric-card"><span>REMAINING BALANCE</span><strong><?= $stats['balance'] === null ? '—' : e(cash($stats['balance'])) ?></strong></div>
  </div>
  <div class="content-panel"><span class="overline">FY <?= $year ?></span><h2>Expenditure transparency</h2><p>Only expenses marked as published by authorized SK officials are displayed.</p>
    <?php if (!$expenditures): ?><div class="table-empty">No published expenditure entries for <?= $year ?>.</div><?php else: ?><div class="table-scroll"><table><thead><tr><th>Date</th><th>Expense / description</th><th class="align-right">Amount (PHP)</th></tr></thead><tbody><?php foreach($expenditures as $x): ?><tr><td><?= e($x['transaction_date']) ?></td><td><strong><?= e($x['title']) ?></strong><?php if($x['description']): ?><small><?= e($x['description']) ?></small><?php endif; ?></td><td class="align-right"><?= e(cash($x['amount'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
  </div>
  <div class="content-panel"><span class="overline">SUPPORTING RECORDS</span><h2>Approved annual budget documents</h2>
    <?php if (!$budgetDocs): ?><div class="table-empty">No approved annual budget PDFs have been published for <?= $year ?>.</div><?php else: ?><div class="doc-list"><?php foreach($budgetDocs as $doc): ?><article class="doc-row"><div class="doc-symbol">PDF</div><div class="doc-details"><strong><?= e($doc['title']) ?></strong><span>FY <?= (int)$doc['fiscal_year'] ?> · Published <?= e(date('M j, Y',strtotime($doc['published_at']))) ?></span></div><a class="btn btn-outline btn-sm" href="<?= e(portal_url('download',['id'=>$doc['id']])) ?>">Download ↓</a></article><?php endforeach; ?></div><?php endif; ?>
  </div>
</div>

<?php elseif ($page === 'documents' || $page === 'reports'): ?>
<section class="page-banner"><div class="shell"><span class="overline">OPEN PUBLIC RECORDS</span><h1><?= $page==='reports' ? 'Quarterly Reports' : 'Document Library' ?></h1><p>Search, read and download public PDF records published by authorized SK officials.</p></div></section>
<div class="shell inner-content">
  <form method="get" class="filter-form document-filter"><input type="hidden" name="page" value="<?= e($page) ?>">
    <div class="field"><label for="q">Search documents</label><input type="search" id="q" name="q" maxlength="100" placeholder="Document title or description" value="<?= e($search) ?>"></div>
    <div class="field"><label for="fy">Fiscal year</label><select id="fy" name="year"><option value="all" <?= ($_GET['year'] ?? '') === 'all' ? 'selected' : '' ?>>All fiscal years</option><option value="<?= $year ?>" <?= ($_GET['year'] ?? '') !== 'all' ? 'selected' : '' ?>><?= $year ?></option><?php foreach($yearOptions as $y): if((int)$y===$year) continue; ?><option value="<?= (int)$y ?>"><?= (int)$y ?></option><?php endforeach; ?></select></div>
    <?php if($page === 'documents'): ?><div class="field"><label for="cat">Category</label><select id="cat" name="category"><option value="0">All categories</option><?php foreach($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $selectedCategory===(int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
    <button class="btn btn-primary" type="submit">Find records →</button>
  </form>
  <div class="content-panel">
    <div class="result-heading"><div><span class="overline">AVAILABLE DOWNLOADS</span><h2><?= count($documents) ?> record<?= count($documents)===1 ? '' : 's' ?> found</h2></div><span class="muted">PDF format · Public access</span></div>
    <?php if (!$documents): ?><div class="empty"><span class="empty-icon">▤</span><h3>No matching documents</h3><p>Try changing the fiscal year, document category or search terms.</p></div><?php else: ?><div class="doc-list">
      <?php foreach ($documents as $doc): ?><article class="doc-row"><div class="doc-symbol">PDF</div><div class="doc-details"><strong><?= e($doc['title']) ?></strong><span><?= e($doc['category_name']) ?> · FY <?= (int)$doc['fiscal_year'] ?><?= $doc['quarter'] ? ' · Q'.(int)$doc['quarter'] : '' ?> · Published <?= e(date('M j, Y',strtotime($doc['published_at']))) ?></span><?php if($doc['description']): ?><p><?= e($doc['description']) ?></p><?php endif; ?></div><a class="btn btn-outline btn-sm" href="<?= e(portal_url('download',['id'=>(int)$doc['id']])) ?>">Download ↓</a></article><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
</div>

<?php elseif ($page === 'projects'): ?>
<section class="page-banner"><div class="shell"><span class="overline">PROGRAMS & IMPACT</span><h1>Youth Programs & Projects</h1><p>Updates on programs and projects disclosed by SK Barangay Bantog.</p></div></section>
<div class="shell inner-content">
 <?php if (!$projects): ?><div class="empty"><span class="empty-icon">◎</span><h3>No project updates published yet</h3><p>Approved project updates will appear when officials make them publicly available.</p></div><?php else: ?><div class="project-grid"><?php foreach($projects as $project): ?><article class="project-card"><div class="project-meta"><span class="project-tag"><?= e(strtoupper($project['status'])) ?></span><span>FY <?= (int)$project['fiscal_year'] ?></span></div><h2><?= e($project['title']) ?></h2><p><?= nl2br(e($project['description'])) ?></p><div class="project-bottom"><span>Reported allocation</span><strong><?= $project['amount'] === null ? 'Not disclosed' : e(cash($project['amount'])) ?></strong></div></article><?php endforeach; ?></div><?php endif; ?>
</div>

<?php elseif ($page === 'about'): ?>
<section class="page-banner"><div class="shell"><span class="overline">ABOUT THIS INITIATIVE</span><h1>Transparency by design.</h1><p>A central public access point for SK Barangay Bantog records.</p></div></section>
<div class="shell inner-content"><div class="content-panel prose"><h2>What you can find here</h2><p>This portal is designed to make published budget allocations, quarterly reports, financial summaries, public documents and community projects easier to access.</p><h2>Who publishes records?</h2><p>Only authenticated SK officials with publishing authority can make uploaded documents publicly downloadable. Draft and archived records are restricted from the public site.</p><h2>Document accuracy</h2><p>Amounts and reports are entered or uploaded by authorized officials. Supporting PDFs should be reviewed and approved under the barangay's applicable disclosure process before publication.</p><p>For formal verification of a report, contact the authorized SK office of Barangay Bantog, Tarlac City.</p></div></div>
<?php else: ?><div class="shell inner-content"><div class="empty"><h1>Page not found</h1><p>The requested page does not exist.</p><a class="btn btn-primary" href="<?= e(portal_url()) ?>">Return home</a></div></div><?php endif; ?>
</main>
<footer class="site-footer"><div class="shell footer-main"><div class="footer-brand"><img class="brand-logo" src="assets/images/sk-bantog-logo.webp" width="47" height="47" alt=""><div><strong>Sangguniang Kabataan<br>Barangay Bantog</strong><p>Tarlac City, Philippines</p></div></div><div><strong>Explore</strong><a href="<?= e(portal_url('dashboard')) ?>">Dashboard</a><a href="<?= e(portal_url('budget')) ?>">Annual budget</a><a href="<?= e(portal_url('documents',['year'=>'all'])) ?>">Public records</a></div><div><strong>Information</strong><a href="<?= e(portal_url('projects')) ?>">Projects</a><a href="<?= e(portal_url('reports',['year'=>'all'])) ?>">Quarterly reports</a><a href="<?= e(portal_url('about')) ?>">About the portal</a></div></div><div class="shell footer-bottom"><span>© <?= date('Y') ?> SK Barangay Bantog Budget Transparency Portal</span><span>Transparency · Accountability · Participation</span></div></footer>
</body></html>
