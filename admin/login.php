<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('admin/index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'E-posta ve şifre zorunludur.';
    } elseif (login_user($email, $password)) {
        redirect('admin/index.php');
    } else {
        $error = 'E-posta veya şifre hatalı.';
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

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo e($error); ?></div>
            <?php endif; ?>

            <form method="post" class="form-stack" autocomplete="off">
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
