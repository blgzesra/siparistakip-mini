<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('admin/index.php');
}

$error = '';
$email = '';
$notice = isset($_GET['expired']) ? 'Uzun süre işlem yapılmadığı için oturumunuz kapatıldı. Lütfen tekrar giriş yapın.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $notice = '';

    ensure_login_attempts_table();
    $ip = client_ip();
    $lockRemaining = login_lock_remaining($ip);

    if ($lockRemaining > 0) {
        $error = login_lock_message($lockRemaining);
    } elseif ($email === '' || $password === '') {
        $error = 'E-posta ve şifre zorunludur.';
    } elseif (login_user($email, $password)) {
        clear_failed_logins($ip);
        redirect('admin/index.php');
    } else {
        $lockSeconds = record_failed_login($ip);
        $error = $lockSeconds > 0 ? login_lock_message($lockSeconds) : 'E-posta veya şifre hatalı.';
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Giriş - <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-card">
            <div class="brand-block">
                <span class="brand-mark">ST</span>
                <div>
                    <h1><?php echo e(APP_NAME); ?></h1>
                    <p>Yönetim paneli</p>
                </div>
            </div>

            <p class="login-intro">Siparişleri, müşterileri ve ürünleri tek ekrandan güvenle yönetin.</p>

            <?php if ($notice !== ''): ?>
                <div class="alert alert-success"><?php echo e($notice); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="post" class="form-stack" autocomplete="off">
                <?php echo csrf_field(); ?>
                <label>
                    <span>E-posta</span>
                    <input
                        type="email"
                        name="email"
                        placeholder="ornek@firma.com"
                        value="<?php echo e($email); ?>"
                        required
                        autofocus
                    >
                </label>
                <label>
                    <span>Şifre</span>
                    <input type="password" name="password" placeholder="Şifrenizi girin" required>
                </label>
                <button type="submit" class="btn btn-primary">Giriş Yap</button>
            </form>

            <p class="login-footnote">Satış panelinize devam etmek için giriş yapın.</p>
        </section>
    </main>
</body>
</html>
