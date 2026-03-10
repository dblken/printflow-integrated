-- =====================================================
-- PrintFlow - Sintra Board Specs Migration
-- Adds columns for detailed Sintra Board specifications
-- =====================================================
-- -------------------------------------------------------
-- 1. Update order_items table
-- -------------------------------------------------------
ALTER TABLE order_items
ADD COLUMN IF NOT EXISTS width DECIMAL(10, 2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS height DECIMAL(10, 2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS thickness VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS stand_type VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS lamination VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cut_type VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS design_notes TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS design_file_path VARCHAR(255) DEFAULT NULL;
-- -------------------------------------------------------
-- 2. Update service_orders table (for consistency)
-- -------------------------------------------------------
ALTER TABLE service_orders
ADD COLUMN IF NOT EXISTS width DECIMAL(10, 2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS height DECIMAL(10, 2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS thickness VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS stand_type VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS lamination VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS cut_type VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS design_notes TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS design_file_path VARCHAR(255) DEFAULT NULL;