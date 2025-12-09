<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_login();
require_once __DIR__ . '/config/database.php';

$pdo = get_pdo();

// Summary figures
$stmt = $pdo->prepare("SELECT COUNT(*) FROM customers");
$stmt->execute();
$totalCustomers = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM sales WHERE payment_method = 'Credit'");
$stmt->execute();
$totalCreditSales = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM credit_payments");
$stmt->execute();
$totalPayments = $stmt->fetchColumn();

$totalOutstanding = max(0, $totalCreditSales - $totalPayments);

// Customers with outstanding balances (total credit for customer - payments)
$sql = "SELECT c.customer_id, c.full_name,
  COALESCE(SUM(CASE WHEN s.payment_method = 'Credit' THEN s.total_amount ELSE 0 END),0) AS total_credit,
  COALESCE(SUM(cp.amount_paid),0) AS total_paid,
  (COALESCE(SUM(CASE WHEN s.payment_method = 'Credit' THEN s.total_amount ELSE 0 END),0) - COALESCE(SUM(cp.amount_paid),0)) AS outstanding
  FROM customers c
  LEFT JOIN sales s ON s.customer_id = c.customer_id
  LEFT JOIN credit_payments cp ON cp.customer_id = c.customer_id
  GROUP BY c.customer_id
  HAVING outstanding > 0
  ORDER BY outstanding DESC
  LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="p-4 flex-fill">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h3>Credit Management (Utang)</h3>
        <div class="text-muted">Manage customer credit accounts and payments</div>
      </div>
      <div>
        <a href="customers.php?action=add" class="btn btn-primary rounded-pill px-4">+ Add Customer</a>
      </div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card p-3">
          <div class="small text-muted">Total Outstanding</div>
          <div class="h4">₱<?php echo number_format($totalOutstanding,2); ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <div class="small text-muted">Total Customers</div>
          <div class="h4"><?php echo $totalCustomers; ?></div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
          <div class="small text-muted">With Outstanding Credit</div>
          <div class="h4"><?php echo count($customers); ?></div>
        </div>
      </div>
    </div>

    <div class="card mb-3 p-3">
      <div class="d-flex justify-content-end align-items-center gap-2">
        <div class="btn-group btn-group-sm" role="group" aria-label="view toggle">
          <button class="btn btn-outline-secondary active">Cards</button>
          <button class="btn btn-outline-primary">Table</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Customers with Outstanding Balances</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Contact</th>
                <th class="text-end">Total Credit</th>
                <th class="text-end">Paid</th>
                <th class="text-end">Outstanding</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($customers)): ?>
                <tr><td colspan="6" class="text-center">No customers with outstanding credit</td></tr>
              <?php else: ?>
                <?php foreach ($customers as $c): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($c['full_name']); ?></td>
                    <td><?php echo ''; // contact not present in this table display ?></td>
                    <td class="text-end">₱<?php echo number_format($c['total_credit'],2); ?></td>
                    <td class="text-end">₱<?php echo number_format($c['total_paid'],2); ?></td>
                    <td class="text-end">₱<?php echo number_format($c['outstanding'],2); ?></td>
                    <td>
                      <a href="customers.php?id=<?php echo (int)$c['customer_id']; ?>&action=view" class="btn btn-sm btn-outline-primary">View</a>
                      <a href="customers.php?id=<?php echo (int)$c['customer_id']; ?>&action=pay" class="btn btn-sm btn-success">Add Payment</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
