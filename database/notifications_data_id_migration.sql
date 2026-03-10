-- Database migration to add data_id to notifications
-- Run this to allow linking notifications to specific objects (like orders)
ALTER TABLE notifications
ADD COLUMN data_id INT DEFAULT NULL
AFTER message;