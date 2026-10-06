<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

ensure_orders_payment_status_column();

$pdo = db();
$customerId = (int) ($_GET['id'] ?? 0);

if ($customerId <= 0) {
    redirect('admin/customers.php');
}

$stmt = $pdo->prepare('SELECT id, name, phone, address, notes, created_at FROM customers WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect('admin/customers.php');
}

$ordersStmt = $pdo->prepare(
    'SELECT
        o.id,
        o.order_no,
        o.status,
        o.payment_status,
        o.total_amount,
        o.created_at,
        oi.product_name,
        oi.quantity,
        oi.unit_price
     FROM orders o
     LEFT JOIN (
        SELECT oi1.*
        FROM order_items oi1
        INNER JOIN (
            SELECT order_id, MIN(id) AS first_item_id
            FROM order_items
            GROUP BY order_id
        ) first_items ON first_items.first_item_id = oi1.id
     ) oi ON oi.order_id = o.id
     WHERE o.customer_id = :customer_id
     ORDER BY o.created_at DESC, o.id DESC'
);
$ordersStmt->execute(['customer_id' => $customerId]);
$orders = $ordersStmt->fetchAll();

$totalSpent = 0.0;
foreach ($orders as $order) {
    $totalSpent += (float) $order['total_amount'];
}

$page_title = 'Müşteri Detayı';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1><?php echo e($customer['name']); ?></h1>
            <p>Müşteri bilgileri ve önceki siparişleri.</p>
        </div>
        <div class="action-row">
            <a class="btn btn-secondary" href="<?php echo url('admin/customers.php?id=' . (int) $customer['id']); ?>">Düzenle</a>
            <a class="btn btn-secondary" href="<?php echo url('admin/customers.php'); ?>">Listeye Dön</a>
        </div>
    </div>

    <section class="metric-grid detail-metrics">
        <div class="metric-card">
            <span>Telefon</span>
            <strong><?php echo e($customer['phone'] ?: '-'); ?></strong>
        </div>
        <div class="metric-card">
            <span>Sipariş Sayısı</span>
            <strong><?php echo count($orders); ?></strong>
        </div>
        <div class="metric-card">
            <span>Toplam Tutar</span>
            <strong><?php echo e(format_money($totalSpent)); ?></strong>
        </div>
        <div class="metric-card">
            <span>Kayıt Tarihi</span>
            <strong><?php echo e(date('d.m.Y', strtotime($customer['created_at']))); ?></strong>
        </div>
    </section>

    <section class="panel detail-panel">
        <div class="detail-grid">
            <div>
                <h2>Adres</h2>
                <p><?php echo nl2br(e($customer['address'] ?: '-')); ?></p>
            </div>
            <div>
                <h2>Not</h2>
                <p><?php echo nl2br(e($customer['notes'] ?: '-')); ?></p>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Önceki Siparişler</h2>
        </div>

        <?php if ($orders): ?>
            <div class="table-wrap">
                <table class="data-table orders-table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Ürün</th>
                            <th>Adet</th>
                            <th>Toplam</th>
                            <th>Sipariş Durumu</th>
                            <th>Ödeme</th>
                            <th>Tarih</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo e($order['order_no']); ?></strong></td>
                                <td>
                                    <?php echo e($order['product_name'] ?: 'Ürün yok'); ?>
                                    <span class="table-subtext"><?php echo e(format_money((float) ($order['unit_price'] ?? 0))); ?></span>
                                </td>
                                <td><?php echo (int) ($order['quantity'] ?? 1); ?></td>
                                <td><strong><?php echo e(format_money((float) $order['total_amount'])); ?></strong></td>
                                <td>
                                    <span class="status-badge status-<?php echo e($order['status']); ?>">
                                        <?php echo e(order_status_label($order['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge payment-<?php echo e($order['payment_status']); ?>">
                                        <?php echo e(payment_status_label($order['payment_status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo e(date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                                <td>
                                    <a class="btn btn-secondary btn-small" href="<?php echo url('admin/order_form.php?id=' . (int) $order['id']); ?>">Düzenle</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>Henüz sipariş yok</h2>
                <p>Bu müşteriye ait önceki sipariş bulunmuyor.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
