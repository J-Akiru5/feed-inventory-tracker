<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$registered = false;
$created_user = null;
$step = $_GET['step'] ?? 'intro';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($step === 'form')) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $business_name = sanitize($_POST['business_name'] ?? '');
    $business_address = sanitize($_POST['business_address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '' || $business_name === '') {
        $errors[] = 'Please fill required fields.';
    }
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
      $pdo = get_pdo();
      $s = $pdo->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');
      $s->execute([':u' => $email]);
        if ($s->fetch()) {
            $errors[] = 'Email already registered.';
        } else {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $ins = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (:u,:p,:fn,:r,NOW())');
          $ins->execute([
            ':u' => $email,
            ':p' => $hash,
            ':fn' => $full_name,
            ':r' => 'owner'
          ]);
          $lastId = $pdo->lastInsertId();
          // Do not auto-login owner; show confirmation page instead
          $registered = true;
          $created_user = [
            'user_id' => $lastId,
            'full_name' => $full_name,
            'email' => $email,
            'business_name' => $business_name,
            'role' => 'Owner'
          ];
          // clear POST to avoid re-submission when showing the success view
          $_POST = [];
        }
    }
}

?>
<?php
function render_owner_success(array $created_user) {
    ?>
    <div class="text-center">
        <div style="width:84px;height:84px;margin:0 auto;border-radius:50%;background:#e9f9ee;display:flex;align-items:center;justify-content:center;">
          <svg width="40" height="40" viewBox="0 0 24 24" fill="none"><path d="M20 6L9 17l-5-5" stroke="#13a44a" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>
        <h2 class="mt-3">Registration Successful!</h2>
        <p class="text-muted">Your owner account has been created successfully.</p>

        <div class="card mt-3 mb-3" style="border-radius:12px;border:2px solid rgba(123,66,255,0.12);background:linear-gradient(180deg,rgba(123,66,255,0.02), rgba(200,170,255,0.01));">
          <div class="card-body">
            <h5>Account Details:</h5>
            <div class="row">
              <div class="col-4 text-muted">Name:</div>
              <div class="col-8"><strong><?php echo htmlspecialchars($created_user['full_name']); ?></strong></div>
              <div class="col-4 text-muted">Email:</div>
              <div class="col-8"><strong><?php echo htmlspecialchars($created_user['email']); ?></strong></div>
              <div class="col-4 text-muted">Business:</div>
              <div class="col-8"><strong><?php echo htmlspecialchars($created_user['business_name']); ?></strong></div>
              <div class="col-4 text-muted">Role:</div>
              <div class="col-8"><strong>👑 Owner</strong></div>
            </div>
          </div>
        </div>

        <div class="alert alert-success" role="alert">
          <div>You can now login with your Owner account using the credentials you just created.</div>
        </div>

        <div class="d-grid mt-3">
          <a href="/feed-inventory-tracker/public/login.php" class="gradient-btn">Go to Login</a>
        </div>
      </div>
    <?php
}

function render_owner_intro() {
    ?>
    <a href="/feed-inventory-tracker/public/login.php" class="back-link">&larr; Back to Login</a>
      <div class="auth-hero"> <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" fill="#7b42ff"/></svg></div>
      <h1 class="text-center">Store Owner Registration</h1>
      <p class="text-center text-muted">Administrator Account Setup</p>

      <div class="card mt-4 mb-3" style="border-radius:12px;border:2px solid rgba(123,66,255,0.15);background:linear-gradient(180deg,rgba(123,66,255,0.03), rgba(200,170,255,0.01));">
        <div class="card-body">
          <h5><svg width="18" height="18" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:8px"><path d="M12 2L20 6v8l-8 4-8-4V6l8-4z" fill="#7b42ff"/></svg>Why Register as Owner?</h5>
          <ul class="mt-3" style="line-height:1.8;">
            <li><strong>Full System Access:</strong> Complete control over all modules and features</li>
            <li><strong>User Management:</strong> Create and manage storekeeper accounts</li>
            <li><strong>Financial Reports:</strong> Access all sales, inventory, and credit reports</li>
            <li><strong>Database Management:</strong> Seed sample data and manage system settings</li>
            <li><strong>Business Analytics:</strong> View comprehensive dashboard insights</li>
          </ul>
        </div>
      </div>

      <div class="alert alert-warning" role="alert">
        <h6>Important Notes:</h6>
        <ul class="mb-0">
          <li>Only register as Owner if you are the business owner/administrator</li>
          <li>You will need a security code to complete registration</li>
          <li>Owner accounts have full access to all system data and settings</li>
          <li>Keep your credentials secure and do not share them</li>
        </ul>
      </div>

      <div class="d-flex gap-3">
        <a href="/feed-inventory-tracker/public/login.php" class="btn btn-outline-secondary flex-grow-1">Cancel</a>
        <a href="/feed-inventory-tracker/public/register_owner.php?step=form" class="gradient-btn px-4">Continue to Registration</a>
      </div>
    <?php
}

function render_owner_form(array $errors = []) {
    ?>
    <a href="/feed-inventory-tracker/public/register_owner.php" class="back-link">&larr; Back</a>
      <h3 class="text-center">Create Owner Account</h3>
      <p class="text-center text-muted">Fill in your business details</p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
      <?php endif; ?>

      <form method="post">
        <h6 class="mt-2">1 Personal Information</h6>
        <div class="mb-3">
          <label class="form-label">Full Name *</label>
          <input name="full_name" class="form-control" required>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Email Address *</label>
            <input name="email" type="email" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone Number *</label>
            <input name="phone" class="form-control" required>
          </div>
        </div>

        <h6 class="mt-2">2 Business Information</h6>
        <div class="mb-3">
          <label class="form-label">Business Name *</label>
          <input name="business_name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Business Address *</label>
          <textarea name="business_address" class="form-control" rows="3"></textarea>
        </div>

        <h6 class="mt-2">3 Security</h6>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Password *</label>
            <input name="password" type="password" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Confirm Password *</label>
            <input name="confirm_password" type="password" class="form-control" required>
          </div>
        </div>

        <div class="d-grid mt-3">
          <button class="gradient-btn">Create Owner Account</button>
        </div>
      </form>
    <?php
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Owner Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/feed-inventory-tracker/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-bg">
  <div class="auth-card">
    <?php
    if ($registered) {
        render_owner_success($created_user);
    } elseif ($step !== 'form') {
        render_owner_intro();
    } else {
        render_owner_form($errors);
    }
    ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
