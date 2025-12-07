<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_login();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="p-4 flex-fill">
  <div class="container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h1>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>