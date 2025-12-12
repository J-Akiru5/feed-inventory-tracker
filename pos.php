<?php
require_once __DIR__ . '/functions/auth_guard.php';
require_login();
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$pdo = get_pdo();

// Load products (simple list for POS)
$stmt = $pdo->prepare("SELECT p.product_id, p.name, p.category, p.base_unit, COALESCE(SUM(i.quantity_on_hand),0) AS stock
  FROM products p
  LEFT JOIN inventory i ON i.product_id = p.product_id
  WHERE p.is_active = 1
  GROUP BY p.product_id
  ORDER BY p.name ASC");
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load categories
$stmt = $pdo->prepare("SELECT DISTINCT COALESCE(category, '') AS name FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Load customers (for customer select)
$stmt = $pdo->prepare("SELECT customer_id, full_name FROM customers ORDER BY full_name ASC");
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main class="p-4 flex-fill">
  <div class="container-fluid">
    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-3 p-3 glass-panel">
          <div class="row g-2 align-items-center">
            <div class="col-md-6">
              <input id="posSearch" class="form-control" placeholder="Search products..." />
            </div>
            <div class="col-md-3">
              <select id="posCategory" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 text-end">
              <div class="btn-group" role="group">
                <button id="posViewCards" class="btn btn-sm btn-primary">Cards</button>
                <button id="posViewTable" class="btn btn-sm btn-outline-secondary">Table</button>
              </div>
            </div>
          </div>
        </div>

        <div id="posProducts" class="card p-5 glass-panel text-center">
          <?php if (empty($products)): ?>
            <div class="p-5">No products found</div>
          <?php else: ?>
            <div class="row g-3" id="posCards">
              <?php foreach ($products as $p): ?>
                <div class="col-md-4">
                  <div class="card p-3 h-100 pos-product glass-panel" data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>" data-cat="<?php echo htmlspecialchars($p['category']); ?>">
                    <div class="fw-bold"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="small text-muted"><?php echo htmlspecialchars($p['category']); ?></div>
                    <div class="mt-3">Stock: <?php echo rtrim(rtrim(number_format((float)$p['stock'],2),'0'),'.'); ?> <?php echo htmlspecialchars($p['base_unit']); ?></div>
                    <div class="mt-3 text-end"><button class="btn btn-sm btn-outline-primary add-to-cart" data-id="<?php echo (int)$p['product_id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>">Add</button></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div id="posTable" style="display:none;">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th></th></tr></thead>
                  <tbody>
                    <?php foreach ($products as $p): ?>
                      <tr class="pos-product" data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>" data-cat="<?php echo htmlspecialchars($p['category']); ?>">
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td><?php echo rtrim(rtrim(number_format((float)$p['stock'],2),'0'),'.'); ?></td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-primary add-to-cart" data-id="<?php echo (int)$p['product_id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>">Add</button></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card p-3 glass-panel">
          <h5 class="card-title">Cart</h5>
          <div id="cartEmpty" class="text-muted text-center py-4">Cart is empty</div>
          <div id="cartContents" style="display:none;">
            <ul id="cartList" class="list-group mb-3"></ul>
          </div>

          <div class="mt-3">
            <label class="form-label">Customer *</label>
            <select id="customerSelect" class="form-select mb-2">
              <option value="0">🏃‍♂️ Walk-in Customer</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?php echo (int)$c['customer_id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
              <?php endforeach; ?>
            </select>
            <div class="small text-muted mb-3">Unregistered customer - credit payment not available</div>

            <label class="form-label">Payment Method *</label>
            <select id="paymentMethod" class="form-select mb-3">
              <option value="cash">💵 Cash</option>
              <option value="card">💳 Card</option>
              <option value="mobile">📱 GCash</option>
            </select>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <div>Total:</div>
              <div class="fw-bold">₱<span id="cartTotal">0.00</span></div>
            </div>

            <div class="d-grid mt-3">
              <button id="completeSale" class="btn btn-success btn-lg" style="border-radius:12px;padding:14px;">Complete Sale</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
(function(){
  const search = document.getElementById('posSearch');
  const cat = document.getElementById('posCategory');
  const cards = document.getElementById('posCards');
  const table = document.getElementById('posTable');
  const viewCards = document.getElementById('posViewCards');
  const viewTable = document.getElementById('posViewTable');

  function filter() {
    const q = (search.value || '').toLowerCase();
    const c = (cat.value || '');
    document.querySelectorAll('.pos-product').forEach(el=>{
      const name = (el.dataset.name||'');
      const category = (el.dataset.cat||'');
      const match = (name.includes(q) || q==='') && (c === '' || category === c);
      if (el.closest('.col-md-4')) el.closest('.col-md-4').style.display = match ? '' : 'none';
      else el.style.display = match ? '' : 'none';
    });
  }
  search.addEventListener('input', filter);
  cat.addEventListener('change', filter);

  viewCards.addEventListener('click', ()=>{ cards.style.display='flex'; table.style.display='none'; viewCards.classList.add('btn-primary'); viewTable.classList.remove('btn-primary'); });
  viewTable.addEventListener('click', ()=>{ cards.style.display='none'; table.style.display='block'; viewTable.classList.add('btn-primary'); viewCards.classList.remove('btn-primary'); });

  // Basic cart
  const cart = {};
  function renderCart(){
    const cartList = document.getElementById('cartList');
    const cartEmpty = document.getElementById('cartEmpty');
    const cartContents = document.getElementById('cartContents');
    cartList.innerHTML = '';
    let total = 0;
    const keys = Object.keys(cart);
    if (keys.length===0){ cartEmpty.style.display='block'; cartContents.style.display='none'; }
    else {
      cartEmpty.style.display='none'; cartContents.style.display='block';
      keys.forEach(id=>{
        const item = cart[id];
        const li = document.createElement('li'); li.className='list-group-item d-flex justify-content-between align-items-center';
        li.innerHTML = `<div>${escapeHtml(item.name)} <small class="text-muted">x${item.qty}</small></div><div>₱${(item.price||0).toFixed(2)}</div>`;
        cartList.appendChild(li);
        total += (item.price||0) * item.qty;
      });
    }
    document.getElementById('cartTotal').textContent = total.toFixed(2);
  }

  function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

  document.querySelectorAll('.add-to-cart').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id = btn.dataset.id;
      const name = btn.dataset.name;
      if (!cart[id]) cart[id] = { name: name, qty: 0, price: 0 };
      cart[id].qty += 1;
      renderCart();
    });
  });

  document.getElementById('completeSale').addEventListener('click', ()=>{
    alert('Complete Sale clicked — implement server-side processing');
  });

})();
</script>
