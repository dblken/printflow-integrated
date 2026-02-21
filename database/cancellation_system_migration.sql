-- Migration: Order Cancellation Management System
-- Date: 2026-02-21
-- Update orders table to track cancellation details and production timing
ALTER TABLE orders
ADD COLUMN cancelled_by ENUM('Customer', 'Staff', 'Admin') DEFAULT NULL,
    ADD COLUMN cancel_reason TEXT DEFAULT NULL,
    ADD COLUMN cancelled_at DATETIME DEFAULT NULL,
    ADD COLUMN production_started_at DATETIME DEFAULT NULL;
-- Ensure status column includes all required statuses if not already there
-- Assuming it's a VARCHAR or ENUM. If it's ENUM, we'd need to modify it carefully.
-- For now, we'll assume it handles 'Pending', 'In Production', 'Printing', 'Completed', 'Cancelled'.
-- Update customers table to track cancellation abuse
ALTER TABLE customers
ADD COLUMN cancel_count INT DEFAULT 0,
    ADD COLUMN is_restricted BOOLEAN DEFAULT 0;