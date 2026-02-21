-- =====================================================
-- PrintFlow - Service-Based Ordering System
-- Run once: mysql -u root -p printflow < database/service_orders_migration.sql
-- Or execute in phpMyAdmin SQL tab
-- =====================================================

-- Services lookup table (for reference, forms are hardcoded per service)
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert the 10 services
INSERT IGNORE INTO services (slug, name) VALUES
('tarpaulin', 'Tarpaulin Printing'),
('tshirt', 'T-Shirt Printing'),
('stickers', 'Decals / Stickers'),
('glass_stickers', 'Glass / Wall / Frosted Stickers'),
('transparent', 'Transparent Stickers'),
('layout', 'Layout Design Service'),
('reflectorized', 'Reflectorized Signages'),
('sintraboard', 'Stickers on Sintraboard'),
('standees', 'Sintraboard Standees'),
('souvenirs', 'Souvenirs');

-- Service orders (main orders table for service-based orders)
CREATE TABLE IF NOT EXISTS service_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    customer_id INT NOT NULL,
    status ENUM('Pending Review', 'Approved', 'Processing', 'Completed', 'Rejected') NOT NULL DEFAULT 'Pending Review',
    total_price DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    KEY idx_status (status),
    KEY idx_customer (customer_id),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order details (field_name, field_value pairs - one row per form field)
CREATE TABLE IF NOT EXISTS service_order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    field_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    KEY idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order files (uploaded designs/documents)
CREATE TABLE IF NOT EXISTS service_order_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES service_orders(id) ON DELETE CASCADE,
    KEY idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
