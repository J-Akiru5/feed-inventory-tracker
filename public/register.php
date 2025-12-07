<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $errors[] = 'All fields are required.';
    }
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
      $pdo = get_pdo();
      // check existing
      $s = $pdo->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');
      $s->execute([':u' => $email]);
      if ($s->fetch()) {
        $errors[] = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (:u,:p,:fn,:r,NOW())');
<<<<<<< HEAD
            $ins->execute([':u' => $email, ':p' => $hash, ':r' => 'storekeeper', ':fn' => $full_name]);
=======
            $ins->execute([':u'=>$email,':p'=>$hash,':fn'=>$full_name,':r'=>'storekeeper']);
>>>>>>> 39573ea8fb54714e868dcde24c5a7a69c3f80dcc
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = 'storekeeper';
            $_SESSION['full_name'] = $full_name;
            header('Location: ../index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Account - Storekeeper</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/feed-inventory-tracker/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-bg">
  <div class="auth-card">
    <a href="/feed-inventory-tracker/public/login.php" class="back-link">&larr; Back to login</a>
    <div class="auth-hero"> <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="none"/></svg></div>
    <h3 class="text-center">Dingle Poultry Supply</h3>
    <p class="text-center text-muted">Inventory Management System</p>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input name="full_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Email Address</label>
        <input name="email" type="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required>
        <div class="form-text">Minimum 6 characters</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm Password</label>
        <input name="confirm_password" type="password" class="form-control" required>
      </div>
      <div class="d-grid mt-3">
        <button class="gradient-btn">Create Account</button>
      </div>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
