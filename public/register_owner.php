<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $s = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
        $s->execute([':u' => $email]);
        if ($s->fetch()) {
            $errors[] = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (username, password, role, full_name, phone, business_name, business_address, created_at) VALUES (:u,:p,:r,:fn,:ph,:bn,:ba,NOW())');
            $ins->execute([
                ':u'=>$email,':p'=>$hash,':r'=>'owner',':fn'=>$full_name,':ph'=>$phone,':bn'=>$business_name,':ba'=>$business_address
            ]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = 'owner';
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
  <title>Create Owner Account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/feed-inventory-tracker/assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-bg">
  <div class="auth-card">
    <a href="/feed-inventory-tracker/public/login.php" class="back-link">&larr; Back</a>
    <div class="auth-hero"> <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="4" fill="none"/></svg></div>
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
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
