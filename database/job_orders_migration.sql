-- =====================================================
-- PrintFlow - Job Orders & Production System
-- Run once: mysql -u root -p printflow < database/job_orders_migration.sql
-- =====================================================
-- Job Orders (main internal production tracking)
CREATE TABLE IF NOT EXISTS job_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_title VARCHAR(255) DEFAULT NULL,
    service_type VARCHAR(100) DEFAULT 'Tarpaulin',
    customer_id INT DEFAULT NULL,
    width_ft DECIMAL(10, 2) DEFAULT 0.00,
    height_ft DECIMAL(10, 2) DEFAULT 0.00,
    quantity INT DEFAULT 1,
    status ENUM(
        'PENDING',
        'APPROVED',
        'IN_PRODUCTION',
        'TO_PAY',
        'COMPLETED',
        'CANCELLED',
        'TO_RECEIVE'
    ) NOT NULL DEFAULT 'PENDING',
    customer_type ENUM('NEW', 'REGULAR') DEFAULT 'NEW',
    machine_id INT DEFAULT NULL,
    estimated_total DECIMAL(12, 2) DEFAULT 0.00,
    due_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE
    SET NULL,
        KEY idx_status (status),
        KEY idx_created (created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Job Order Materials (granular production tracking)
CREATE TABLE IF NOT EXISTS job_order_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(12, 2) DEFAULT 1.00,
    uom VARCHAR(20) DEFAULT 'ft',
    roll_id INT DEFAULT NULL,
    notes TEXT,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES job_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES materials(material_id) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- Optional: Assign some existing service_orders to job_orders if desired
-- INSERT INTO job_orders (job_title, service_type, customer_id, status, created_at)
-- SELECT service_name, service_name, customer_id, 'PENDING', created_at 
-- FROM service_orders WHERE status = 'Pending Review';