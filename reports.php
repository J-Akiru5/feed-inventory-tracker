<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_login();
require_once __DIR__ . '/config/database.php';

$pdo = get_pdo();

// Determine active tab and date period
$tab = $_GET['tab'] ?? 'sales';
$period = $_GET['period'] ?? 'today';

switch ($period) {
    case 'week':
        $dateWhere = "YEARWEEK(sale_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $dateWhere = "MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())";
        break;
    case 'today':
    default:
        $dateWhere = "DATE(sale_date) = CURDATE()";
        $period = 'today';
        break;
}

if ($tab === 'sales') {
    // Sales totals
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE {$dateWhere}");
    $stmt->execute();
    $totalSales = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE payment_method = 'Cash' AND {$dateWhere}");
    $stmt->execute();
    $cashSales = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE payment_method = 'Online' AND {$dateWhere}");
    $stmt->execute();
    $onlineSales = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE payment_method = 'Credit' AND {$dateWhere}");
    $stmt->execute();
    $creditSales = $stmt->fetchColumn();

    // Sales transactions
    $sql = "SELECT s.sale_id, s.sale_date, COALESCE(c.full_name,'Walk-in') AS customer, s.payment_method, s.total_amount, (
        SELECT COUNT(*) FROM sale_items si WHERE si.sale_id = s.sale_id
    ) AS items_count
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.customer_id
    WHERE {$dateWhere}
    ORDER BY s.sale_date DESC
    LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} elseif ($tab === 'inventory') {
    // Inventory aggregates
    $lowThreshold = 5;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity_on_hand * cost_price),0) FROM inventory");
    $stmt->execute();
    $totalInventoryValue = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    $totalProducts = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM (
        SELECT p.product_id, COALESCE(SUM(i.quantity_on_hand),0) AS total_qty
        FROM products p
        LEFT JOIN inventory i ON p.product_id = i.product_id
        GROUP BY p.product_id
    ) t WHERE t.total_qty <= :th");
    $stmt->execute([':th' => $lowThreshold]);
    $lowStockProducts = $stmt->fetchColumn();

    $sql = "SELECT p.product_id, p.name, p.category, p.base_unit, COALESCE(SUM(i.quantity_on_hand),0) AS stock, COALESCE(SUM(i.quantity_on_hand * i.cost_price),0) AS value
      FROM products p
      LEFT JOIN inventory i ON p.product_id = i.product_id
      GROUP BY p.product_id
      ORDER BY p.name ASC
      LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $stockRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} else {
    // other tabs: placeholders for now
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="p-4 flex-fill">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Reports &amp; Analytics</h3>
      <div class="text-muted">View business insights and data</div>
    </div>

    <div class="card mb-4 p-3">
      <div class="d-flex gap-3">
        <a class="btn <?php echo $tab === 'sales' ? 'btn-success' : 'btn-light'; ?>" href="?tab=sales">Sales Report</a>
        <a class="btn <?php echo $tab === 'inventory' ? 'btn-success' : 'btn-light'; ?>" href="?tab=inventory">Inventory Report</a>
        <a class="btn <?php echo $tab === 'expiry' ? 'btn-success' : 'btn-light'; ?>" href="?tab=expiry">Expiry Report</a>
        <a class="btn <?php echo $tab === 'ar' ? 'btn-success' : 'btn-light'; ?>" href="?tab=ar">Accounts Receivable</a>
      </div>
    </div>

    <?php if ($tab === 'sales'): ?>

    <div class="card mb-4 p-3">
      <div class="row align-items-center">
        <div class="col-md-3">
          <select class="form-select" onchange="location = this.value">
            <option value="?tab=sales&period=today" <?php echo $period === 'today' ? 'selected' : ''; ?>>Today</option>
            <option value="?tab=sales&period=week" <?php echo $period === 'week' ? 'selected' : ''; ?>>This Week</option>
            <option value="?tab=sales&period=month" <?php echo $period === 'month' ? 'selected' : ''; ?>>This Month</option>
          </select>
        </div>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card p-3">
          <div class="small text-muted">Total Sales</div>
          <div class="h4">₱<?php echo number_format($totalSales,2); ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3">
          <div class="small text-muted">Cash Sales</div>
          <div class="h4 text-success">₱<?php echo number_format($cashSales,2); ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3">
          <div class="small text-muted">Online Sales</div>
          <div class="h4 text-primary">₱<?php echo number_format($onlineSales,2); ?></div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card p-3">
          <div class="small text-muted">Credit Sales</div>
          <div class="h4 text-warning">₱<?php echo number_format($creditSales,2); ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Sales Transactions</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Payment Method</th>
                <th>Items</th>
                <th class="text-end">Total</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($sales)): ?>
                <tr><td colspan="5" class="text-center">No sales in this period</td></tr>
              <?php else: ?>
                <?php foreach ($sales as $s): ?>
                  <tr>
                    <td><?php echo date('M j, Y, g:i A', strtotime($s['sale_date'])); ?></td>
                    <td><?php echo htmlspecialchars($s['customer']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($s['payment_method'])); ?></td>
                    <td><?php echo (int)$s['items_count']; ?></td>
                    <td class="text-end">₱<?php echo number_format($s['total_amount'],2); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php elseif ($tab === 'inventory'): ?>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card p-3">
          <div class="small text-muted">Total Inventory Value</div>
          <div class="h4">₱<?php echo number_format($totalInventoryValue,2); ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <div class="small text-muted">Total Products</div>
          <div class="h4"><?php echo (int)$totalProducts; ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <div class="small text-muted">Low Stock Items</div>
          <div class="h4 text-warning"><?php echo (int)$lowStockProducts; ?></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Current Stock Levels</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Stock</th>
                <th>Value</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($stockRows)): ?>
                <tr><td colspan="5" class="text-center">No products found</td></tr>
              <?php else: ?>
                <?php foreach ($stockRows as $r): ?>
                  <?php $status = ((float)$r['stock'] <= 5) ? 'Low Stock' : 'In Stock'; ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                    <td><?php echo htmlspecialchars($r['category']); ?></td>
                    <td><?php echo (is_numeric($r['stock']) ? rtrim(rtrim(number_format($r['stock'],2), '0'), '.') : $r['stock']) . ' ' . htmlspecialchars($r['base_unit'] ?? ''); ?></td>
                    <td>₱<?php echo number_format($r['value'],2); ?></td>
                    <td><span class="badge <?php echo $status === 'In Stock' ? 'bg-success' : 'bg-warning text-dark'; ?>"><?php echo $status; ?></span></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <?php else: ?>

    <div class="card p-4 text-center text-muted">Report tab not implemented yet.</div>

    <?php endif; ?>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
