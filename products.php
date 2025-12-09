<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_owner();
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = get_pdo();

// Handle product delete requests (delete units & inventory first, then product)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
  $productId = (int)($_POST['product_id'] ?? 0);
  if ($productId > 0) {
    try {
      $pdo->beginTransaction();
      $delUnits = $pdo->prepare('DELETE FROM product_units WHERE product_id = :id');
      $delUnits->execute([':id' => $productId]);
      $delInv = $pdo->prepare('DELETE FROM inventory WHERE product_id = :id');
      $delInv->execute([':id' => $productId]);
      $delP = $pdo->prepare('DELETE FROM products WHERE product_id = :id');
      $delP->execute([':id' => $productId]);
      $pdo->commit();
      $_SESSION['flash'] = 'Product deleted successfully.';
    } catch (Exception $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      $_SESSION['flash'] = 'Failed to delete product. It may be referenced by other records.';
    }
  } else {
    $_SESSION['flash'] = 'Invalid product selected.';
  }
  header('Location: /feed-inventory-tracker/products.php');
  exit;
}

// add-category UI/handler removed — categories are managed elsewhere

// Load products with aggregated stock
$stmt = $pdo->prepare("SELECT p.product_id, p.name, p.category, p.base_unit, p.photo_filename, COALESCE(SUM(i.quantity_on_hand),0) AS stock
  FROM products p
  LEFT JOIN inventory i ON i.product_id = p.product_id
  WHERE p.is_active = 1
  GROUP BY p.product_id
  ORDER BY p.name ASC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load product units (variations)
$stmt = $pdo->prepare("SELECT product_id, unit_name, selling_price FROM product_units ORDER BY product_id, unit_name ASC");
$stmt->execute();
$unitsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unitsByProduct = [];
foreach ($unitsRows as $u) {
  $unitsByProduct[$u['product_id']][] = $u;
}

// Load distinct categories
// Ensure a simple `categories` table exists for owner-defined categories
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(191) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$stmt = $pdo->prepare("SELECT name FROM categories UNION SELECT DISTINCT category COLLATE utf8mb4_unicode_ci AS name FROM products WHERE category IS NOT NULL AND category != '' ORDER BY name ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="p-4 flex-fill">
  <div class="container">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-info"><?php echo htmlspecialchars($_SESSION['flash']); ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2>Products &amp; Inventory</h2>
        <div class="text-muted">Manage your product catalog and stock levels</div>
      </div>
      <a href="/feed-inventory-tracker/product_add.php" class="btn btn-dark rounded-pill px-4">+ Add Product</a>
    </div>

    <div class="card mb-4 p-3">
      <div class="row g-3">
        <div class="col-md-6">
          <input id="productSearch" class="form-control" placeholder="Search products..." />
        </div>
        <div class="col-md-3">
          <select id="categoryFilter" class="form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
          </select>
          <!-- Add Category removed -->
          <!-- category pills removed: using select dropdown only -->
        </div>
        <div class="col-md-3 d-flex justify-content-end align-items-center">
          <div class="btn-group btn-group-sm" role="group">
            <button id="viewCards" class="btn btn-primary">Cards</button>
            <button id="viewTable" class="btn btn-outline-secondary">Table</button>
          </div>
        </div>
      </div>
    </div>


    <div id="cardsView" class="row g-3">
      <?php foreach ($products as $p): ?>
        <?php $stock = (float)($p['stock'] ?? 0); $isLow = $stock <= 5; $units = $unitsByProduct[$p['product_id']] ?? []; ?>
        <div class="col-md-4">
          <div class="card p-3 h-100">
            <div class="d-flex gap-3 align-items-start">
              <div style="width:48px;height:48px;border-radius:8px;background:#e9f6ff;display:flex;align-items:center;justify-content:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2L20 6V18L12 22L4 18V6L12 2Z" stroke="#2FA4FF" stroke-width="1.2" stroke-linejoin="round"/></svg>
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="text-muted small"><?php echo htmlspecialchars($p['category']); ?></div>
              </div>
                    <div class="ms-auto">
                      <form method="post" onsubmit="return confirm('Delete this product and all related inventory? This cannot be undone.');" style="display:inline">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?php echo (int)$p['product_id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                    </div>
            </div>

            <div class="mt-3 text-muted small">
              <div>Base Unit: <span class="float-end"><?php echo htmlspecialchars($p['base_unit']); ?></span></div>
              <div>Stock: <span class="float-end <?php echo $isLow ? 'text-danger' : ''; ?>"><?php echo rtrim(rtrim(number_format($stock,2), '0'), '.'); ?> <?php echo htmlspecialchars($p['base_unit']); ?><?php if ($isLow) echo ' (Low Stock)'; ?></span></div>
            </div>

            <hr />

            <div class="small text-muted mb-2">Variations:</div>
            <?php if (empty($units)): ?>
              <div class="text-muted">No variations</div>
            <?php else: ?>
              <?php foreach ($units as $u): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div><?php echo htmlspecialchars($u['unit_name']); ?></div>
                  <div class="fw-bold">₱<?php echo number_format($u['selling_price'],2); ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div id="tableView" style="display:none;" class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Base Unit</th>
                <th>Stock</th>
                <th>Variations</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): $units = $unitsByProduct[$p['product_id']] ?? []; ?>
                <tr>
                  <td><?php echo htmlspecialchars($p['name']); ?></td>
                  <td><?php echo htmlspecialchars($p['category']); ?></td>
                  <td><?php echo htmlspecialchars($p['base_unit']); ?></td>
                  <td><?php echo rtrim(rtrim(number_format((float)$p['stock'],2), '0'), '.'); ?> <?php echo htmlspecialchars($p['base_unit']); ?></td>
                  <td>
                    <?php if (empty($units)): ?>No variations<?php else: ?>
                      <?php foreach ($units as $u): ?>
                        <div><?php echo htmlspecialchars($u['unit_name']); ?> — ₱<?php echo number_format($u['selling_price'],2); ?></div>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(() => {
  const cardsView = document.getElementById('cardsView');
  const tableView = document.getElementById('tableView');
  document.getElementById('viewCards').addEventListener('click', () => { cardsView.style.display='flex'; tableView.style.display='none'; document.getElementById('viewCards').classList.add('btn-primary'); document.getElementById('viewTable').classList.remove('btn-primary'); });
  document.getElementById('viewTable').addEventListener('click', () => { cardsView.style.display='none'; tableView.style.display='block'; document.getElementById('viewTable').classList.add('btn-primary'); document.getElementById('viewCards').classList.remove('btn-primary'); });

  const searchInput = document.getElementById('productSearch');
  const categoryFilter = document.getElementById('categoryFilter');

  function filterCards() {
    const q = searchInput.value.toLowerCase();
    const cat = categoryFilter.value;
    Array.from(cardsView.querySelectorAll('.card')).forEach(card => {
      const name = card.querySelector('.fw-bold')?.textContent.toLowerCase() || '';
      const category = card.querySelector('.text-muted.small')?.textContent || '';
      const matches = (name.includes(q) || category.includes(q)) && (cat === '' || category === cat);
      card.parentElement.style.display = matches ? '' : 'none';
    });
  }

  searchInput.addEventListener('input', filterCards);
  categoryFilter.addEventListener('change', filterCards);
  // Add Category UI removed — no toggle needed
  // category pills removed — no JS needed for pill interactions
  // Initialize pills from select value (useful after reload)
  // category pills removed — no pill initialization required
})();
</script>
