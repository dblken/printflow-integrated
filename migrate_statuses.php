<?php
require_once 'includes/db.php';

echo "Updating orders table status enum...\n";

// Current list: 'Pending','Pending Review','For Revision','Pending Approval','To Pay','Processing','In Production','Printing','Ready for Pickup','Completed','Cancelled'
// Adding: 'Downpayment Submitted', 'Paid – In Process'

$new_enum = "ENUM('Pending','Pending Review','For Revision','Pending Approval','To Pay','Processing','In Production','Printing','Ready for Pickup','Completed','Cancelled', 'Downpayment Submitted', 'Paid – In Process')";

$sql = "ALTER TABLE orders MODIFY COLUMN status $new_enum DEFAULT 'Pending'";

$success = db_execute($sql);

if ($success) {
    echo "Database schema updated successfully.\n";
} else {
    echo "Failed to update database schema: " . $conn->error . "\n";
}
?>
