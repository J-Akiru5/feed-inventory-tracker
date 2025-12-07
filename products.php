<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_owner();
require_once __DIR__ . '/config/database.php';

$pdo = get_pdo();
$stmt = $pdo->query("SELECT p.*, GROUP_CONCAT(u.unit_name SEPARATOR ', ') AS units
    FROM products p
    LEFT JOIN product_units u ON u.product_id = p.id
    GROUP BY p.id");
$products = $stmt->fetchAll();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="p-4 flex-fill">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Products</h2>
      <a href="/feed-inventory-tracker/product_add.php" class="btn btn-success">Add New Product</a>
    </div>

    <div class="table-responsive">
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Category</th>
            <th>Base Unit</th>
            <th>Units</th>
            <th>Photo</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="6" class="text-center">No products found.</td></tr>
          <?php else: foreach ($products as $p): ?>
            <tr>
              <td><?php echo htmlspecialchars($p['id']); ?></td>
              <td><?php echo htmlspecialchars($p['name']); ?></td>
              <td><?php echo htmlspecialchars($p['category']); ?></td>
              <td><?php echo htmlspecialchars($p['base_unit']); ?></td>
              <td><?php echo htmlspecialchars($p['units'] ?? ''); ?></td>
              <td>
                <?php if (!empty($p['photo'])): ?>
                  <img src="/feed-inventory-tracker/uploads/<?php echo htmlspecialchars($p['photo']); ?>" alt="" style="height:48px;object-fit:cover;border-radius:4px;">
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
