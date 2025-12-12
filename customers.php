<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_login();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/session.php';

$pdo = get_pdo();
$flash = null;

// Add customer handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact_info'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        set_flash('Customer name is required.');
        header('Location: /feed-inventory-tracker/customers.php?action=add');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO customers (full_name, contact_info, address) VALUES (:n, :c, :a)');
    try {
        $stmt->execute([':n'=>$name, ':c'=>$contact, ':a'=>$address]);
        set_flash('Customer added successfully.');
        header('Location: /feed-inventory-tracker/customers.php');
        exit;
    } catch (Exception $e) {
        set_flash('Failed to add customer: ' . $e->getMessage());
        header('Location: /feed-inventory-tracker/customers.php?action=add');
        exit;
    }
}

$action = $_GET['action'] ?? '';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="p-4 flex-fill">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Customers</h3>
      <a href="/feed-inventory-tracker/customers.php?action=add" class="btn btn-primary">+ Add Customer</a>
    </div>

    <?php $flash = get_flash(); if (!empty($flash)): ?>
      <div class="alert alert-info"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
      <div class="card glass-panel p-3" style="max-width:700px;">
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input name="full_name" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">Contact Info</label>
            <input name="contact_info" class="form-control" />
          </div>
          <div class="mb-3">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3"></textarea>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-primary">Save Customer</button>
            <a href="/feed-inventory-tracker/customers.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="card glass-panel p-3">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr><th>Name</th><th>Contact</th><th>Address</th><th>Created</th></tr>
            </thead>
            <tbody>
              <?php
                $stmt = $pdo->query('SELECT customer_id, full_name, contact_info, address, created_at FROM customers ORDER BY created_at DESC');
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($rows)){
                  echo '<tr><td colspan="4" class="text-center">No customers found</td></tr>';
                } else {
                  foreach ($rows as $r) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($r['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($r['contact_info']) . '</td>';
                    echo '<td>' . htmlspecialchars($r['address']) . '</td>';
                    echo '<td>' . htmlspecialchars($r['created_at']) . '</td>';
                    echo '</tr>';
                  }
                }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
