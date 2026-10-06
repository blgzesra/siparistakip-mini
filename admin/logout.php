<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

// Logout changes state, so it only runs as a POST (CSRF-checked in auth.php).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(is_logged_in() ? 'admin/index.php' : 'admin/login.php');
}

logout_user();
redirect('admin/login.php');
