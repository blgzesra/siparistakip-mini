<?php
$nav_items = [
    ['files' => ['index.php'], 'label' => 'Panel', 'href' => 'admin/index.php', 'icon' => 'P'],
    ['files' => ['orders.php', 'order_form.php'], 'label' => 'Siparişler', 'href' => 'admin/orders.php', 'icon' => 'S'],
    ['files' => ['customers.php', 'customer_view.php'], 'label' => 'Müşteriler', 'href' => 'admin/customers.php', 'icon' => 'M'],
    ['files' => ['products.php'], 'label' => 'Ürünler', 'href' => 'admin/products.php', 'icon' => 'U'],
    ['files' => ['settings.php'], 'label' => 'Ayarlar', 'href' => 'admin/settings.php', 'icon' => 'A'],
];
$current_file = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
        <aside class="sidebar" data-sidebar>
            <a class="brand" href="<?php echo url('admin/index.php'); ?>">
                <span class="brand-mark">ST</span>
                <span class="brand-text">
                    <span class="brand-name"><?php echo e(APP_NAME); ?></span>
                    <span class="brand-subtitle">Mini satış paneli</span>
                </span>
            </a>
            <div class="nav-section">Yönetim</div>
            <nav class="nav">
                <?php foreach ($nav_items as $item): ?>
                    <?php $isActive = in_array($current_file, $item['files'], true); ?>
                    <a class="<?php echo $isActive ? 'is-active' : ''; ?>" href="<?php echo url($item['href']); ?>" <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                        <span class="nav-icon" aria-hidden="true"><?php echo e($item['icon']); ?></span>
                        <span><?php echo e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
