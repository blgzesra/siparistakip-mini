<?php
$page_title = $page_title ?? 'Panel';
$user = current_user();
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($page_title); ?> - <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn menu-toggle" type="button" data-sidebar-toggle aria-label="Menü">&#9776;</button>
                <div class="topbar-title"><?php echo e($page_title); ?></div>
            </div>
            <div class="topbar-user">
                <span><?php echo e($user['name'] ?? 'Yönetici'); ?></span>
                <a href="<?php echo url('admin/logout.php'); ?>">Çıkış</a>
            </div>
        </header>
