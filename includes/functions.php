<?php
require_once __DIR__ . '/config.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url($path);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function active_nav(string $file): string
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $file ? 'is-active' : '';
}

function format_money(float $amount): string
{
    return number_format($amount, 2, ',', '.') . ' TL';
}

function setting_value(string $key, ?string $default = null): ?string
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        $rows = db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();

        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings[$key] ?? $default;
}

function order_status_options(): array
{
    return [
        'pending' => 'Yeni',
        'preparing' => 'Hazırlanıyor',
        'shipped' => 'Kargoda',
        'completed' => 'Teslim Edildi',
        'cancelled' => 'İptal',
    ];
}

function order_status_label(string $status): string
{
    $labels = order_status_options();

    return $labels[$status] ?? ucfirst($status);
}

function payment_status_options(): array
{
    return [
        'waiting' => 'Bekliyor',
        'paid' => 'Ödendi',
        'partial' => 'Kısmi Ödendi',
    ];
}

function payment_status_label(string $status): string
{
    $labels = payment_status_options();

    return $labels[$status] ?? ucfirst($status);
}

function ensure_settings_whatsapp_number_column(): void
{
    $stmt = db()->prepare(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'settings'
           AND COLUMN_NAME = 'whatsapp_number'"
    );
    $stmt->execute();

    if ((int) $stmt->fetchColumn() === 0) {
        db()->exec(
            "ALTER TABLE settings
             ADD COLUMN whatsapp_number VARCHAR(40) DEFAULT NULL AFTER setting_value"
        );
    }
}

function ensure_orders_payment_status_column(): void
{
    $stmt = db()->prepare(
        "SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'orders'
           AND COLUMN_NAME = 'payment_status'"
    );
    $stmt->execute();

    if ((int) $stmt->fetchColumn() === 0) {
        db()->exec(
            "ALTER TABLE orders
             ADD COLUMN payment_status VARCHAR(40) NOT NULL DEFAULT 'waiting' AFTER status"
        );
    }
}

function generate_order_no(PDO $pdo): string
{
    for ($i = 0; $i < 10; $i++) {
        $orderNo = 'SP-' . date('Ymd') . '-' . random_int(1000, 9999);

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE order_no = :order_no');
        $stmt->execute(['order_no' => $orderNo]);

        if ((int) $stmt->fetchColumn() === 0) {
            return $orderNo;
        }
    }

    return 'SP-' . date('YmdHis') . '-' . random_int(10, 99);
}

function whatsapp_phone(?string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone ?? '');

    if (strlen($digits) === 10) {
        return '90' . $digits;
    }

    if (strlen($digits) === 11 && strpos($digits, '0') === 0) {
        return '9' . $digits;
    }

    return $digits;
}

function whatsapp_order_url(array $order): string
{
    $message = sprintf(
        "Merhaba, %s numaralı siparişiniz: %s x %d. Toplam: %s. Durum: %s.",
        $order['order_no'] ?? '',
        $order['product_name'] ?? 'Ürün',
        (int) ($order['quantity'] ?? 1),
        format_money((float) ($order['total_amount'] ?? 0)),
        order_status_label((string) ($order['status'] ?? 'pending'))
    );

    $phone = whatsapp_phone($order['customer_phone'] ?? '');

    if ($phone === '') {
        $phone = whatsapp_phone(setting_value('whatsapp_number', ''));
    }

    $query = 'text=' . rawurlencode($message);

    if ($phone !== '') {
        return 'https://wa.me/' . $phone . '?' . $query;
    }

    return 'https://wa.me/?' . $query;
}
