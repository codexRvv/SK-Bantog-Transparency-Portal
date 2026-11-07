<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
$operator = require_admin();
$types = ['CBYDP','ABYIP'];
$type = strtoupper((string)($_GET['type'] ?? 'ABYIP'));
if (!in_array($type,$types,true)) $type='ABYIP';
try {
 if ($_SERVER['REQUEST_METHOD']==='POST') {
  verify_csrf();
  $action=(string)($_POST['action'] ?? '');
  if ($action==='create') {
   $type=(string)($_POST['plan_type'] ?? '');
   if (!in_array($type,$types,true)) throw new DomainException('Invalid plan type.');
   $year=filter_var($_POST['fiscal_year'] ?? null,FILTER_VALIDATE_INT);
   if (!$year || $year<2020 || $year>2100) throw new DomainException('Invalid fiscal year.');
   $end=$type==='CBYDP' ? $year+2 : $year;
   if ($end>2100) throw new DomainException('Invalid planning period.');
   $title=trim((string)($_POST['title'] ?? ''));
   if ($title==='' || strlen($title)>200) throw new DomainException('Enter a title (up to 200 characters).');
   $description=trim((string)($_POST['description'] ?? ''));
   if (strlen($description)>5000) throw new DomainException('Description is too long.');
   sql('INSERT INTO youth_plans (plan_type,title,fiscal_year,end_year,description,created_by) VALUES (?,?,?,?,?,?)',[$type,$title,$year,$end,$description,$operator['id']]);
   flash('Plan created as draft. Add its program entries.');
   jump('plans.php?id='.db()->lastInsertId());
  }
  $id=filter_var($_POST['id'] ?? null,FILTER_VALIDATE_INT);
  if (!$id || !sql('SELECT id FROM youth_plans WHERE id=?',[$id])->fetchColumn()) throw new DomainException('Plan not found.');
  if ($action==='save_sheet') {
   $status=sql('SELECT status FROM youth_plans WHERE id=?',[$id])->fetchColumn();
   if($status!=='draft') throw new DomainException('Switch the plan to draft before editing.');
   $rows=$_POST['rows']??[];
   if(!is_array($rows)||count($rows)>250) throw new DomainException('Maximum 250 rows.');
   $fields=['center_of_participation','youth_concern','objective','program_title','target_beneficiaries','budget','implementation_period','responsible_person'];
   $prepared=[];
   foreach($rows as $row){
    if(!is_array($row)) throw new DomainException('Invalid row.');
    $v=[];
    foreach($fields as $field){
     if(!is_string($row[$field]??'')) throw new DomainException('Invalid cell.');
     $v[$field]=trim($row[$field]??'');
     if(strlen($v[$field])>($field==='youth_concern'||$field==='objective'?2000:200)) throw new DomainException('Cell too long.');
    }
    if(!array_filter($v,fn($x)=>$x!==''))continue;
    foreach(['center_of_participation','youth_concern','objective','program_title'] as $required) if($v[$required]==='')throw new DomainException('Complete all required cells on each row.');
    if($v['budget']!=='' && (!preg_match('/^\\d{1,12}(?:\\.\\d{1,2})?$/',$v['budget']) || (float)$v['budget']>999999999999.99))throw new DomainException('Invalid peso allocation.');
    $prepared[]=$v;
   }
   $pdo=db();$pdo->beginTransaction();
   try {
    $lock=$pdo->prepare('SELECT status FROM youth_plans WHERE id=? FOR UPDATE');$lock->execute([$id]);
    if($lock->fetchColumn()!=='draft')throw new DomainException('Plan was published. Refresh the worksheet.');
    $pdo->prepare('DELETE FROM youth_plan_items WHERE plan_id=?')->execute([$id]);
    $insert=$pdo->prepare('INSERT INTO youth_plan_items (plan_id,center_of_participation,youth_concern,objective,program_title,target_beneficiaries,budget,implementation_period,responsible_person) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach($prepared as $v)$insert->execute([$id,$v['center_of_participation'],$v['youth_concern'],$v['objective'],$v['program_title'],$v['target_beneficiaries'],$v['budget']===''?null:$v['budget'],$v['implementation_period'],$v['responsible_person']]);
    $pdo->commit();
   }catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();throw $ex;}
   flash('Saved '.count($prepared).' worksheet rows.');jump('plans.php?id='.$id);
  }
  if ($action==='add_item') {
   $keys=['center_of_participation','youth_concern','objective','program_title','target_beneficiaries','implementation_period','responsible_person'];
   $v=[];
   foreach($keys as $key) {
    $value=trim((string)($_POST[$key] ?? ''));
    if (strlen($value)>2000) throw new DomainException('One of the fields is too long.');
    $v[$key]=$value;
   }
   foreach(['center_of_participation','youth_concern','objective','program_title'] as $required) if ($v[$required]==='') throw new DomainException('Complete the required activity fields.');
   $raw=trim((string)($_POST['budget'] ?? ''));
   $amount=null;
   if ($raw!=='') {
    if (!is_numeric($raw) || (float)$raw<0 || (float)$raw>999999999999.99) throw new DomainException('Invalid budget amount.');
    $amount=number_format((float)$raw,2,'.','');
   }
   sql('INSERT INTO youth_plan_items (plan_id,center_of_participation,youth_concern,objective,program_title,target_beneficiaries,budget,implementation_period,responsible_person) VALUES (?,?,?,?,?,?,?,?,?)',[$id,$v['center_of_participation'],$v['youth_concern'],$v['objective'],$v['program_title'],$v['target_beneficiaries'],$amount,$v['implementation_period'],$v['responsible_person']]);
   flash('Activity added to plan.');
  } elseif ($action==='remove_item') {
   if (!may_publish($operator)) throw new DomainException('Publishing access required.');
   $item=(int)($_POST['item_id'] ?? 0);
   sql('DELETE FROM youth_plan_items WHERE id=? AND plan_id=?',[$item,$id]);
   flash('Activity removed.');
  } elseif ($action==='visibility') {
   if (!may_publish($operator)) throw new DomainException('Only administrators and publishers may change visibility.');
   $status=(string)($_POST['status'] ?? '');
   if (!in_array($status,['draft','published','archived'],true)) throw new DomainException('Invalid status.');
   $count=(int)sql('SELECT COUNT(*) FROM youth_plan_items WHERE plan_id=?',[$id])->fetchColumn();
   if ($status==='published' && $count===0) throw new DomainException('Add at least one activity before publishing.');
   sql("UPDATE youth_plans SET status=?,published_at=CASE WHEN ?='published' THEN COALESCE(published_at,NOW()) ELSE NULL END WHERE id=?",[$status,$status,$id]);
   flash('Plan status updated. Publish only after official approval.');
  } else throw new DomainException('Unknown action.');
  jump('plans.php?id='.$id);
 }
} catch (DomainException $ex) { flash($ex->getMessage(),'error'); jump('plans.php'.(isset($id)&&$id?'?id='.$id:'')); }
try {
 $id=(int)($_GET['id'] ?? 0);
 $plan=$id>0 ? sql('SELECT * FROM youth_plans WHERE id=?',[$id])->fetch() : null;
 $items=$plan ? sql('SELECT * FROM youth_plan_items WHERE plan_id=? ORDER BY id',[$id])->fetchAll() : [];
 $plans=sql('SELECT p.*,COUNT(i.id) AS activity_count FROM youth_plans p LEFT JOIN youth_plan_items i ON i.plan_id=p.id GROUP BY p.id ORDER BY p.fiscal_year DESC,p.id DESC LIMIT 100')->fetchAll();
} catch (Throwable $ex) { render_failure($ex); }
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Youth Planning | SK Bantog</title><link rel="stylesheet" href="../assets/css/site.css"><link rel="stylesheet" href="../assets/css/admin.css"><link rel="stylesheet" href="../assets/css/plans.css"></head><body class="admin-body">
<div class="plans-shell"><header class="plans-top"><a href="index.php?view=overview">← Back to dashboard</a><div><strong>SK BANTOG</strong><small>2027 · OFFICIALS WORKSPACE</small></div><a href="../plans.php" target="_blank" rel="noopener">Public plans ↗</a></header>
<main class="plans-main"><div class="plans-heading"><div><span class="overline">YOUTH DEVELOPMENT PLANNING</span><h1>CBYDP & ABYIP workspace</h1><p>Prepare structured youth plans, track activities, and publish only officially approved information.</p></div></div>
<?php if ($alert=take_flash()): ?><div class="alert <?= e($alert['kind']) ?>"><?= e($alert['message']) ?></div><?php endif; ?>
<?php if (!$plan): ?>
<div class="plans-columns"><section class="admin-panel"><h2>Create a planning document</h2><p>All new plans are private drafts.</p><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="create"><label>Plan type</label><select name="plan_type"><option value="ABYIP" <?= $type==='ABYIP'?'selected':'' ?>>ABYIP · Annual investment program</option><option value="CBYDP" <?= $type==='CBYDP'?'selected':'' ?>>CBYDP · Three-year youth development plan</option></select><label>Title</label><input name="title" maxlength="200" required placeholder="e.g. SK Bantog Youth Investment Program"><label>Starting fiscal year</label><input name="fiscal_year" type="number" min="2020" max="2098" value="2027" required><label>Overview / planning notes</label><textarea name="description" rows="4" maxlength="5000"></textarea><button class="btn btn-primary">Create draft plan →</button></form></section><section class="admin-panel"><h2>Existing plans</h2><?php if (!$plans): ?><p class="plans-empty">No plans created yet. Start with the FY 2027 ABYIP.</p><?php endif; ?><?php foreach($plans as $p): ?><a class="plan-list-item" href="plans.php?id=<?= (int)$p['id'] ?>"><span><b><?= e($p['title']) ?></b><small><?= e($p['plan_type']) ?> · <?= (int)$p['fiscal_year'] ?><?= $p['end_year']!=$p['fiscal_year']?'–'.(int)$p['end_year']:'' ?> · <?= (int)$p['activity_count'] ?> activities</small></span><em><?= e(ucfirst($p['status'])) ?></em></a><?php endforeach; ?></section></div>
<?php else: ?>
<div class="plans-subnav"><a href="plans.php">← All plans</a><span><?= e($plan['plan_type']) ?> · FY <?= (int)$plan['fiscal_year'] ?><?= $plan['end_year']!=$plan['fiscal_year']?'–'.(int)$plan['end_year']:'' ?></span><span class="status-tag <?= e($plan['status']) ?>"><?= e(ucfirst($plan['status'])) ?></span></div>
<section class="sheet-summary"><div><span class="overline"><?= e($plan['plan_type']) ?> · FY <?= (int)$plan['fiscal_year'] ?></span><h2><?= e($plan['title']) ?></h2><p><?= e($plan['description'] ?: 'Edit the planning worksheet below. Each row is a program or activity.') ?></p></div><div><strong id="sheet-count"><?= count($items) ?></strong> activities <strong id="sheet-total"><?= e(cash(array_sum(array_map(fn($x)=>(float)($x['budget']??0),$items)))) ?></strong> proposed allocation</div></section>
<div class="sheet-toolbar"><span class="sheet-tab"><?= e($plan['plan_type']) ?> · WORKSHEET</span><div class="sheet-tools"><button type="button" id="add-row" class="btn btn-outline" <?= $plan['status']!=='draft'?'disabled':'' ?>>+ Add row</button><button type="button" id="export-csv" class="btn btn-outline">Export CSV</button><button type="button" id="print-sheet" class="btn btn-outline">Print</button><button type="submit" form="sheet-form" class="btn btn-primary" <?= $plan['status']!=='draft'?'disabled':'' ?>>Save worksheet</button></div></div>
<p class="sheet-hint">Edit directly in the cells. Save to keep your changes. Blank rows are ignored. Only draft plans are editable. CSV export can be opened in Excel.</p>
<form id="sheet-form" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save_sheet"><input type="hidden" name="id" value="<?= $id ?>"><div class="sheet-scroll"><table class="sheet-table" id="plan-sheet"><thead><tr><th>#</th><th>Center of participation *</th><th>Youth concern *</th><th>Objective *</th><th>Program / project / activity *</th><th>Target beneficiaries</th><th>Proposed budget (₱)</th><th>Implementation period</th><th>Responsible person</th><th class="no-print">Action</th></tr></thead><tbody id="sheet-body"><?php foreach($items as $n=>$item): ?><tr><th class="row-number"><?= $n+1 ?></th><?php foreach(['center_of_participation','youth_concern','objective','program_title','target_beneficiaries','budget','implementation_period','responsible_person'] as $field): ?><td><input data-field="<?= e($field) ?>" aria-label="<?= e($field) ?>" name="rows[<?= $n ?>][<?= e($field) ?>]" value="<?= e((string)($item[$field]??'')) ?>" <?= $field==='budget'?'type="number" min="0" step="0.01"':'' ?> <?= $plan['status']!=='draft'?'readonly':'' ?>></td><?php endforeach; ?><td class="no-print"><button type="button" class="sheet-remove" <?= $plan['status']!=='draft'?'disabled':'' ?> aria-label="Remove row">×</button></td></tr><?php endforeach; ?></tbody><tfoot><tr><td colspan="6">TOTAL PROPOSED ALLOCATION</td><td id="sheet-footer-total"><?= e(cash(array_sum(array_map(fn($x)=>(float)($x['budget']??0),$items)))) ?></td><td colspan="3"></td></tr></tfoot></table></div></form>
<div class="sheet-publication"><div><h3>Publication</h3><p>Publish only after the signed official plan has been approved and verified. Upload the signed PDF separately in Document Management.</p></div><?php if(may_publish($operator)): ?><form method="post" class="sheet-publish-form"><?= csrf_field() ?><input type="hidden" name="action" value="visibility"><input type="hidden" name="id" value="<?= $id ?>"><select name="status" aria-label="Publication status"><option value="draft" <?= $plan['status']==='draft'?'selected':'' ?>>Draft · Private</option><option value="published" <?= $plan['status']==='published'?'selected':'' ?>>Published · Public</option><option value="archived" <?= $plan['status']==='archived'?'selected':'' ?>>Archived · Private</option></select><button class="btn btn-outline">Update status</button></form><?php endif; ?></div><script defer src="../assets/js/plans-sheet.js"></script>
<?php endif; ?></main></div></body></html>
