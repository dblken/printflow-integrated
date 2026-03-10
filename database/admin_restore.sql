-- Admin & Inventory Restoration Schema
-- Reconstructs the missing tables required by the new Admin Module
-- 1. Branches Table
CREATE TABLE IF NOT EXISTS `branches` (
    `id` int NOT NULL AUTO_INCREMENT,
    `branch_name` varchar(255) NOT NULL,
    `address` text,
    `contact_number` varchar(50) DEFAULT NULL,
    `status` enum('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_branch_name` (`branch_name`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 2. Inventory Categories
CREATE TABLE IF NOT EXISTS `inv_categories` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `sort_order` int DEFAULT '0',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 3. Inventory Items
CREATE TABLE IF NOT EXISTS `inv_items` (
    `id` int NOT NULL AUTO_INCREMENT,
    `category_id` int DEFAULT NULL,
    `sku` varchar(50) DEFAULT NULL,
    `name` varchar(255) NOT NULL,
    `description` text,
    `unit_of_measure` varchar(50) DEFAULT 'pcs',
    `track_by_roll` tinyint(1) DEFAULT '0',
    `default_roll_length_ft` decimal(10, 2) DEFAULT NULL,
    `reorder_level` decimal(10, 2) DEFAULT '0.00',
    `allow_negative_stock` tinyint(1) DEFAULT '0',
    `unit_cost` decimal(10, 2) DEFAULT '0.00',
    `status` enum('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_sku` (`sku`),
    KEY `fk_inv_category` (`category_id`),
    CONSTRAINT `fk_inv_category` FOREIGN KEY (`category_id`) REFERENCES `inv_categories` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 4. Branch Inventory (SOH per branch)
CREATE TABLE IF NOT EXISTS `branch_inventory` (
    `id` int NOT NULL AUTO_INCREMENT,
    `branch_id` int NOT NULL,
    `item_id` int NOT NULL,
    `stock_quantity` decimal(10, 2) NOT NULL DEFAULT '0.00',
    `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_branch_item` (`branch_id`, `item_id`),
    CONSTRAINT `fk_bi_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bi_item` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 5. Inventory Transactions
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `item_id` int NOT NULL,
    `branch_id` int DEFAULT NULL,
    `direction` enum('IN', 'OUT') NOT NULL,
    `quantity` decimal(10, 2) NOT NULL,
    `uom` varchar(20) DEFAULT NULL,
    `ref_type` varchar(50) NOT NULL,
    -- 'order', 'adjustment_manual', 'opening_balance'
    `ref_id` int DEFAULT NULL,
    `user_id` int DEFAULT NULL,
    `notes` text,
    `transaction_date` date NOT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_it_item` (`item_id`),
    KEY `fk_it_branch` (`branch_id`),
    CONSTRAINT `fk_it_item` FOREIGN KEY (`item_id`) REFERENCES `inv_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_it_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 6. Job Orders Table (New Module Style)
CREATE TABLE IF NOT EXISTS `job_orders` (
    `id` int NOT NULL AUTO_INCREMENT,
    `customer_id` int DEFAULT NULL,
    `customer_name` varchar(255) DEFAULT NULL,
    `service_type` varchar(100) NOT NULL,
    `width_ft` decimal(10, 2) DEFAULT '0',
    `height_ft` decimal(10, 2) DEFAULT '0',
    `quantity` int DEFAULT '1',
    `total_sqft` decimal(10, 2) DEFAULT '0',
    `price_per_sqft` decimal(10, 2) DEFAULT NULL,
    `price_per_piece` decimal(10, 2) DEFAULT NULL,
    `estimated_total` decimal(10, 2) DEFAULT NULL,
    `amount_paid` decimal(10, 2) DEFAULT '0.00',
    `required_payment` decimal(10, 2) DEFAULT '0.00',
    `payment_status` enum('UNPAID', 'PARTIAL', 'PAID') DEFAULT 'UNPAID',
    `status` enum(
        'PENDING',
        'PROCESSING',
        'READY',
        'COMPLETED',
        'CANCELLED'
    ) DEFAULT 'PENDING',
    `priority` enum('NORMAL', 'HIGH', 'URGENT') DEFAULT 'NORMAL',
    `machine_id` int DEFAULT NULL,
    `notes` text,
    `artwork_path` varchar(255) DEFAULT NULL,
    `due_date` date DEFAULT NULL,
    `branch_id` int NOT NULL DEFAULT '1',
    `created_by` int DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_jo_branch` (`branch_id`),
    CONSTRAINT `fk_jo_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 7. Service Material Rules
CREATE TABLE IF NOT EXISTS `service_material_rules` (
    `id` int NOT NULL AUTO_INCREMENT,
    `service_type` varchar(100) NOT NULL,
    `item_id` int NOT NULL,
    `rule_type` varchar(50) DEFAULT 'AUTO',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci;
-- 8. Add Branch to existing tables
SET @dbname = DATABASE();
SET @tablename = 'users';
SET @columnname = 'branch_id';
SET @preparedStatement = (
        SELECT IF(
                (
                    SELECT COUNT(*)
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = @dbname
                        AND TABLE_NAME = @tablename
                        AND COLUMN_NAME = @columnname
                ) > 0,
                'SELECT 1',
                CONCAT(
                    'ALTER TABLE ',
                    @tablename,
                    ' ADD COLUMN ',
                    @columnname,
                    ' INT DEFAULT 1'
                )
            )
    );
PREPARE stmt
FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
-- Seed initial branch if empty
INSERT IGNORE INTO `branches` (id, branch_name, status)
VALUES (1, 'Main Branch', 'Active');