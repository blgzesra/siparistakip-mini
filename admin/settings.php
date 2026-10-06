<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();
$errors = [];

ensure_settings_whatsapp_number_column();

$settingsRows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
$settings = [];

foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$form = [
    'app_name' => $settings['app_name'] ?? APP_NAME,
    'currency' => $settings['currency'] ?? 'TRY',
    'whatsapp_number' => $settings['whatsapp_number'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'app_name' => trim($_POST['app_name'] ?? ''),
        'currency' => strtoupper(trim($_POST['currency'] ?? 'TRY')),
        'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
    ];

    if ($form['app_name'] === '') {
        $errors[] = 'Panel adı zorunludur.';
    }

    if ($form['currency'] === '') {
        $errors[] = 'Para birimi zorunludur.';
    }

    if ($form['whatsapp_number'] !== '' && !preg_match('/^[0-9\s+\-()]+$/', $form['whatsapp_number'])) {
        $errors[] = 'WhatsApp numarası sadece rakam, boşluk, +, parantez ve tire içerebilir.';
    }

    if (!$errors) {
        $form['whatsapp_number'] = whatsapp_phone($form['whatsapp_number']);

        $stmt = $pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value)
             VALUES (:setting_key, :setting_value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($form as $key => $value) {
            $stmt->execute([
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }

        $columnStmt = $pdo->prepare(
            "UPDATE settings
             SET whatsapp_number = :whatsapp_number
             WHERE setting_key = 'whatsapp_number'"
        );
        $columnStmt->execute(['whatsapp_number' => $form['whatsapp_number']]);

        redirect('admin/settings.php?saved=1');
    }
}

$page_title = 'Ayarlar';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1>Ayarlar</h1>
            <p>Temel işletme ayarlarını güncelleyin.</p>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Ayarlar kaydedildi.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <section class="panel form-panel">
        <div class="panel-head">
            <h2>Genel Ayarlar</h2>
        </div>

        <form class="form-stack" method="post">
            <div class="form-grid">
                <label>
                    Panel Adı
                    <input type="text" name="app_name" value="<?php echo e($form['app_name']); ?>" required>
                </label>

                <label>
                    Para Birimi
                    <input type="text" name="currency" value="<?php echo e($form['currency']); ?>" maxlength="10" required>
                </label>

                <label>
                    WhatsApp Numarası
                    <input type="text" name="whatsapp_number" value="<?php echo e($form['whatsapp_number']); ?>" placeholder="905551112233" maxlength="40">
                    <span class="form-help">Başında ülke kodu ile girin. Örnek: 905551112233</span>
                </label>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Kaydet</button>
            </div>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
