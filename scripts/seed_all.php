<?php
// scripts/seed_all.php
// System-wide seeder for development/testing scenarios.
// Usage: php scripts/seed_all.php [--reset]

require_once __DIR__ . '/../config/database.php';

$opts = getopt('', ['reset']);
$pdo = get_pdo();

function info($msg){ echo '[*] ' . $msg . PHP_EOL; }
function err($msg){ echo '[!] ' . $msg . PHP_EOL; }

try {
    if (isset($opts['reset'])) {
        info('Reset requested — clearing most data (preserving admin user)');
        $pdo->beginTransaction();
        // child tables first
        $pdo->exec('DELETE FROM sale_items');
        $pdo->exec('DELETE FROM credit_payments');
        $pdo->exec('DELETE FROM sales');
        $pdo->exec('DELETE FROM inventory');
        $pdo->exec('DELETE FROM product_units');
        $pdo->exec('DELETE FROM products');
        $pdo->exec('DELETE FROM customers');
        // preserve 'admin' if exists
        $pdo->exec("DELETE FROM users WHERE username <> 'admin'");
        $pdo->commit();
        info('Reset complete.');
    }

    info('Seeding sample data...');
    $pdo->beginTransaction();

    // Create sample users (skip if username exists)
    $users = [
        ['username'=>'admin','password'=>'password123','full_name'=>'System Administrator','role'=>'owner'],
        ['username'=>'owner','password'=>'ownerpass','full_name'=>'Owner User','role'=>'owner'],
        ['username'=>'keeper1','password'=>'keeperpass','full_name'=>'Storekeeper 1','role'=>'storekeeper'],
        ['username'=>'keeper2','password'=>'keeperpass','full_name'=>'Storekeeper 2','role'=>'storekeeper'],
    ];
    $insertUser = $pdo->prepare('INSERT IGNORE INTO users (username, password_hash, full_name, role) VALUES (:u, :p, :n, :r)');
    foreach ($users as $u) {
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $insertUser->execute([':u'=>$u['username'], ':p'=>$hash, ':n'=>$u['full_name'], ':r'=>$u['role']]);
    }

    // Insert categories table if missing (safe)
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(191) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $cats = ['Feeds','DOC','Vet Supplies','Tools','Packaging'];
    $insCat = $pdo->prepare('INSERT IGNORE INTO categories (name) VALUES (:n)');
    foreach ($cats as $c) $insCat->execute([':n'=>$c]);

    // Products
    $products = [
        ['name'=>'B-MEG Broiler Starter','category'=>'Feeds','base_unit'=>'kg','description'=>'Broiler starter feed'],
        ['name'=>'Integra Broiler Grower','category'=>'Feeds','base_unit'=>'kg','description'=>'Broiler grower feed'],
        ['name'=>'Layer Feed Premium','category'=>'Feeds','base_unit'=>'kg','description'=>'Layer feed'],
        ['name'=>'DOC Starter','category'=>'DOC','base_unit'=>'pcs','description'=>'Day-old chicks'],
        ['name'=>'Antibiotic Mix A','category'=>'Vet Supplies','base_unit'=>'bottle','description'=>'Medication for poultry']
    ];

    $insP = $pdo->prepare('INSERT INTO products (name, category, description, base_unit, is_active) VALUES (:n, :c, :d, :b, 1)');
    $insUnit = $pdo->prepare('INSERT INTO product_units (product_id, unit_name, conversion_factor, selling_price) VALUES (:pid, :u, :cf, :price)');
    foreach ($products as $p) {
        // avoid duplicates by checking name
        $chk = $pdo->prepare('SELECT product_id FROM products WHERE name = :n LIMIT 1');
        $chk->execute([':n'=>$p['name']]);
        $pid = $chk->fetchColumn();
        if (!$pid) {
            $insP->execute([':n'=>$p['name'], ':c'=>$p['category'], ':d'=>$p['description'], ':b'=>$p['base_unit']]);
            $pid = $pdo->lastInsertId();
        }
        // add units
        if ($p['base_unit'] === 'kg') {
            $insUnit->execute([':pid'=>$pid, ':u'=>'Sack 50kg', ':cf'=>50, ':price'=>2400]);
            $insUnit->execute([':pid'=>$pid, ':u'=>'Kilo', ':cf'=>1, ':price'=>50]);
        } elseif ($p['base_unit'] === 'pcs') {
            $insUnit->execute([':pid'=>$pid, ':u'=>'Box 100pcs', ':cf'=>100, ':price'=>8000]);
            $insUnit->execute([':pid'=>$pid, ':u'=>'Piece', ':cf'=>1, ':price'=>80]);
        } else {
            $insUnit->execute([':pid'=>$pid, ':u'=>'Bottle', ':cf'=>1, ':price'=>350]);
        }
    }

    // Inventory batches
    $insInv = $pdo->prepare('INSERT INTO inventory (product_id, batch_number, supplier, quantity_on_hand, cost_price, expiry_date) VALUES (:pid, :batch, :supplier, :qty, :cost, :expiry)');
    // for each product create 2 batches
    $stmt = $pdo->query('SELECT product_id, base_unit FROM products');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $pid = $r['product_id'];
        $insInv->execute([':pid'=>$pid, ':batch'=>'BATCH-' . rand(100,999), ':supplier'=>'Local Supplier', ':qty'=>100, ':cost'=>30, ':expiry'=>date('Y-m-d', strtotime('+180 days'))]);
        $insInv->execute([':pid'=>$pid, ':batch'=>'BATCH-' . rand(1000,1999), ':supplier'=>'Warehouse Co', ':qty'=>50, ':cost'=>32, ':expiry'=>date('Y-m-d', strtotime('+90 days'))]);
    }

    // Customers
    $customers = [
        ['full_name'=>'Jeff Martinez','contact'=>'09171234567','address'=>'Town A'],
        ['full_name'=>'Hazel Dato-on','contact'=>'09179876543','address'=>'Town B'],
        ['full_name'=>'Mary Smith','contact'=>'09991234567','address'=>'Town C']
    ];
    $insC = $pdo->prepare('INSERT IGNORE INTO customers (full_name, contact_info, address) VALUES (:n, :c, :a)');
    foreach ($customers as $c) $insC->execute([':n'=>$c['full_name'], ':c'=>$c['contact'], ':a'=>$c['address']]);

        // Find an owner user to use as recorded_by / user_id (do this before audit inserts)
        $userId = $pdo->query("SELECT user_id FROM users WHERE username='admin' LIMIT 1")->fetchColumn() ?: 1;

        // Ensure audit_logs table exists and seed some entries
        $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            user_id INT DEFAULT NULL,
            action VARCHAR(191) NOT NULL,
            details TEXT DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Ensure the `event_time` column exists for older schemas that may lack it
        $colExists = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'event_time'")->fetchColumn();
        if (!$colExists) {
            $pdo->exec("ALTER TABLE audit_logs ADD COLUMN event_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER log_id");
        }

        $insLog = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details, event_time) VALUES (:uid, :act, :det, :dt)');
        $insLog->execute([':uid'=>$userId, ':act'=>'seed',':det'=>'Initial seeder run inserted baseline data',':dt'=>date('Y-m-d H:i:s', strtotime('-2 days'))]);
        $insLog->execute([':uid'=>$userId, ':act'=>'create_products',':det'=>'Seeded products and units',':dt'=>date('Y-m-d H:i:s', strtotime('-2 days +1 hour'))]);
        $insLog->execute([':uid'=>$userId, ':act'=>'create_inventory',':det'=>'Seeded inventory batches',':dt'=>date('Y-m-d H:i:s', strtotime('-1 day'))]);


    // Create a couple of sales (if tables exist) using available inventory stocks
    // Find an owner user to use as recorded_by / user_id
    $userId = $pdo->query("SELECT user_id FROM users WHERE username='admin' LIMIT 1")->fetchColumn() ?: 1;

    // pick some inventory rows
    $invRows = $pdo->query('SELECT stock_id, product_id, quantity_on_hand FROM inventory LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($invRows)) {
        $insSale = $pdo->prepare('INSERT INTO sales (customer_id, user_id, sale_date, total_amount, payment_method, payment_status) VALUES (:cid, :uid, :dt, :total, :pm, :status)');
        $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, inventory_stock_id, product_unit_id, quantity_sold, price_at_sale) VALUES (:sid, :stock, :unit, :qty, :price)');

        // cash sale
        $total = 0;
        $chosen = array_slice($invRows, 0, 2);
        foreach ($chosen as $c) { $total += 150 * 1; }
        $insSale->execute([':cid'=>NULL, ':uid'=>$userId, ':dt'=>date('Y-m-d H:i:s', strtotime('-1 day')), ':total'=>$total, ':pm'=>'Cash', ':status'=>'Paid']);
        $saleId = $pdo->lastInsertId();
        foreach ($chosen as $c) {
            $insItem->execute([':sid'=>$saleId, ':stock'=>$c['stock_id'], ':unit'=>NULL, ':qty'=>1, ':price'=>150]);
            // reduce inventory quantity for realism
            $pdo->prepare('UPDATE inventory SET quantity_on_hand = quantity_on_hand - :q WHERE stock_id = :s')->execute([':q'=>1, ':s'=>$c['stock_id']]);
        }

        // credit sale to an existing customer
        $custId = $pdo->query('SELECT customer_id FROM customers LIMIT 1')->fetchColumn();
        if ($custId) {
            $total = 0; $chosen = array_slice($invRows, 2, 2);
            foreach ($chosen as $c) { $total += 200; }
            $insSale->execute([':cid'=>$custId, ':uid'=>$userId, ':dt'=>date('Y-m-d H:i:s'), ':total'=>$total, ':pm'=>'Credit', ':status'=>'Unpaid']);
            $saleId = $pdo->lastInsertId();
            foreach ($chosen as $c) {
                $insItem->execute([':sid'=>$saleId, ':stock'=>$c['stock_id'], ':unit'=>NULL, ':qty'=>1, ':price'=>200]);
                $pdo->prepare('UPDATE inventory SET quantity_on_hand = quantity_on_hand - :q WHERE stock_id = :s')->execute([':q'=>1, ':s'=>$c['stock_id']]);
            }
        }
    }

    // Credit payments sample
    $cust = $pdo->query('SELECT customer_id FROM customers LIMIT 1')->fetchColumn();
    if ($cust) {
        $pdo->prepare('INSERT INTO credit_payments (customer_id, sale_id, amount_paid, payment_date, notes, recorded_by) VALUES (:cid, NULL, :amt, :dt, :notes, :rb)')
            ->execute([':cid'=>$cust, ':amt'=>500, ':dt'=>date('Y-m-d'), ':notes'=>'Initial payment', ':rb'=>$userId]);
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    } else {
        info('No active transaction to commit (DDL may have caused an implicit commit).');
    }
    info('Seeding completed successfully.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    err('Seeding failed: ' . $e->getMessage());
    exit(1);
}

info('Done.');

?>
