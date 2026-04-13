<?php
/**
 * STUDIUM-CAT SETUP CHECKER
 * 
 * ⚠️  RUN ONCE THEN DELETE THIS FILE FROM THE SERVER ⚠️
 * 
 * Visit: https://dev.studium.cat/setup_check.php
 * This verifies your environment and creates missing tables.
 */

// Very basic password protection
$ACCESS_KEY = 'studium2024setup';
if (($_GET['key'] ?? '') !== $ACCESS_KEY) {
    die('<h2 style="color:red;font-family:sans-serif;">Access denied. Add ?key=studium2024setup to URL.</h2>');
}

include __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Studium Setup Check</title>
<style>
body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;color:#1e293b;padding:30px;max-width:900px;margin:0 auto}
h1{color:#1B4965}
.card{background:#fff;border-radius:12px;padding:20px;margin:16px 0;box-shadow:0 2px 8px rgba(0,0,0,.07)}
.ok{color:#16a34a;font-weight:bold}
.fail{color:#dc2626;font-weight:bold}
.warn{color:#d97706;font-weight:bold}
table{width:100%;border-collapse:collapse}
td,th{padding:10px 14px;text-align:left;border-bottom:1px solid #e2e8f0}
th{background:#f1f5f9;font-weight:600}
</style>
</head>
<body>
<h1>🛠 Studium-CAT Setup Check</h1>

<div class="card">
<h2>🌍 Environment</h2>
<table>
<tr><th>Key</th><th>Value</th><th>Status</th></tr>
<tr>
  <td>Detected Environment</td>
  <td><?= APP_ENV ?></td>
  <td class="<?= IS_PRODUCTION ? 'ok' : 'warn' ?>"><?= IS_PRODUCTION ? '✅ Production' : '⚠️ Local Dev' ?></td>
</tr>
<tr>
  <td>PHP Version</td>
  <td><?= phpversion() ?></td>
  <td class="<?= version_compare(phpversion(), '8.0', '>=') ? 'ok' : 'fail' ?>"><?= version_compare(phpversion(), '8.0', '>=') ? '✅ OK' : '❌ Needs PHP 8.0+' ?></td>
</tr>
<tr>
  <td>HTTP_HOST</td>
  <td><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') ?></td>
  <td class="ok">ℹ️ Info</td>
</tr>
<tr>
  <td>Display Errors</td>
  <td><?= ini_get('display_errors') ? 'ON' : 'OFF' ?></td>
  <td class="<?= (!IS_PRODUCTION && ini_get('display_errors')) || (IS_PRODUCTION && !ini_get('display_errors')) ? 'ok' : 'warn' ?>">
    <?= IS_PRODUCTION && !ini_get('display_errors') ? '✅ Hidden (safe)' : (IS_PRODUCTION && ini_get('display_errors') ? '⚠️ Visible (fix config.php)' : '✅ Visible (OK for local)') ?>
  </td>
</tr>
</table>
</div>

<div class="card">
<h2>🗄️ Database</h2>
<table>
<tr><th>Item</th><th>Value</th><th>Status</th></tr>
<?php
$dbOk = $con && !mysqli_connect_errno();
?>
<tr>
  <td>Main DB Connection</td>
  <td><?= DB_HOST ?> / <?= DB_NAME ?></td>
  <td class="<?= $dbOk ? 'ok' : 'fail' ?>"><?= $dbOk ? '✅ Connected' : '❌ Failed: ' . mysqli_connect_error() ?></td>
</tr>
<?php if ($dbOk): ?>
<tr>
  <td>DB Charset</td>
  <td><?php $r = mysqli_query($con, "SELECT @@character_set_database"); $row = mysqli_fetch_row($r); echo $row[0]; ?></td>
  <td class="ok">ℹ️ Info</td>
</tr>
<?php endif; ?>
</table>
</div>

<div class="card">
<h2>📋 Required Tables</h2>
<table>
<tr><th>Table</th><th>Status</th><th>Action</th></tr>
<?php
$requiredTables = [
    'login'                 => 'Core user accounts table',
    'temporary_exam_state'  => 'NGN exam pause/resume',
    'temporary_exam_result' => 'NGN exam answer storage',
    'review'                => 'Traditional exam answers',
    'question'              => 'Traditional questions',
    'exam_results'          => 'Final exam results',
    'topics'                => 'Topic/bundle structure',
];

foreach ($requiredTables as $table => $desc) {
    $r = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    $exists = $r && mysqli_num_rows($r) > 0;
    $countSql = $exists ? mysqli_query($con, "SELECT COUNT(*) AS n FROM `$table`") : null;
    $count = $countSql ? mysqli_fetch_assoc($countSql)['n'] : '-';
    echo "<tr>";
    echo "<td><strong>$table</strong><br><small style='color:#64748b'>$desc</small></td>";
    echo "<td class='" . ($exists ? 'ok' : 'fail') . "'>" . ($exists ? "✅ Exists ($count rows)" : '❌ MISSING') . "</td>";
    echo "<td>" . ($exists ? '—' : '<span class="warn">⚠️ Will be auto-created by config.php on next request</span>') . "</td>";
    echo "</tr>";
}
?>
</table>
</div>

<div class="card">
<h2>🔧 Quick Fixes Applied</h2>
<ul>
  <li>✅ <code>temporary_exam_state</code> — auto-created by config.php if missing</li>
  <li>✅ <code>temporary_exam_result</code> — auto-created by config.php if missing</li>
  <li>✅ Error display hidden from users on production</li>
  <li>✅ Session started safely (no double-start errors)</li>
  <li>✅ DB credentials auto-switch between localhost and Hostinger</li>
</ul>
</div>

<div class="card" style="background:#fef2f2;border:1px solid #fecaca">
<h2>🗑️ Security Reminder</h2>
<p><strong>Delete this file from your server after verifying everything:</strong></p>
<code>Delete: /public_html/dev/setup_check.php</code>
<p>Or access it only via: <code>?key=studium2024setup</code></p>
</div>

</body>
</html>
