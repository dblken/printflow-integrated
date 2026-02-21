-- =====================================================
-- PrintFlow - Design Image DB Storage Migration
-- Stores uploaded design images as LONGBLOB in the DB
-- instead of saving files to the local filesystem.
--
-- Run once in phpMyAdmin SQL tab, or via CLI:
--   mysql -u root -p printflow < database/design_image_migration.sql
-- =====================================================
-- -------------------------------------------------------
-- 1. Add BLOB columns to order_items (product-based orders)
-- -------------------------------------------------------
ALTER TABLE order_items
ADD COLUMN IF NOT EXISTS design_image LONGBLOB DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS design_image_mime VARCHAR(50) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS design_image_name VARCHAR(255) DEFAULT NULL;
-- -------------------------------------------------------
-- 2. Update service_order_files (service-based orders)
--    Add BLOB columns; file_path is kept but no longer written.
-- -------------------------------------------------------
ALTER TABLE service_order_files
ADD COLUMN IF NOT EXISTS file_data LONGBLOB DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS mime_type VARCHAR(50) DEFAULT NULL;
-- Make file_path nullable (legacy rows may still have it)
ALTER TABLE service_order_files
MODIFY COLUMN file_path VARCHAR(255) DEFAULT NULL;