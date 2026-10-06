<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['auth_user']['id']);
}

function current_user(): ?array
{
    return is_logged_in() ? $_SESSION['auth_user'] : null;
}

function login_user(string $email, string $password): bool
{
    $email = strtolower(trim($email));

    if ($email === '' || $password === '') {
        return false;
    }

    $stmt = db()->prepare(
        'SELECT id, name, email, password_hash, role
         FROM users
         WHERE email = :email AND is_active = 1
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);

    $_SESSION['auth_user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    $update = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $update->execute(['id' => (int) $user['id']]);

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('admin/login.php');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_is_valid($token): bool
{
    return is_string($token)
        && $token !== ''
        && !empty($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Every POST to a page that includes this file must carry the session's token.
function require_valid_csrf(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || csrf_is_valid($_POST['csrf_token'] ?? null)) {
        return;
    }

    http_response_code(400);
    ?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Geçersiz istek - <?php echo e(APP_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-card">
            <div class="alert alert-error">Form güvenlik doğrulaması başarısız oldu. Sayfanın süresi dolmuş olabilir.</div>
            <p>Lütfen sayfayı yeniden açıp işlemi tekrar deneyin.</p>
            <a class="btn btn-primary" href="<?php echo url('admin/index.php'); ?>">Panele dön</a>
        </section>
    </main>
</body>
</html>
<?php
    exit;
}

require_valid_csrf();
