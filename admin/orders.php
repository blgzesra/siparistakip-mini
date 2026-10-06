<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

ensure_orders_payment_status_column();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $orderId = (int) ($_POST['id'] ?? 0);

    if ($orderId > 0) {
        $stmt = db()->prepare('DELETE FROM orders WHERE id = :id');
        $stmt->execute(['id' => $orderId]);
    }

    redirect('admin/orders.php?deleted=1');
}

$ordersStmt = db()->query(
    'SELECT
        o.id,
        o.order_no,
        o.status,
        o.payment_status,
        o.total_amount,
        o.created_at,
        c.name AS customer_name,
        c.phone AS customer_phone,
        oi.product_name,
        oi.quantity,
        oi.unit_price
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     LEFT JOIN (
        SELECT oi1.*
        FROM order_items oi1
        INNER JOIN (
            SELECT order_id, MIN(id) AS first_item_id
            FROM order_items
            GROUP BY order_id
        ) first_items ON first_items.first_item_id = oi1.id
     ) oi ON oi.order_id = o.id
     ORDER BY o.created_at DESC, o.id DESC'
);
$orders = $ordersStmt->fetchAll();

$page_title = 'Siparişler';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1>Siparişler</h1>
            <p>Müşteri, ürün, ödeme ve kargo durumlarını tek ekrandan takip edin.</p>
        </div>
        <a class="btn btn-primary" href="<?php echo url('admin/order_form.php'); ?>">Yeni Sipariş</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Sipariş kaydedildi.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Sipariş silindi.</div>
    <?php endif; ?>

    <section class="panel">
        <?php if ($orders): ?>
            <div class="table-wrap">
                <table class="data-table orders-table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
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
                                    <?php echo e($order['customer_name'] ?: 'Müşteri yok'); ?>
                                    <?php if (!empty($order['customer_phone'])): ?>
                                        <span class="table-subtext"><?php echo e($order['customer_phone']); ?></span>
                                    <?php endif; ?>
                                </td>
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
                                    <div class="action-row">
                                        <a class="btn btn-secondary btn-small" href="<?php echo url('admin/order_form.php?id=' . (int) $order['id']); ?>">Düzenle</a>
                                        <a class="btn btn-whatsapp btn-small" href="<?php echo e(whatsapp_order_url($order)); ?>" target="_blank" rel="noopener">WhatsApp</a>
                                        <form method="post" onsubmit="return confirm('Bu siparişi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $order['id']; ?>">
                                            <button class="btn btn-danger btn-small" type="submit">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>Henüz sipariş yok</h2>
                <p>İlk siparişi eklemek için "Yeni Sipariş" düğmesini kullanın.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
