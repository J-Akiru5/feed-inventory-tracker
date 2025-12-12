<?php
$current = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/../functions/permissions.php';
// Default links for owner/admin
$links = [
  'index.php' => ['label' => 'Dashboard', 'icon' => 'dashboard'],
  'pos.php' => ['label' => 'Point of Sale', 'icon' => 'pos'],
  'products.php' => ['label' => 'Products & Inventory', 'icon' => 'products'],
  'inventory.php' => ['label' => 'Stock Receiving', 'icon' => 'truck'],
  'credit.php' => ['label' => 'Credit Management', 'icon' => 'users'],
  'reports.php' => ['label' => 'Reports', 'icon' => 'reports'],
  'users.php' => ['label' => 'User Management', 'icon' => 'users'],
];

// If the logged-in user is a storekeeper, limit visible links (view-only)
if (is_storekeeper()) {
  $links = [
    'index.php' => ['label' => 'Dashboard', 'icon' => 'dashboard'],
    'pos.php' => ['label' => 'Point of Sale', 'icon' => 'pos'],
    'products.php' => ['label' => 'Products & Inventory', 'icon' => 'products'],
    'inventory.php' => ['label' => 'Stock Receiving', 'icon' => 'truck'],
    'credit.php' => ['label' => 'Credit Management', 'icon' => 'users'],
    'reports.php' => ['label' => 'Reports', 'icon' => 'reports'],
  ];
}
?>
<nav class="sidebar">
  <div class="sidebar-inner p-4 d-flex flex-column text-white">
    <?php
      if (session_status() === PHP_SESSION_NONE) session_start();
      $user_name = get_user_full_name();
      $user_role = get_user_role();
    ?>
    <div class="brand mb-4">
      <div class="brand-title">Dingle Poultry<br/>Supply</div>
      <div class="brand-sub small text-muted"><?php echo $user_name ? htmlspecialchars($user_name) : 'hazel dato-on'; ?><br/><span class="role"><?php echo $user_role ? ucfirst(htmlspecialchars($user_role)) : 'Owner'; ?></span></div>
    </div>

    <ul class="nav flex-column mb-auto">
      <?php foreach ($links as $file => $meta):
        $isActive = ($current === $file) ? 'active' : '';
        $href = '/feed-inventory-tracker/' . $file;
        $label = $meta['label'];
        $icon = $meta['icon'];
      ?>
        <li class="nav-item mb-2">
          <a class="nav-link d-flex align-items-center <?php echo $isActive; ?>" href="<?php echo htmlspecialchars($href); ?>">
            <span class="icon me-3">
              <?php if ($icon === 'dashboard'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 13h8V3H3v10zM3 21h8v-6H3v6zM13 21h8V11h-8v10zM13 3v6h8V3h-8z" fill="currentColor"/></svg>
              <?php elseif ($icon === 'pos'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M7 7h10v2H7zM3 5h18v14H3zM5 11h14v2H5z" fill="currentColor"/></svg>
              <?php elseif ($icon === 'products'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 2L20 6v8l-8 4-8-4V6l8-4z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
              <?php elseif ($icon === 'truck'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M1 3h13v13H1zM16 8h6v8h-6z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
              <?php elseif ($icon === 'users'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zM8 11c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zM21 20v-1c0-2.21-3.13-4-7-4s-7 1.79-7 4v1h14z" fill="currentColor"/></svg>
              <?php elseif ($icon === 'reports'): ?>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M3 13h4v8H3zM10 7h4v14h-4zM17 3h4v18h-4z" fill="currentColor"/></svg>
              <?php endif; ?>
            </span>
            <span class="label"><?php echo htmlspecialchars($label); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="mt-auto">
      <a href="/feed-inventory-tracker/public/logout.php" class="nav-link logout d-flex align-items-center">
        <span class="icon me-3">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M16 17l5-5-5-5v3H9v4h7v3zM5 19h8v2H5a2 2 0 0 1-2-2V7h2v12z" fill="currentColor"/></svg>
        </span>
        <span class="label">Logout</span>
      </a>
    </div>

  </div>
</nav>
