<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

ensure_orders_payment_status_column();

$pdo = db();
$orderId = (int) ($_GET['id'] ?? 0);
$isEdit = $orderId > 0;
$errors = [];

$customers = $pdo->query(
    'SELECT id, name, phone
     FROM customers
     ORDER BY name ASC'
)->fetchAll();

$products = $pdo->query(
    'SELECT id, name, sku, price, stock_quantity
     FROM products
     ORDER BY name ASC'
)->fetchAll();

$customerIds = array_map('intval', array_column($customers, 'id'));
$productMap = [];

foreach ($products as $product) {
    $productMap[(int) $product['id']] = $product;
}

$form = [
    'customer_id' => '',
    'product_id' => '',
    'quantity' => 1,
    'status' => 'pending',
    'payment_status' => 'waiting',
    'notes' => '',
];

if ($isEdit) {
    $stmt = $pdo->prepare(
        'SELECT
            o.id,
            o.customer_id,
            o.status,
            o.payment_status,
            o.notes,
            oi.product_id,
            oi.quantity
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
         WHERE o.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        redirect('admin/orders.php');
    }

    $form = [
        'customer_id' => (string) ($order['customer_id'] ?? ''),
        'product_id' => (string) ($order['product_id'] ?? ''),
        'quantity' => (int) ($order['quantity'] ?? 1),
        'status' => $order['status'] ?: 'pending',
        'payment_status' => $order['payment_status'] ?: 'waiting',
        'notes' => $order['notes'] ?? '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'customer_id' => trim($_POST['customer_id'] ?? ''),
        'product_id' => trim($_POST['product_id'] ?? ''),
        'quantity' => (int) ($_POST['quantity'] ?? 1),
        'status' => trim($_POST['status'] ?? 'pending'),
        'payment_status' => trim($_POST['payment_status'] ?? 'waiting'),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    $customerId = (int) $form['customer_id'];
    $productId = (int) $form['product_id'];
    $quantity = max(1, (int) $form['quantity']);
    $form['quantity'] = $quantity;

    if (!in_array($customerId, $customerIds, true)) {
        $errors[] = 'Lütfen müşteri seçin.';
    }

    if (!isset($productMap[$productId])) {
        $errors[] = 'Lütfen ürün seçin.';
    }

    if (!array_key_exists($form['status'], order_status_options())) {
        $errors[] = 'Geçerli bir sipariş durumu seçin.';
    }

    if (!array_key_exists($form['payment_status'], payment_status_options())) {
        $errors[] = 'Geçerli bir ödeme durumu seçin.';
    }

    if (!$errors) {
        $product = $productMap[$productId];
        $unitPrice = (float) $product['price'];
        $total = $unitPrice * $quantity;

        try {
            $pdo->beginTransaction();

            if ($isEdit) {
                $updateOrder = $pdo->prepare(
                    'UPDATE orders
                     SET customer_id = :customer_id,
                         status = :status,
                         payment_status = :payment_status,
                         subtotal = :subtotal,
                         discount_total = 0,
                         tax_total = 0,
                         total_amount = :total_amount,
                         notes = :notes
                     WHERE id = :id'
                );
                $updateOrder->execute([
                    'customer_id' => $customerId,
                    'status' => $form['status'],
                    'payment_status' => $form['payment_status'],
                    'subtotal' => $total,
                    'total_amount' => $total,
                    'notes' => $form['notes'],
                    'id' => $orderId,
                ]);

                $deleteItems = $pdo->prepare('DELETE FROM order_items WHERE order_id = :order_id');
                $deleteItems->execute(['order_id' => $orderId]);
            } else {
                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders
                        (customer_id, order_no, status, payment_status, subtotal, discount_total, tax_total, total_amount, notes)
                     VALUES
                        (:customer_id, :order_no, :status, :payment_status, :subtotal, 0, 0, :total_amount, :notes)'
                );
                $insertOrder->execute([
                    'customer_id' => $customerId,
                    'order_no' => generate_order_no($pdo),
                    'status' => $form['status'],
                    'payment_status' => $form['payment_status'],
                    'subtotal' => $total,
                    'total_amount' => $total,
                    'notes' => $form['notes'],
                ]);

                $orderId = (int) $pdo->lastInsertId();
            }

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items
                    (order_id, product_id, product_name, quantity, unit_price, line_total)
                 VALUES
                    (:order_id, :product_id, :product_name, :quantity, :unit_price, :line_total)'
            );
            $insertItem->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'product_name' => $product['name'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $total,
            ]);

            $pdo->commit();

            redirect('admin/orders.php?saved=1');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = 'Sipariş kaydedilemedi. Lütfen tekrar deneyin.';
        }
    }
}

