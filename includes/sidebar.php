<?php
$current = basename($_SERVER['PHP_SELF']);
$links = [
  'index.php' => 'Dashboard',
  'pos.php' => 'POS',
  'products.php' => 'Products',
  'inventory.php' => 'Stock Receive',
  'customers.php' => 'Customers',
  'reports.php' => 'Reports',
];
?>
<nav class="sidebar p-3 text-white">
  <ul class="nav flex-column">
    <?php foreach ($links as $file => $label):
      $isActive = ($current === $file) ? 'active' : '';
      $href = '/feed-inventory-tracker/' . $file;
    ?>
      <li class="nav-item">
        <a class="nav-link <?php echo $isActive; ?>" href="<?php echo htmlspecialchars($href); ?>"><?php echo htmlspecialchars($label); ?></a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
