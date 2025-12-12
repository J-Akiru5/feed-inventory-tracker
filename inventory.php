<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

$require_once_line = "require_once __DIR__ . '/functions/permissions.php';";
require_login();

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions/permissions.php';

$pdo = get_pdo();
$user_role = get_user_role();
$errors = [];

 // Fetch products for select (use product_id and base_unit)
 $pstmt = $pdo->query('SELECT product_id, name, base_unit FROM products ORDER BY name ASC');
 $products = $pstmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Only owners may perform receive/modify actions. Storekeepers are view-only.
  if (!is_owner()) {
    $errors[] = 'You do not have permission to modify inventory.';
  } else {
    $product_id = (int) ($_POST['product_id'] ?? 0);
    $batch_no = sanitize($_POST['batch_no'] ?? '');
    $supplier = sanitize($_POST['supplier'] ?? '');
    $expiry_date = sanitize($_POST['expiry_date'] ?? '');
    $quantity = (float) ($_POST['quantity'] ?? 0);
    $cost_price = (float) ($_POST['cost_price'] ?? 0);

    if ($product_id <= 0 || $quantity <= 0) {
      $errors[] = 'Product and Quantity are required.';
    }

    if (empty($errors)) {
      // Insert using schema: inventory (product_id, batch_number, supplier, quantity_on_hand, cost_price, expiry_date)
      $ist = $pdo->prepare("INSERT INTO inventory (product_id, batch_number, supplier, quantity_on_hand, cost_price, expiry_date)
        VALUES (:pid, :batch, :supplier, :qty, :cost, :expiry)");
      $ist->execute([
        ':pid' => $product_id,
        ':batch' => $batch_no ?: null,
        ':supplier' => $supplier ?: null,
        ':qty' => $quantity,
        ':cost' => $cost_price,
        ':expiry' => $expiry_date ?: null,
      ]);
      header('Location: /feed-inventory-tracker/inventory.php');
      exit;
    }
    }
}

// Fetch inventory batches ordered by expiry date ASC
// Fetch inventory batches ordered by expiry date (use stock_id and quantity_on_hand)
$invStmt = $pdo->query("SELECT i.stock_id, i.batch_number, i.expiry_date, i.quantity_on_hand, i.cost_price, i.received_date, p.name AS product_name, p.base_unit
  FROM inventory i
  JOIN products p ON p.product_id = i.product_id
  ORDER BY CASE WHEN i.expiry_date IS NULL THEN '9999-12-31' ELSE i.expiry_date END ASC");
$batches = $invStmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="p-4 flex-fill">
  <div class="container">
    <h2>Receive Stock</h2>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <?php if (is_owner()): ?>
      <form method="post">
        <div class="card p-3 glass-panel mb-3">
          <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Product</label>
          <select name="product_id" class="form-select" <?php echo (is_owner() ? 'required' : 'disabled'); ?>>
            <option value="">-- Select product --</option>
            <?php foreach ($products as $p): ?>
              <option value="<?php echo (int)$p['product_id']; ?>"><?php echo htmlspecialchars($p['name'] . ' (' . $p['base_unit'] . ')'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Batch No</label>
          <input name="batch_no" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Expiry Date</label>
          <input type="date" name="expiry_date" class="form-control">
        </div>
        <div class="col-md-1">
          <label class="form-label">Qty</label>
          <input name="quantity" type="number" step="0.01" class="form-control" required>
        </div>
            <div class="col-md-2">
              <label class="form-label">Cost Price</label>
              <input name="cost_price" type="number" step="0.01" class="form-control">
            </div>
          </div>
          <?php if ($user_role === 'owner'): ?>
            <div class="mt-3 d-grid">
              <button class="btn btn-primary">Receive</button>
            </div>
          <?php endif; ?>
        </div>
      </form>
    <?php else: ?>
      <div class="alert alert-secondary">View-only: You can browse inventory, but only the owner can receive stock.</div>
    <?php endif; ?>

    <hr>
    <h3>Current Inventory Batches</h3>
    <div class="table-responsive card p-3 glass-panel">
      <table class="table table-sm table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Product</th>
            <th>Batch No</th>
            <th>Expiry</th>
            <th>Quantity</th>
            <th>Cost</th>
            <th>Added At</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($batches)): ?>
            <tr><td colspan="7" class="text-center">No inventory batches.</td></tr>
          <?php else: foreach ($batches as $i): ?>
            <tr>
              <td><?php echo htmlspecialchars($i['stock_id']); ?></td>
              <td><?php echo htmlspecialchars($i['product_name']); ?></td>
              <td><?php echo htmlspecialchars($i['batch_number']); ?></td>
              <td><?php echo htmlspecialchars($i['expiry_date']); ?></td>
              <td><?php echo htmlspecialchars($i['quantity_on_hand'] . ' ' . $i['base_unit']); ?></td>
              <td><?php echo htmlspecialchars($i['cost_price']); ?></td>
              <td><?php echo htmlspecialchars($i['received_date']); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
