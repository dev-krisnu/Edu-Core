<?php
/** Shared styles for dark aurora portal pages (student/, faculty/, etc.) */
$portalBase = $portalBase ?? '..';
?>
<link href="<?= htmlspecialchars($portalBase, ENT_QUOTES, 'UTF-8') ?>/style.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= htmlspecialchars($portalBase, ENT_QUOTES, 'UTF-8') ?>/assets/css/educore.css" rel="stylesheet">
<link href="<?= htmlspecialchars($portalBase, ENT_QUOTES, 'UTF-8') ?>/assets/css/themes.css" rel="stylesheet">
<link href="<?= htmlspecialchars($portalBase, ENT_QUOTES, 'UTF-8') ?>/assets/css/portal-pages.css" rel="stylesheet">
