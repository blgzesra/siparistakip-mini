<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$pdo = db();
$errors = [];
$customerId = (int) ($_GET['id'] ?? 0);
$isEdit = $customerId > 0;

$form = [
    'name' => '',
    'phone' => '',
    'address' => '',
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $deleteId = (int) ($_POST['id'] ?? 0);

    if ($deleteId > 0) {
        $stmt = $pdo->prepare('DELETE FROM customers WHERE id = :id');
        $stmt->execute(['id' => $deleteId]);
    }

    redirect('admin/customers.php?deleted=1');
}

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT id, name, phone, address, notes FROM customers WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        redirect('admin/customers.php');
    }

    $form = [
        'name' => $customer['name'] ?? '',
        'phone' => $customer['phone'] ?? '',
        'address' => $customer['address'] ?? '',
        'notes' => $customer['notes'] ?? '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $form = [
        'name' => trim($_POST['name'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
    ];

    if ($form['name'] === '') {
        $errors[] = 'Müşteri adı zorunludur.';
    }

    if (!$errors) {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE customers
                 SET name = :name,
                     phone = :phone,
                     address = :address,
                     notes = :notes
                 WHERE id = :id'
            );
            $stmt->execute([
                'name' => $form['name'],
                'phone' => $form['phone'] !== '' ? $form['phone'] : null,
                'address' => $form['address'] !== '' ? $form['address'] : null,
                'notes' => $form['notes'] !== '' ? $form['notes'] : null,
                'id' => $customerId,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO customers (name, phone, address, notes)
                 VALUES (:name, :phone, :address, :notes)'
            );
            $stmt->execute([
                'name' => $form['name'],
                'phone' => $form['phone'] !== '' ? $form['phone'] : null,
                'address' => $form['address'] !== '' ? $form['address'] : null,
                'notes' => $form['notes'] !== '' ? $form['notes'] : null,
            ]);
        }

        redirect('admin/customers.php?saved=1');
    }
}

$customers = $pdo->query(
    'SELECT
        c.id,
        c.name,
        c.phone,
        c.address,
        c.notes,
        c.created_at
     FROM customers c
     ORDER BY c.name ASC'
)->fetchAll();

$page_title = 'Müşteriler';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="content">
    <div class="page-head">
        <div>
            <h1>Müşteriler</h1>
            <p>Müşteri kayıtlarını ekleyin, düzenleyin ve sipariş geçmişine ulaşın.</p>
        </div>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Müşteri kaydedildi.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Müşteri silindi.</div>
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
            <h2><?php echo $isEdit ? 'Müşteri Düzenle' : 'Yeni Müşteri'; ?></h2>
        </div>

        <form class="form-stack" method="post">
            <input type="hidden" name="action" value="save">

            <div class="form-grid">
                <label>
                    Ad Soyad
                    <input type="text" name="name" value="<?php echo e($form['name']); ?>" required>
                </label>

                <label>
                    Telefon
                    <input type="text" name="phone" value="<?php echo e($form['phone']); ?>">
                </label>
            </div>

            <label>
                Adres
                <textarea name="address" rows="3"><?php echo e($form['address']); ?></textarea>
            </label>

            <label>
                Not
                <textarea name="notes" rows="3"><?php echo e($form['notes']); ?></textarea>
            </label>

            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Kaydet</button>
                <?php if ($isEdit): ?>
                    <a class="btn btn-secondary" href="<?php echo url('admin/customers.php'); ?>">Vazgeç</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="panel-head">
            <h2>Müşteri Listesi</h2>
        </div>

        <?php if ($customers): ?>
            <div class="table-wrap">
                <table class="data-table customers-table">
                    <thead>
                        <tr>
                            <th>Müşteri</th>
                            <th>Telefon</th>
                            <th>Adres</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($customer['name']); ?></strong>
                                    <?php if (!empty($customer['notes'])): ?>
                                        <span class="table-subtext"><?php echo e($customer['notes']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($customer['phone'] ?: '-'); ?></td>
                                <td><?php echo e($customer['address'] ?: '-'); ?></td>
                                <td>
                                    <div class="action-row">
                                        <a class="btn btn-secondary btn-small" href="<?php echo url('admin/customer_view.php?id=' . (int) $customer['id']); ?>">Detay</a>
                                        <a class="btn btn-secondary btn-small" href="<?php echo url('admin/customers.php?id=' . (int) $customer['id']); ?>">Düzenle</a>
                                        <form method="post" onsubmit="return confirm('Bu müşteriyi silmek istediğinize emin misiniz?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $customer['id']; ?>">
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
                <h2>Henüz müşteri yok</h2>
                <p>İlk müşteriyi eklemek için formu doldurun.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
