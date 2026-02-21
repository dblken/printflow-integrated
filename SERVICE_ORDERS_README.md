# PrintFlow - Service-Based Ordering System

## Overview

The service ordering system allows customers to place orders for 10 different printing services. Each service has its own order form with specific fields. Staff can view, approve, or reject orders.

---

## Folder Structure

```
printflow/
├── customer/
│   ├── order_tarpaulin.php      # Tarpaulin Printing
│   ├── order_tshirt.php         # T-Shirt Printing
│   ├── order_stickers.php       # Decals / Stickers
│   ├── order_glass_stickers.php # Glass / Wall / Frosted
│   ├── order_transparent.php    # Transparent Stickers
│   ├── order_layout.php         # Layout Design Service
│   ├── order_reflectorized.php  # Reflectorized Signages
│   ├── order_sintraboard.php    # Stickers on Sintraboard
│   ├── order_standees.php       # Sintraboard Standees
│   ├── order_souvenirs.php      # Souvenirs
│   ├── order_success.php        # Success page after submit
│   ├── service_orders.php       # Customer: My Service Orders list
│   └── service_order_view.php   # Customer: View single order
├── staff/
│   ├── service_orders.php       # Staff: Service Orders list
│   └── service_order_details.php # Staff: View order + Approve/Reject
├── includes/
│   └── service_order_helper.php # Shared: file upload, validation, DB insert
├── uploads/
│   └── orders/                  # Uploaded design files (JPG, PNG, PDF)
└── database/
    └── service_orders_migration.sql # DB schema
```

---

## Database Setup

Run the migration to create tables:

**Option 1 - phpMyAdmin:**
1. Open phpMyAdmin → select `printflow` database
2. Go to SQL tab
3. Paste contents of `database/service_orders_migration.sql`
4. Execute

**Option 2 - Command line:**
```bash
mysql -u root -p printflow < database/service_orders_migration.sql
```

**Tables created:**
- `services` - Reference list of 10 services
- `service_orders` - Main orders (id, service_name, customer_id, status, total_price, created_at)
- `service_order_details` - Field/value pairs per order
- `service_order_files` - Uploaded file paths

**Note:** Tables are also auto-created on first use if the migration was not run.

---

## How It Works

### Customer Flow
1. Customer logs in → Dashboard shows "Order a Service" section
2. Clicks a service (e.g. Tarpaulin)
3. Fills form and uploads design (JPG, PNG, PDF - max 5MB)
4. Submits → Order saved with status "Pending Review"
5. Redirected to success page
6. Can view orders at "My Service Orders"

### Staff Flow
1. Staff goes to **Service Orders** in sidebar
2. Sees list of all service orders (filter by status)
3. Clicks "View" on an order
4. Sees: Order info, Customer info, All selected options, Uploaded files (preview if image)
5. **Approve** → Status = Processing
6. **Reject** → Status = Rejected
7. Or use dropdown to manually update status (Completed, etc.)

---

## File Upload Rules

- **Allowed types:** JPG, JPEG, PNG, PDF
- **Max size:** 5MB
- **Storage:** `/printflow/uploads/orders/`
- Files are validated before save (MIME type + extension)

---

## Order Statuses

| Status          | Description                    |
|-----------------|--------------------------------|
| Pending Review  | New order, awaiting staff      |
| Approved        | Staff approved                 |
| Processing      | Order is being worked on       |
| Completed       | Order finished                 |
| Rejected        | Order rejected by staff        |

---

## URLs (Customer)

| Page              | URL                                    |
|-------------------|----------------------------------------|
| Order Tarpaulin   | `/printflow/customer/order_tarpaulin.php` |
| Order T-Shirt     | `/printflow/customer/order_tshirt.php` |
| My Service Orders | `/printflow/customer/service_orders.php` |

(Similarly for other services.)

---

## URLs (Staff)

| Page              | URL                                           |
|-------------------|-----------------------------------------------|
| Service Orders    | `/printflow/staff/service_orders.php`         |
| Order Details     | `/printflow/staff/service_order_details.php?id=X` |
