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
<div class="plans-shell"><header class="plans-top"><a href="index.php?view=overview">← Back to dashboard</a><div><strong>SK BANTOG</strong><small>2027 · OFFICIALS WORKSPACE</small></div><a href="../index.php?page=plans" target="_blank" rel="noopener">Public plans ↗</a></header>
<main class="plans-main"><div class="plans-heading"><div><span class="overline">YOUTH DEVELOPMENT PLANNING</span><h1>CBYDP & ABYIP workspace</h1><p>Prepare structured youth plans, track activities, and publish only officially approved information.</p></div></div>
<?php if ($alert=take_flash()): ?><div class="alert <?= e($alert['kind']) ?>"><?= e($alert['message']) ?></div><?php endif; ?>
<?php if (!$plan): ?>
<div class="plans-columns"><section class="admin-panel"><h2>Create a planning document</h2><p>All new plans are private drafts.</p><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="create"><label>Plan type</label><select name="plan_type"><option value="ABYIP" <?= $type==='ABYIP'?'selected':'' ?>>ABYIP · Annual investment program</option><option value="CBYDP" <?= $type==='CBYDP'?'selected':'' ?>>CBYDP · Three-year youth development plan</option></select><label>Title</label><input name="title" maxlength="200" required placeholder="e.g. SK Bantog Youth Investment Program"><label>Starting fiscal year</label><input name="fiscal_year" type="number" min="2020" max="2098" value="2027" required><label>Overview / planning notes</label><textarea name="description" rows="4" maxlength="5000"></textarea><button class="btn btn-primary">Create draft plan →</button></form></section><section class="admin-panel"><h2>Existing plans</h2><?php if (!$plans): ?><p class="plans-empty">No plans created yet. Start with the FY 2027 ABYIP.</p><?php endif; ?><?php foreach($plans as $p): ?><a class="plan-list-item" href="plans.php?id=<?= (int)$p['id'] ?>"><span><b><?= e($p['title']) ?></b><small><?= e($p['plan_type']) ?> · <?= (int)$p['fiscal_year'] ?><?= $p['end_year']!=$p['fiscal_year']?'–'.(int)$p['end_year']:'' ?> · <?= (int)$p['activity_count'] ?> activities</small></span><em><?= e(ucfirst($p['status'])) ?></em></a><?php endforeach; ?></section></div>
<?php else: ?>
<div class="plans-subnav"><a href="plans.php">← All plans</a><span><?= e($plan['plan_type']) ?> · FY <?= (int)$plan['fiscal_year'] ?><?= $plan['end_year']!=$plan['fiscal_year']?'–'.(int)$plan['end_year']:'' ?></span><span class="status-tag <?= e($plan['status']) ?>"><?= e(ucfirst($plan['status'])) ?></span></div>
<div class="plans-columns"><section class="admin-panel"><h2><?= e($plan['title']) ?></h2><p><?= e($plan['description'] ?: 'No overview added.') ?></p><div class="plans-stat"><strong><?= count($items) ?></strong><span>Planned activities</span><strong><?= e(cash(array_sum(array_map(fn($x)=>(float)($x['budget']??0),$items)))) ?></strong><span>Total entered allocations</span></div>
<?php if(may_publish($operator)): ?><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="visibility"><input type="hidden" name="id" value="<?= $id ?>"><label>Public visibility</label><select name="status"><option value="draft" <?= $plan['status']==='draft'?'selected':'' ?>>Draft · Private</option><option value="published" <?= $plan['status']==='published'?'selected':'' ?>>Published · Public</option><option value="archived" <?= $plan['status']==='archived'?'selected':'' ?>>Archived · Private</option></select><small>Publish only after the plan has been approved and the entries checked against the signed document.</small><button class="btn btn-primary">Update publication status</button></form><?php endif; ?></section>
<section class="admin-panel"><h2>Add program or activity</h2><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="add_item"><input type="hidden" name="id" value="<?= $id ?>"><label>Center of participation *</label><select name="center_of_participation" required><option value="">Select focus area</option><?php foreach(['Education','Health','Economic Empowerment','Social Inclusion and Equity','Peace Building and Security','Governance','Active Citizenship','Environment','Global Mobility','Agriculture'] as $focus): ?><option><?= e($focus) ?></option><?php endforeach; ?></select><label>Youth concern *</label><textarea name="youth_concern" rows="2" required></textarea><label>Objective / expected result *</label><textarea name="objective" rows="2" required></textarea><label>Program, project or activity *</label><input name="program_title" maxlength="200" required><label>Target beneficiaries / indicators</label><input name="target_beneficiaries" maxlength="150"><label>Proposed allocation (₱)</label><input name="budget" type="number" step=".01" min="0"><label>Implementation period</label><input name="implementation_period" maxlength="150" placeholder="e.g. January–March 2027"><label>Responsible official / committee</label><input name="responsible_person" maxlength="150"><button class="btn btn-primary">Add activity →</button></form></section></div>
<section class="admin-panel plans-activities"><h2>Plan activities</h2><?php if(!$items): ?><p class="plans-empty">No activities entered yet.</p><?php else: ?><div class="table-scroll"><table><thead><tr><th>Focus area / activity</th><th>Concern and objective</th><th>Target / period</th><th>Allocation</th><th></th></tr></thead><tbody><?php foreach($items as $item): ?><tr><td><strong><?= e($item['program_title']) ?></strong><small><?= e($item['center_of_participation']) ?></small></td><td><?= e($item['youth_concern']) ?><small><?= e($item['objective']) ?></small></td><td><?= e($item['target_beneficiaries']) ?><small><?= e($item['implementation_period']) ?></small></td><td><?= $item['budget']===null?'—':e(cash($item['budget'])) ?></td><td><?php if(may_publish($operator)): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove_item"><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>"><button class="mini-button" type="submit">Remove</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?></main></div></body></html>