$page_title = $isEdit ? 'Sipariş Düzenle' : 'Yeni Sipariş';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1><?php echo e($page_title); ?></h1>
            <p>Bir müşteri ve bir ürün seçerek hızlıca sipariş oluşturun.</p>
        </div>
        <a class="btn btn-secondary" href="<?php echo url('admin/orders.php'); ?>">Listeye Dön</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!$customers || !$products): ?>
        <div class="alert alert-error">
            Sipariş eklemek için önce en az bir müşteri ve bir ürün kaydı gerekir.
        </div>
    <?php endif; ?>

    <section class="panel">
        <form class="form-stack order-form" method="post">
            <?php echo csrf_field(); ?>
            <div class="form-grid">
                <label>
                    Müşteri
                    <select name="customer_id" required>
                        <option value="">Müşteri seçin</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo (int) $customer['id']; ?>" <?php echo (int) $form['customer_id'] === (int) $customer['id'] ? 'selected' : ''; ?>>
                                <?php echo e($customer['name']); ?><?php echo $customer['phone'] ? ' - ' . e($customer['phone']) : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Ürün
                    <select name="product_id" required data-product-select>
                        <option value="" data-price="0">Ürün seçin</option>
                        <?php foreach ($products as $product): ?>
                            <option
                                value="<?php echo (int) $product['id']; ?>"
                                data-price="<?php echo e((string) $product['price']); ?>"
                                <?php echo (int) $form['product_id'] === (int) $product['id'] ? 'selected' : ''; ?>
                            >
                                <?php echo e($product['name']); ?> - <?php echo e(format_money((float) $product['price'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Adet
                    <input type="number" name="quantity" min="1" step="1" value="<?php echo (int) $form['quantity']; ?>" required data-quantity-input>
                </label>

                <label>
                    Sipariş Durumu
                    <select name="status" required>
                        <?php foreach (order_status_options() as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $form['status'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Ödeme Durumu
                    <select name="payment_status" required>
                        <?php foreach (payment_status_options() as $value => $label): ?>
                            <option value="<?php echo e($value); ?>" <?php echo $form['payment_status'] === $value ? 'selected' : ''; ?>>
                                <?php echo e($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="total-box">
                    <span>Toplam</span>
                    <strong data-total-preview>0,00 TL</strong>
                </div>
            </div>

            <label>
                Not
                <textarea name="notes" rows="4" placeholder="Sipariş notu"><?php echo e($form['notes']); ?></textarea>
            </label>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit" <?php echo (!$customers || !$products) ? 'disabled' : ''; ?>>
                    Kaydet
                </button>
                <a class="btn btn-secondary" href="<?php echo url('admin/orders.php'); ?>">Vazgeç</a>
            </div>
        </form>
    </section>
</main>

<script>
(function () {
    var productSelect = document.querySelector('[data-product-select]');
    var quantityInput = document.querySelector('[data-quantity-input]');
    var totalPreview = document.querySelector('[data-total-preview]');

    if (!productSelect || !quantityInput || !totalPreview) {
        return;
    }

    function formatMoney(value) {
        return new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(value) + ' TL';
    }

    function updateTotal() {
        var selected = productSelect.options[productSelect.selectedIndex];
        var price = parseFloat(selected ? selected.getAttribute('data-price') : '0') || 0;
        var quantity = parseInt(quantityInput.value, 10) || 1;

        if (quantity < 1) {
            quantity = 1;
            quantityInput.value = 1;
        }

        totalPreview.textContent = formatMoney(price * quantity);
    }

    productSelect.addEventListener('change', updateTotal);
    quantityInput.addEventListener('input', updateTotal);
    updateTotal();
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
