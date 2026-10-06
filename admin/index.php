<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$todayStats = db()->query(
    'SELECT COUNT(*) AS order_count, COALESCE(SUM(total_amount), 0) AS total_revenue
     FROM orders
     WHERE created_at >= CURDATE()
       AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)'
)->fetch();

$statusStats = db()->query(
    "SELECT
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_orders
     FROM orders"
)->fetch();

$latestOrdersStmt = db()->query(
    'SELECT o.order_no, o.status, o.total_amount, o.created_at, c.name AS customer_name
     FROM orders o
     LEFT JOIN customers c ON c.id = o.customer_id
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT 5'
);
$latestOrders = $latestOrdersStmt->fetchAll();

$page_title = 'Panel';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1>Genel Bakış</h1>
            <p>Güncel sipariş durumunu hızlıca takip edin.</p>
        </div>
    </div>

    <div class="metric-grid">
        <section class="metric-card">
            <span>Bugünkü Sipariş</span>
            <strong><?php echo (int) ($todayStats['order_count'] ?? 0); ?></strong>
        </section>
        <section class="metric-card">
            <span>Bugünkü Toplam Gelir</span>
            <strong><?php echo e(format_money((float) ($todayStats['total_revenue'] ?? 0))); ?></strong>
        </section>
        <section class="metric-card">
            <span>Bekleyen Sipariş</span>
            <strong><?php echo (int) ($statusStats['pending_orders'] ?? 0); ?></strong>
        </section>
        <section class="metric-card">
            <span>Tamamlanan Sipariş</span>
            <strong><?php echo (int) ($statusStats['completed_orders'] ?? 0); ?></strong>
        </section>
    </div>

    <section class="panel">
        <div class="panel-head">
            <h2>Son 5 Sipariş</h2>
        </div>

        <?php if ($latestOrders): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
                            <th>Durum</th>
                            <th>Tutar</th>
                            <th>Tarih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latestOrders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($order['order_no']); ?></strong>
                                </td>
                                <td><?php echo e($order['customer_name'] ?: 'Misafir'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo e($order['status']); ?>">
                                        <?php echo e(order_status_label($order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo e(format_money((float) $order['total_amount'])); ?></td>
                                <td><?php echo e(date('d.m.Y H:i', strtotime($order['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="muted">Henüz sipariş bulunmuyor.</p>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
