<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'storekeeper';
    $username = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    } else {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('SELECT user_id AS id, password_hash AS password, role, full_name FROM users WHERE username = :username AND role = :role LIMIT 1');
        $stmt->execute(['username' => $username, 'role' => $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
          $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: ../index.php');
            exit;
        } else {
            $errors[] = 'Invalid credentials for selected role.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login - Dingle Poultry System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/feed-inventory-tracker/assets/css/auth.css" rel="stylesheet">
  <link href="/feed-inventory-tracker/assets/css/styles.css" rel="stylesheet">
</head>
<body class="auth-bg">
  <div class="auth-card">
    <a href="/feed-inventory-tracker/public/login.php" class="back-link">&larr; Back to login</a>
    <div class="auth-hero"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2v6" stroke="#fff"></path><path d="M6 12h12" stroke="#fff"></path></svg></div>
    <h3 class="text-center">Dingle Poultry Supply</h3>
    <p class="text-center text-muted">Inventory Management System</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="role-toggle d-flex gap-3 mb-3">
        <input type="hidden" name="role" id="role" value="storekeeper">
        <button type="button" class="btn btn-outline-secondary flex-fill" data-role="owner" onclick="selectRole(this)">
          <div><strong>Owner</strong><div class="link-muted">Full Access</div></div>
        </button>
        <button type="button" class="btn btn-outline-primary active flex-fill" data-role="storekeeper" onclick="selectRole(this)">
          <div><strong>Storekeeper</strong><div class="link-muted">Staff Access</div></div>
        </button>
      </div>

      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input name="email" type="email" class="form-control" placeholder="your@email.com" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
      </div>

      <div class="mb-3">
        <a href="#" class="link-muted">Forgot password?</a>
      </div>

      <div class="d-grid mb-2">
        <button class="gradient-btn">Sign In</button>
      </div>

      <div class="text-center mb-3">
        <a href="/feed-inventory-tracker/public/register.php">Don't have an account? Register here</a>
      </div>

      <div class="text-center">
        <div>Business Owner?</div>
        <a href="/feed-inventory-tracker/public/register_owner.php" class="btn" style="background:linear-gradient(90deg,#6f42c1,#8870ff);color:#fff;border-radius:18px;padding:10px 18px;margin-top:8px;">Register as Store Owner</a>
      </div>
    </form>

  </div>

  <script>
    function selectRole(btn) {
      document.querySelectorAll('.role-toggle .btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('role').value = btn.getAttribute('data-role');
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
