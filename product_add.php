<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_owner();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

$pdo = get_pdo();
$errors = [];

// Fetch categories for select (distinct existing categories)
$catStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $base_unit = sanitize($_POST['base_unit'] ?? '');

    if ($name === '' || $base_unit === '') {
        $errors[] = 'Name and Base Unit are required.';
    }

    $photoFilename = null;
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Error uploading photo.';
        } else {
            $tmp = $_FILES['photo']['tmp_name'];
            $orig = $_FILES['photo']['name'];
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif'];
            if (!in_array($ext, $allowed)) {
                $errors[] = 'Invalid photo format. Allowed: jpg, png, gif.';
            } else {
                $uploadDir = __DIR__ . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $photoFilename = uniqid('p_', true) . '.' . $ext;
                if (!move_uploaded_file($tmp, $uploadDir . $photoFilename)) {
                    $errors[] = 'Failed to move uploaded file.';
                }
            }
        }
    }

    if (empty($errors)) {
      // Insert into products table using actual column names; no created_at column exists
      $stmt = $pdo->prepare("INSERT INTO products (name, category, description, photo_filename, base_unit, is_active) VALUES (:name, :category, :desc, :photo, :base_unit, 1)");
      $stmt->execute([
        ':name' => $name,
        ':category' => $category,
        ':desc' => '',
        ':photo' => $photoFilename,
        ':base_unit' => $base_unit,
      ]);

      $productId = $pdo->lastInsertId();

      // Insert default unit into product_units (unit_id is auto)
      $uStmt = $pdo->prepare("INSERT INTO product_units (product_id, unit_name, conversion_factor, selling_price) VALUES (:pid, :unit, :factor, :price)");
      $uStmt->execute([
        ':pid' => $productId,
        ':unit' => $base_unit,
        ':factor' => 1.00,
        ':price' => 0.00,
      ]);

      header('Location: /feed-inventory-tracker/products.php');
      exit;
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>

<main class="p-4 flex-fill">
  <div class="container">
    <h2>Add New Product</h2>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Category</label>
        <select name="category" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Base Unit (e.g., kg)</label>
        <input name="base_unit" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control">
      </div>
      <div class="d-grid">
        <button class="btn btn-primary">Save Product</button>
      </div>
    </form>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
