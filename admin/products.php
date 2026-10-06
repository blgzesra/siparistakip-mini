<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();
$errors = [];
$productId = (int) ($_GET['id'] ?? 0);
$isEdit = $productId > 0;

$form = [
    'name' => '',
    'price' => '0.00',
    'stock_quantity' => '0',
    'is_active' => '1',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int) ($_POST['id'] ?? 0);

    if ($deleteId > 0) {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $deleteId]);
    }

    redirect('admin/products.php?deleted=1');
}

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT id, name, price, stock_quantity, is_active FROM products WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();

    if (!$product) {
        redirect('admin/products.php');
    }

    $form = [
        'name' => $product['name'] ?? '',
        'price' => (string) $product['price'],
        'stock_quantity' => (string) $product['stock_quantity'],
        'is_active' => (string) (int) $product['is_active'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $form = [
        'name' => trim($_POST['name'] ?? ''),
        'price' => str_replace(',', '.', trim($_POST['price'] ?? '0')),
        'stock_quantity' => trim($_POST['stock_quantity'] ?? '0'),
        'is_active' => isset($_POST['is_active']) ? '1' : '0',
    ];

    if ($form['name'] === '') {
        $errors[] = 'Ürün adı zorunludur.';
    }

    if (!is_numeric($form['price']) || (float) $form['price'] < 0) {
        $errors[] = 'Geçerli bir fiyat girin.';
    }

    if (!ctype_digit($form['stock_quantity'])) {
        $errors[] = 'Stok sıfır veya pozitif tam sayı olmalıdır.';
    }

    if (!$errors) {
        $price = round((float) $form['price'], 2);
        $stockQuantity = (int) $form['stock_quantity'];
        $isActive = (int) $form['is_active'];

        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE products
                 SET name = :name,
                     price = :price,
                     stock_quantity = :stock_quantity,
                     is_active = :is_active
                 WHERE id = :id'
            );
            $stmt->execute([
                'name' => $form['name'],
                'price' => $price,
                'stock_quantity' => $stockQuantity,
                'is_active' => $isActive,
                'id' => $productId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, price, stock_quantity, is_active)
                 VALUES (:name, :price, :stock_quantity, :is_active)'
            );
            $stmt->execute([
                'name' => $form['name'],
                'price' => $price,
                'stock_quantity' => $stockQuantity,
                'is_active' => $isActive,
            ]);
        }

        redirect('admin/products.php?saved=1');
    }
}

$products = $pdo->query(
    'SELECT
        p.id,
        p.name,
        p.price,
        p.stock_quantity,
        p.is_active,
        p.created_at
     FROM products p
     ORDER BY p.name ASC'
)->fetchAll();

$page_title = 'Ürünler';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1>Ürünler</h1>
            <p>Ürün adı, fiyatı, stok durumu ve aktifliğini yönetin.</p>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Ürün kaydedildi.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Ürün silindi.</div>
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
            <h2><?php echo $isEdit ? 'Ürün Düzenle' : 'Yeni Ürün'; ?></h2>
        </div>

        <form class="form-stack" method="post">
            <input type="hidden" name="action" value="save">

            <div class="form-grid">
                <label>
                    Ürün Adı
                    <input type="text" name="name" value="<?php echo e($form['name']); ?>" required>
                </label>

                <label>
                    Fiyat
                    <input type="number" name="price" min="0" step="0.01" value="<?php echo e($form['price']); ?>" required>
                </label>

                <label>
                    Stok
                    <input type="number" name="stock_quantity" min="0" step="1" value="<?php echo e($form['stock_quantity']); ?>" required>
                </label>

                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?php echo $form['is_active'] === '1' ? 'checked' : ''; ?>>
                    Aktif
                </label>
            </div>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Kaydet</button>
                <?php if ($isEdit): ?>
                    <a class="btn btn-secondary" href="<?php echo url('admin/products.php'); ?>">Vazgeç</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Ürün Listesi</h2>
        </div>

        <?php if ($products): ?>
            <div class="table-wrap">
                <table class="data-table products-table">
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Fiyat</th>
                            <th>Stok</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?php echo e($product['name']); ?></strong></td>
                                <td><?php echo e(format_money((float) $product['price'])); ?></td>
                                <td><?php echo (int) $product['stock_quantity']; ?></td>
                                <td>
                                    <?php if ((int) $product['is_active'] === 1): ?>
                                        <span class="status-badge status-completed">Aktif</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-row">
                                        <a class="btn btn-secondary btn-small" href="<?php echo url('admin/products.php?id=' . (int) $product['id']); ?>">Düzenle</a>
                                        <form method="post" onsubmit="return confirm('Bu ürünü silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
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
                <h2>Henüz ürün yok</h2>
                <p>İlk ürünü eklemek için formu doldurun.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
