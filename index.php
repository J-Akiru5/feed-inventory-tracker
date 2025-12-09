<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_login();
require_once __DIR__ . '/config/database.php';

$pdo = get_pdo();

// Today's sales
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) AS today_sales FROM sales WHERE DATE(sale_date) = CURDATE()");
$stmt->execute();
$todaySales = $stmt->fetchColumn();

// Total products
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
$stmt->execute();
$totalProducts = $stmt->fetchColumn();

// Low stock items (quantity_on_hand <= 5)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE quantity_on_hand <= 5");
$stmt->execute();
$lowStock = $stmt->fetchColumn();

// Credit outstanding (sum of unpaid or partial sales)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE payment_status IN ('Unpaid','Partial')");
$stmt->execute();
$creditOutstanding = $stmt->fetchColumn();

// Items expiring soon (next 30 days)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$stmt->execute();
$expiringSoon = $stmt->fetchColumn();

// Recent sales (last 5)
$stmt = $pdo->prepare("SELECT s.sale_date, COALESCE(c.full_name, 'Walk-in') AS customer, s.payment_method, s.total_amount FROM sales s LEFT JOIN customers c ON s.customer_id = c.customer_id ORDER BY s.sale_date DESC LIMIT 5");
$stmt->execute();
$recentSales = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="p-4 flex-fill">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>Dashboard</h3>
      <div class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-md-4 col-lg-2">
        <div class="card p-3">
          <div class="small text-muted">Today's Sales</div>
          <div class="h5">₱<?php echo number_format($todaySales,2); ?></div>
        </div>
      </div>
      <div class="col-sm-6 col-md-4 col-lg-2">
        <div class="card p-3">
          <div class="small text-muted">Total Products</div>
          <div class="h5"><?php echo (int)$totalProducts; ?></div>
        </div>
      </div>
      <div class="col-sm-6 col-md-4 col-lg-2">
        <div class="card p-3">
          <div class="small text-muted">Low Stock Items</div>
          <div class="h5"><?php echo (int)$lowStock; ?></div>
        </div>
      </div>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card p-3">
          <div class="small text-muted">Credit Outstanding</div>
          <div class="h5">₱<?php echo number_format($creditOutstanding,2); ?></div>
        </div>
      </div>
      <div class="col-sm-6 col-md-4 col-lg-3">
        <div class="card p-3">
          <div class="small text-muted">Items Expiring Soon</div>
          <div class="h5"><?php echo (int)$expiringSoon; ?></div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Recent Sales</h5>
            <?php if (empty($recentSales)): ?>
              <div class="p-5 text-center text-muted">No sales yet</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Date</th>
                      <th>Customer</th>
                      <th>Payment Method</th>
                      <th class="text-end">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($recentSales as $r): ?>
                      <tr>
                        <td><?php echo date('M j, Y, g:i A', strtotime($r['sale_date'])); ?></td>
                        <td><?php echo htmlspecialchars($r['customer']); ?></td>
                        <td><span class="badge bg-light text-muted text-capitalize"><?php echo htmlspecialchars($r['payment_method']); ?></span></td>
                        <td class="text-end">₱<?php echo number_format($r['total_amount'],2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card mb-4">
          <div class="card-body">
            <h5 class="card-title">Recent Audit Logs</h5>
            <div class="p-5 text-center text-muted">No audit logs yet</div>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="row g-3">
          <div class="col-md-4">
            <a href="/feed-inventory-tracker/pos.php" class="text-decoration-none">
              <div class="card text-white" style="background:linear-gradient(90deg,#05a66b,#09c37a);border:none;">
                <div class="card-body py-4">
                  <h4 class="fw-bold">Process Sale</h4>
                  <p class="mb-0">Start a new transaction at the POS</p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="/feed-inventory-tracker/inventory.php" class="text-decoration-none">
              <div class="card text-white" style="background:linear-gradient(90deg,#0b74ff,#2fa8ff);border:none;">
                <div class="card-body py-4">
                  <h4 class="fw-bold">Receive Stock</h4>
                  <p class="mb-0">Record new inventory deliveries</p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-4">
            <a href="/feed-inventory-tracker/reports.php" class="text-decoration-none">
              <div class="card text-white" style="background:linear-gradient(90deg,#6f42c1,#8870ff);border:none;">
                <div class="card-body py-4">
                  <h4 class="fw-bold">View Reports</h4>
                  <p class="mb-0">Analyze sales and inventory data</p>
                </div>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
