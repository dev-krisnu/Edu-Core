<?php
/**
 * Quick path diagnostic — delete after confirming URLs work.
 */
require_once __DIR__ . '/config/app.php';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html><head><title>EduCore Path Check</title>
<style>body{font-family:system-ui;max-width:720px;margin:40px auto;padding:20px;line-height:1.6}
code{background:#f1f5f9;padding:2px 8px;border-radius:4px;word-break:break-all}
.ok{color:#059669}.warn{color:#d97706}</style></head>
<body>
<h1>EduCore path diagnostic</h1>
<p><strong>Detected BASE_URL:</strong><br><code><?= htmlspecialchars(BASE_URL ?: '(empty — project at web root)') ?></code></p>
<p><strong>Your login URL should be:</strong><br><code class="ok"><?= htmlspecialchars('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('index.php')) ?></code></p>
<p><strong>Student dashboard after login:</strong><br><code><?= htmlspecialchars('http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('student/dashboard.php')) ?></code></p>
<p class="warn">If these look correct, log in at the login URL above. Delete this file when done.</p>
<p><a href="<?= htmlspecialchars(url('index.php')) ?>">→ Go to login</a></p>
</body></html>
