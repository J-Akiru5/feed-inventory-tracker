-- 2. Users Table (Owners and Storekeepers)
CREATE TABLE users (
  user_id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  password_hash varchar(255) NOT NULL,
  full_name varchar(100) NOT NULL,
  role enum('owner','storekeeper') NOT NULL DEFAULT 'storekeeper',
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Products Table (The definition of an item)
CREATE TABLE products (
  product_id int(11) NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  category varchar(50) NOT NULL COMMENT 'Feeds, DOC, Vet Supplies, Tools, Packaging',
  description text,
  photo_filename varchar(255) DEFAULT NULL,
  base_unit varchar(20) NOT NULL COMMENT 'e.g., kg, pcs, bottle',
  is_active tinyint(1) DEFAULT 1,
  PRIMARY KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Product Units Table (Conversion Logic, e.g., Sack vs Kilo)
CREATE TABLE product_units (
  unit_id int(11) NOT NULL AUTO_INCREMENT,
  product_id int(11) NOT NULL,
  unit_name varchar(50) NOT NULL COMMENT 'e.g., Sack 50kg, Kilo',
  conversion_factor decimal(10,2) NOT NULL COMMENT 'How many base units? e.g., 50.00 for Sack',
  selling_price decimal(10,2) NOT NULL,
  PRIMARY KEY (unit_id),
  KEY product_id (product_id),
  CONSTRAINT fk_units_product FOREIGN KEY (product_id) REFERENCES products (product_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Inventory Table (Physical Batches with Expiry)
CREATE TABLE inventory (
  stock_id int(11) NOT NULL AUTO_INCREMENT,
  product_id int(11) NOT NULL,
  batch_number varchar(50) DEFAULT NULL,
  supplier varchar(100) DEFAULT NULL,
  quantity_on_hand decimal(10,2) NOT NULL COMMENT 'Always stored in base_unit',
  cost_price decimal(10,2) NOT NULL COMMENT 'Cost per base unit',
  expiry_date date DEFAULT NULL,
  received_date datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (stock_id),
  KEY product_id (product_id),
  CONSTRAINT fk_inv_product FOREIGN KEY (product_id) REFERENCES products (product_id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Customers Table (For Credit/'Utang' Tracking)
CREATE TABLE customers (
  customer_id int(11) NOT NULL AUTO_INCREMENT,
  full_name varchar(100) NOT NULL,
  contact_info varchar(100) DEFAULT NULL,
  address text,
  id_proof_filename varchar(255) DEFAULT NULL,
  backup_contact varchar(100) DEFAULT NULL,
  status enum('Active','Suspended','Blacklisted') DEFAULT 'Active',
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Sales Table (Transaction Header)
CREATE TABLE sales (
  sale_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) DEFAULT NULL COMMENT 'NULL if Walk-in',
  user_id int(11) NOT NULL COMMENT 'Who processed the sale',
  sale_date datetime DEFAULT CURRENT_TIMESTAMP,
  total_amount decimal(10,2) NOT NULL,
  payment_method enum('Cash','Online','Credit') NOT NULL,
  payment_status enum('Paid','Partial','Unpaid') NOT NULL,
  due_date date DEFAULT NULL,
  remarks text,
  PRIMARY KEY (sale_id),
  KEY customer_id (customer_id),
  KEY user_id (user_id),
  CONSTRAINT fk_sale_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id) ON DELETE SET NULL,
  CONSTRAINT fk_sale_user FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Sale Items Table (Transaction Details)
CREATE TABLE sale_items (
  sale_item_id int(11) NOT NULL AUTO_INCREMENT,
  sale_id int(11) NOT NULL,
  inventory_stock_id int(11) NOT NULL,
  product_unit_id int(11) DEFAULT NULL COMMENT 'Which unit was selected at POS',
  quantity_sold decimal(10,2) NOT NULL,
  price_at_sale decimal(10,2) NOT NULL,
  PRIMARY KEY (sale_item_id),
  KEY sale_id (sale_id),
  KEY inventory_stock_id (inventory_stock_id),
  CONSTRAINT fk_items_sale FOREIGN KEY (sale_id) REFERENCES sales (sale_id) ON DELETE CASCADE,
  CONSTRAINT fk_items_stock FOREIGN KEY (inventory_stock_id) REFERENCES inventory (stock_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Credit Payments Table (Utang Payments)
CREATE TABLE credit_payments (
  payment_id int(11) NOT NULL AUTO_INCREMENT,
  customer_id int(11) NOT NULL,
  sale_id int(11) DEFAULT NULL COMMENT 'Optional: link to specific sale',
  amount_paid decimal(10,2) NOT NULL,
  payment_date date DEFAULT NULL,
  notes varchar(255) DEFAULT NULL,
  recorded_by int(11) NOT NULL,
  PRIMARY KEY (payment_id),
  KEY customer_id (customer_id),
  CONSTRAINT fk_pay_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. INSERT DEFAULT ADMIN USER
-- Username: admin
-- Password: password123
INSERT INTO users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'owner');