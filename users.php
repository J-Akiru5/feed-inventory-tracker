<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_owner();
require_once __DIR__ . '/config/database.php';

$pdo = get_pdo();
$message = null;

// Handle DB management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'seed_sample') {
        // Insert minimal sample data: customers, products, units, inventory
        $pdo->beginTransaction();
        try {
            $pdo->exec("INSERT INTO customers (full_name, contact_info) VALUES ('Jeff Martinez','09171234567'), ('Hazel Dato-on','09179876543')");
            $pdo->exec("INSERT INTO products (name, category, base_unit) VALUES ('B-MEG Broiler Starter','Feeds','kg'), ('Integra Broiler Grower','Feeds','kg')");
            // product_units assume product_ids are last inserted ids; safer to fetch
            $pstmt = $pdo->prepare("SELECT product_id FROM products ORDER BY product_id DESC LIMIT 2");
            $pstmt->execute();
            $pids = $pstmt->fetchAll(PDO::FETCH_COLUMN);
            if (count($pids) >= 2) {
                $pdo->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, selling_price) VALUES (:pid, :u, 1, :price)")
                    ->execute([':pid'=>$pids[0], ':u'=>'Whole Sack 50kg', ':price'=>2400]);
                $pdo->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, selling_price) VALUES (:pid, :u, 1, :price)")
                    ->execute([':pid'=>$pids[1], ':u'=>'Whole Sack 50kg', ':price'=>2300]);
            }
            // Insert an inventory batch for each product
            $ip = $pdo->prepare("INSERT INTO inventory (product_id, batch_number, supplier, quantity_on_hand, cost_price, expiry_date) VALUES (:pid, :b, :s, :q, :c, DATE_ADD(CURDATE(), INTERVAL 180 DAY))");
            foreach ($pids as $pid) {
                $ip->execute([':pid'=>$pid, ':b'=>'BATCH1', ':s'=>'Local Supplier', ':q'=>100, ':c'=>1500]);
            }
            $pdo->commit();
            $message = 'Sample data seeded successfully.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Seeding failed: ' . $e->getMessage();
        }
    }
    if ($action === 'clear_all') {
        $pdo->beginTransaction();
        try {
            // delete child tables first
            $pdo->exec('DELETE FROM sale_items');
            $pdo->exec('DELETE FROM credit_payments');
            $pdo->exec('DELETE FROM sales');
            $pdo->exec('DELETE FROM inventory');
            $pdo->exec('DELETE FROM product_units');
            $pdo->exec('DELETE FROM products');
            $pdo->exec('DELETE FROM customers');
            $pdo->commit();
            $message = 'All data cleared (products, customers, sales, inventory).';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Clear failed: ' . $e->getMessage();
        }
    }
}

// Counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'owner'");
$stmt->execute();
$ownersCount = $stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'storekeeper'");
$stmt->execute();
$keepersCount = $stmt->fetchColumn();

// Users list
$stmt = $pdo->prepare("SELECT user_id, full_name, username AS email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="p-4 flex-fill">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2>User Management</h2>
        <div class="text-muted">Manage system users and permissions</div>
      </div>
      <a href="/feed-inventory-tracker/product_add.php" class="btn btn-dark rounded-pill px-4">+ Add User</a>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <div class="card p-3">
          <div class="small text-muted">Store Owners</div>
          <div class="h4"><?php echo (int)$ownersCount; ?></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
          <div class="small text-muted">Storekeepers</div>
          <div class="h4"><?php echo (int)$keepersCount; ?></div>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">All Users</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr><td colspan="4" class="text-center">No users found</td></tr>
              <?php else: foreach ($users as $u): ?>
                <tr>
                  <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td><?php echo '<span class="badge bg-'.($u['role']==='owner'?'primary':'info').'">'.htmlspecialchars(ucfirst($u['role'])).'</span>'; ?></td>
                  <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5>Database Management</h5>
        <p class="text-muted">Manage your database with these tools</p>
        <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form method="post" class="d-flex gap-3">
          <input type="hidden" name="action" value="seed_sample" />
          <button class="btn btn-success">🌱 Seed Sample Data</button>
        </form>
        <form method="post" class="d-flex gap-3 mt-3">
          <input type="hidden" name="action" value="clear_all" />
          <button class="btn btn-danger" onclick="return confirm('Clear all data (products, customers, sales, inventory)? This cannot be undone.')">🗑️ Clear All Data</button>
        </form>
        <div class="mt-3 alert alert-secondary">Seed Sample Data: Populates the database with realistic sample products, inventory batches, customers, and sales records for testing.<br/>Clear All Data: Removes all products, customers, sales, and inventory data. Use with caution!</div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
