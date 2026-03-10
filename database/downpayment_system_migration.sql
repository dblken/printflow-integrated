-- Database migration for Downpayment System
-- Run this to update the orders table and status enum
-- Add payment columns
ALTER TABLE orders
ADD COLUMN payment_proof VARCHAR(255) DEFAULT NULL
AFTER payment_reference,
    ADD COLUMN payment_submitted_at DATETIME DEFAULT NULL
AFTER payment_proof;
-- Update status enum
-- MySQL doesn't allow easy modification of enums, so we'll use a safer approach 
-- depending on the version, but usually MODIFY COLUMN works.
ALTER TABLE orders
MODIFY COLUMN status ENUM(
        'Pending',
        'Pending Review',
        'For Revision',
        'Pending Approval',
        'To Pay',
        'Downpayment Submitted',
        'Paid – In Process',
        'Processing',
        'In Production',
        'Printing',
        'Ready for Pickup',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending';