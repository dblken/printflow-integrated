<?php
/**
 * Staff Orders Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Staff');
require_once __DIR__ . '/../includes/staff_pending_check.php';

// Handle status update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['status'];
        $staff_id = get_user_id();

        // Use the centralized update_order_status logic
        $success = update_order_status($order_id, $new_status, $staff_id);

        if ($success) {
            // Log activity
            log_activity($staff_id, 'Order Status Update', "Updated Order #{$order_id} to {$new_status}");

            // Notify customer
            $order_data = db_query("SELECT customer_id FROM orders WHERE order_id = ?", 'i', [$order_id]);
            if (!empty($order_data)) {
                if ($new_status === 'To Pay') {
                    $msg = "💳 Your order #{$order_id} has been approved! Please prepare your payment upon pickup.";
                } else {
                    $msg = "Your order #{$order_id} status has been updated to: {$new_status}";
                }
                // Do not send email to prevent hanging on local XAMPP
                create_notification($order_data[0]['customer_id'], 'Customer', $msg, 'Order', false, false);
            }

            // If AJAX, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'new_status' => $new_status]);
                exit;
            }

            redirect('/printflow/staff/orders.php?success=1');
        } else {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Database update failed']);
                exit;
            }
            redirect('/printflow/staff/orders.php?error=1');
        }
    } else {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';

// Build query
$sql = "SELECT o.*, CONCAT(c.first_name, ' ', c.last_name) as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.customer_id WHERE 1=1";
$params = [];
$types = '';

if (!empty($status_filter)) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Pagination settings
$items_per_page = 10;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

// Count total items for pagination
$count_sql = "SELECT COUNT(*) as total FROM orders o WHERE 1=1";
$count_params = [];
$count_types = '';

if (!empty($status_filter)) {
    $count_sql .= " AND o.status = ?";
    $count_params[] = $status_filter;
    $count_types .= 's';
}

$total_result = db_query($count_sql, $count_types, $count_params);
$total_items = $total_result[0]['total'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);

$sql .= " ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$orders = db_query($sql, $types, $params);

$page_title = 'Orders - Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="/printflow/public/assets/css/output.css">
    <?php include __DIR__ . '/../includes/admin_style.php'; ?>
    <style>
        /* ── Order Detail Modal ─────────────────────────────────── */
        #orderModal {
            position: fixed; inset: 0; z-index: 9999;
            display: flex; align-items: center; justify-content: center;
            padding: 16px;
            opacity: 0; pointer-events: none;
            transition: opacity 0.25s ease;
        }
        #orderModal.open { opacity: 1; pointer-events: all; }

        .om-backdrop {
            position: absolute; inset: 0;
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px);
            transition: opacity 0.25s ease;
        }

        .om-panel {
            position: relative; z-index: 1;
            background: #fff;
            border-radius: 20px;
            width: 100%; max-width: 960px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            transform: translateY(24px) scale(0.97);
            transition: transform 0.3s cubic-bezier(.34,1.56,.64,1), opacity 0.25s ease;
            opacity: 0;
        }
        #orderModal.open .om-panel {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .om-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 24px 28px 20px;
            border-bottom: 1px solid #f1f5f9;
            position: sticky; top: 0; background: #fff; border-radius: 20px 20px 0 0; z-index: 2;
        }
        .om-title { font-size: 1.35rem; font-weight: 800; color: #0f172a; }
        .om-subtitle { font-size: 0.78rem; color: #94a3b8; margin-top: 2px; }
        .om-close {
            width: 36px; height: 36px; border-radius: 50%;
            border: none; background: #f1f5f9; color: #64748b;
            cursor: pointer; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;
            transition: background 0.15s, color 0.15s;
        }
        .om-close:hover { background: #e2e8f0; color: #0f172a; }

        .om-body { padding: 24px 28px 28px; }
        .om-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 700px) { .om-grid { grid-template-columns: 1fr; } }

        .om-card {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 14px; padding: 20px;
        }
        .om-card-title {
            font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 0.07em; color: #94a3b8; margin-bottom: 14px;
        }
        .om-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 8px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px;
        }
        .om-row:last-child { border-bottom: none; }
        .om-label { color: #6b7280; }
        .om-value { font-weight: 600; color: #1e293b; text-align: right; }

        .om-notes {
            margin-top: 14px; padding: 14px 16px;
            background: linear-gradient(135deg,#fffbeb,#fef3c7);
            border: 1px solid #fde68a; border-radius: 12px;
        }
        .om-notes-title { font-size: 12px; font-weight: 800; color: #92400e; margin-bottom: 6px; }
        .om-notes-text { font-size: 13px; color: #b45309; line-height: 1.6; }

        .om-cust-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .om-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: linear-gradient(135deg,#667eea,#764ba2);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 700; font-size: 16px; flex-shrink: 0;
        }

        .om-status-form { margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .om-status-form-title { font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 10px; }
        .om-status-row { display: flex; gap: 8px; }
        .om-status-row select { flex: 1; }
        .om-status-row button { white-space: nowrap; }

        /* Items table */
        .om-items-section { margin-top: 20px; }
        .om-items-title { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; margin-bottom: 12px; }
        .om-items-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        .om-items-table th {
            text-align: left; padding: 8px 10px;
            font-size: 0.65rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.06em; color: #94a3b8;
            border-bottom: 2px solid #e2e8f0;
        }
        .om-items-table td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .om-items-table tr:last-child td { border-bottom: none; }
        .om-items-total td { border-top: 2px solid #e2e8f0 !important; font-weight: 700; }

        /* Design image */
        .om-design-wrap { margin-top: 10px; }
        .om-design-img {
            max-width: 140px; border-radius: 8px; border: 2px solid #e2e8f0;
            cursor: zoom-in; transition: transform 0.2s, box-shadow 0.2s;
        }
        .om-design-img:hover { transform: scale(1.04); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }

        /* Customs chips */
        .om-custom-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .om-chip {
            background: #e0e7ff; color: #4338ca;
            border-radius: 99px; padding: 2px 10px;
            font-size: 11px; font-weight: 600;
        }

        /* Loader */
        .om-loader { text-align: center; padding: 64px 0; }
        .om-spinner {
            width: 40px; height: 40px; border-radius: 50%;
            border: 3px solid #e2e8f0; border-top-color: #6366f1;
            animation: om-spin 0.7s linear infinite; margin: 0 auto 12px;
        }
        @keyframes om-spin { to { transform: rotate(360deg); } }

        /* Alert flash inside modal */
        .om-alert { border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 14px; }
        .om-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; }
        .om-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

        /* Customer orders list */
        .om-cust-orders { margin-top: 14px; }
        .om-co-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; }
        .om-co-row:last-child { border-bottom: none; }

        /* Status badge replicated in JS */
        .badge { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }
        .badge-gray { background: #f3f4f6; color: #374151; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Sidebar -->
    <?php include __DIR__ . '/../includes/staff_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header>
            <h1 class="page-title">Orders Management</h1>
        </header>

        <main>
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4" style="background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">
                    Order status updated successfully!
                </div>
            <?php endif; ?>

            <!-- Filter -->
            <div class="card">
                <form method="GET" style="display:flex; gap:16px; align-items:flex-end;">
                    <div style="flex:1;">
                        <label>Filter by Status</label>
                        <select name="status" class="input-field">
                            <option value="">All Statuses</option>
                            <option value="Pending Review" <?php echo $status_filter === 'Pending Review' ? 'selected' : ''; ?>>Pending Review</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Processing" <?php echo $status_filter === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="Ready for Pickup" <?php echo $status_filter === 'Ready for Pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                            <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-primary">Apply Filter</button>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="card">
                <div class="overflow-x-auto">
                    <table>
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td style="font-weight:500;">#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo format_date($order['order_date']); ?></td>
                                    <td style="font-weight:600;"><?php echo format_currency($order['total_amount']); ?></td>
                                    <td><?php echo status_badge($order['status'], 'order'); ?></td>
                                    <td style="text-align:right;">
                                        <button
                                            onclick="openOrderModal(<?php echo $order['order_id']; ?>)"
                                            style="background:none; border:none; color:#10b981; font-size:13px; font-weight:600; cursor:pointer; padding:4px 8px; border-radius:6px; transition:background 0.15s;"
                                            onmouseover="this.style.background='#d1fae5'"
                                            onmouseout="this.style.background='none'"
                                        >
                                            View/Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php echo get_pagination_links($current_page, $total_pages, ['status' => $status_filter]); ?>
        </main>
    </div>
</div>

<!-- ══════════════════════════════════════════
     ORDER DETAIL MODAL
═══════════════════════════════════════════ -->
<div id="orderModal" role="dialog" aria-modal="true" aria-labelledby="omTitle">
    <div class="om-backdrop" onclick="closeOrderModal()"></div>
    <div class="om-panel">
        <div class="om-header">
            <div>
                <div class="om-title" id="omTitle">Order Details</div>
                <div class="om-subtitle" id="omSubtitle">Loading…</div>
            </div>
            <button class="om-close" onclick="closeOrderModal()" aria-label="Close">✕</button>
        </div>
        <div class="om-body" id="omBody">
            <!-- Loader -->
            <div class="om-loader">
                <div class="om-spinner"></div>
                <div style="color:#94a3b8; font-size:14px;">Fetching order details…</div>
            </div>
        </div>
    </div>
</div>

<script>
let currentOrderId = null;

// ── Status badge helper ──────────────────────────────────
function statusBadge(val) {
    const map = {
        'Completed':        'badge-green',
        'Pending':          'badge-yellow',
        'Pending Review':   'badge-yellow',
        'Pending Approval': 'badge-yellow',
        'Processing':       'badge-blue',
        'In Production':    'badge-blue',
        'Printing':         'badge-blue',
        'Ready for Pickup': 'badge-purple',
        'Cancelled':        'badge-red',
        'For Revision':     'badge-blue',
        'Paid':             'badge-green',
        'Unpaid':           'badge-gray',
        'Partial':          'badge-yellow',
        'To Pay':           'badge-orange',
    };
    const cls = map[val] || 'badge-gray';
    return `<span class="badge ${cls}">${val}</span>`;
}

// ── Open / close ─────────────────────────────────────────
function openOrderModal(orderId) {
    currentOrderId = orderId;
    const modal = document.getElementById('orderModal');
    document.getElementById('omTitle').textContent = `Order #${orderId}`;
    document.getElementById('omSubtitle').textContent = 'Loading…';
    document.getElementById('omBody').innerHTML = `
        <div class="om-loader">
            <div class="om-spinner"></div>
            <div style="color:#94a3b8;font-size:14px;">Fetching order details…</div>
        </div>`;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch(`/printflow/staff/get_order_data.php?id=${orderId}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => renderOrderModal(data))
    .catch(() => {
        document.getElementById('omBody').innerHTML =
            `<div class="om-alert om-alert-error">Failed to load order. Please try again.</div>`;
    });
}

function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    modal.classList.remove('open');
    document.body.style.overflow = '';
    currentOrderId = null;
}

// Close on Escape
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeOrderModal(); });

// ── Render ───────────────────────────────────────────────
function renderOrderModal(d) {
    document.getElementById('omSubtitle').textContent = d.order_date;

    let cancelBlock = '';
    if (d.status === 'Cancelled' && (d.cancelled_by || d.cancel_reason)) {
        cancelBlock = `
            <div style="margin-top:12px;padding:12px;background:#fef2f2;border:1px solid #fee2e2;border-radius:10px;">
                <div style="font-weight:700;color:#ef4444;font-size:12px;margin-bottom:4px;">Cancellation Details</div>
                <div style="font-size:12px;color:#b91c1c;">
                    <b>By:</b> ${esc(d.cancelled_by)}<br>
                    <b>Reason:</b> ${esc(d.cancel_reason)}<br>
                    ${d.cancelled_at ? `<b>At:</b> ${esc(d.cancelled_at)}` : ''}
                </div>
            </div>`;
    }

    let revisionBlock = '';
    if (d.status === 'For Revision' && d.revision_reason) {
        revisionBlock = `
            <div style="margin-top:12px;padding:12px;background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;">
                <div style="font-weight:700;color:#2563eb;font-size:12px;margin-bottom:4px;">Revision Requested</div>
                <div style="font-size:12px;color:#1e40af;">
                    <b>Reason:</b> ${esc(d.revision_reason)}<br>
                    <b>Count:</b> ${d.revision_count}
                </div>
            </div>`;
    }

    let notesBlock = '';
    if (d.notes) {
        notesBlock = `
            <div class="om-notes">
                <div class="om-notes-title">📝 Customer Notes</div>
                <div class="om-notes-text">${esc(d.notes).replace(/\n/g,'<br>')}</div>
            </div>`;
    }

    let downBlock = '';
    if (d.downpayment_amount > 0) {
        downBlock = `
            <div class="om-row">
                <span class="om-label" style="color:#b45309;font-weight:600;">Mandatory Downpayment</span>
                <span class="om-value" style="color:#b45309;">PHP ${d.downpayment_amount.toFixed(2)}</span>
            </div>`;
    }

    let payRefBlock = '';
    if (d.payment_reference) {
        payRefBlock = `
            <div class="om-row">
                <span class="om-label">Payment Reference</span>
                <span class="om-value">${esc(d.payment_reference)}</span>
            </div>`;
    }

    // Status options
    const statusOptions = ['Pending','Pending Approval','To Pay','In Production','Ready for Pickup','Completed']
        .map(s => `<option value="${s}" ${d.status === s ? 'selected' : ''}>${s}</option>`)
        .join('');

    // Other customer orders
    let custOrdersHTML = '';
    if (d.customer_orders && d.customer_orders.length) {
        custOrdersHTML = `<div class="om-cust-orders">
            <div class="om-card-title" style="margin-top:14px;">Other Orders</div>
            ${d.customer_orders.map(co => `
                <div class="om-co-row">
                    <span>
                        <button onclick="openOrderModal(${co.order_id})" style="background:none;border:none;color:#10b981;font-weight:600;cursor:pointer;font-size:12.5px;">#${co.order_id}</button>
                        <span style="color:#6b7280;margin-left:4px;">${co.order_date}</span>
                    </span>
                    <span>${co.total_amount} ${statusBadge(co.status)}</span>
                </div>`).join('')}
        </div>`;
    }

    // Items
    let itemsHTML = '';
    d.items.forEach(item => {
        // Customization chips
        let chips = '';
        if (item.customization && Object.keys(item.customization).length) {
            chips = `<div class="om-custom-chips">` +
                Object.entries(item.customization)
                    .filter(([,v]) => v)
                    .map(([k,v]) => `<span class="om-chip">${esc(k.replace(/_/g,' '))}: ${esc(String(v))}</span>`)
                    .join('') +
                `</div>`;
        }
        // Design image
        let designHTML = '';
        if (item.has_design) {
            designHTML = `
                <div class="om-design-wrap">
                    <div style="font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">Customer Design</div>
                    <a href="${item.design_url}" target="_blank">
                        <img src="${item.design_url}" alt="Customer Design" class="om-design-img"
                             onerror="this.outerHTML='<span style=\'color:#6b7280;font-size:12px;\'>⚠️ Could not load image</span>'">
                    </a>
                    ${item.design_name ? `<div style="font-size:11px;color:#6b7280;margin-top:4px;">${esc(item.design_name)}</div>` : ''}
                    <div style="margin-top:6px;">
                        <a href="${item.design_url}" target="_blank" style="font-size:11px;color:#6366f1;font-weight:600;text-decoration:none;">↗ View Full Size</a>
                    </div>
                </div>`;
        } else {
            designHTML = `<div style="font-size:11px;color:#9ca3af;margin-top:6px;">📂 No design file</div>`;
        }

        itemsHTML += `
            <tr>
                <td>
                    <div style="font-weight:600;color:#1e293b;">${esc(item.product_name)}</div>
                    ${chips}
                    ${designHTML}
                </td>
                <td style="text-align:center;">${item.quantity}</td>
                <td>PHP ${item.unit_price.toFixed(2)}</td>
                <td style="font-weight:700;">PHP ${item.subtotal.toFixed(2)}</td>
            </tr>`;
    });

    document.getElementById('omBody').innerHTML = `
        <div id="omFlash"></div>

        <div class="om-grid">
            <!-- Order Info -->
            <div class="om-card">
                <div class="om-card-title">Order Information</div>
                <div class="om-row">
                    <span class="om-label">Order Date</span>
                    <span class="om-value">${esc(d.order_date)}</span>
                </div>
                <div class="om-row">
                    <span class="om-label">Total Amount</span>
                    <span class="om-value" style="color:#6366f1;font-size:15px;">${esc(d.total_amount)}</span>
                </div>
                <div class="om-row">
                    <span class="om-label">Current Status</span>
                    <span class="om-value">${statusBadge(d.status)}</span>
                </div>
                <div class="om-row">
                    <span class="om-label">Payment Status</span>
                    <span class="om-value">${statusBadge(d.payment_status)}</span>
                </div>
                ${downBlock}
                ${payRefBlock}
                ${notesBlock}
                ${cancelBlock}
                ${revisionBlock}

                <!-- Update Status Form -->
                <div class="om-status-form">
                    <div class="om-status-form-title">⚙️ Order Management</div>
                    <div class="om-status-row">
                        <select id="omStatusSelect" class="input-field">
                            ${statusOptions}
                        </select>
                        <button class="btn-primary" onclick="updateOrderStatus(${d.order_id}, '${esc(d.csrf_token)}')">
                            Update Status
                        </button>
                    </div>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:20px;">

                <!-- Customer Info -->
                <div class="om-card">
                    <div class="om-card-title">Customer Information</div>
                <div class="om-cust-header">
                    <div class="om-avatar">${esc(d.cust_initial)}</div>
                    <div>
                        <div style="font-weight:700;font-size:15px;color:#1e293b;">${esc(d.cust_name)}</div>
                        <div style="font-size:12px;color:#9ca3af;">Customer</div>
                    </div>
                </div>
                <div class="om-row">
                    <span class="om-label">Email</span>
                    <span class="om-value">${esc(d.cust_email)}</span>
                </div>
                <div class="om-row">
                    <span class="om-label">Contact Number</span>
                    <span class="om-value">${esc(d.cust_phone)}</span>
                </div>
                ${custOrdersHTML}
            </div>
        </div>

        <!-- Items -->
        <div class="om-items-section">
            <div class="om-items-title">Order Items (${d.items.length})</div>
            <div style="overflow-x:auto;">
                <table class="om-items-table">
                    <thead>
                        <tr>
                            <th>Product & Design</th>
                            <th style="text-align:center;">Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHTML}</tbody>
                    <tfoot>
                        <tr class="om-items-total">
                            <td colspan="3" style="text-align:right;font-size:14px;">Total</td>
                            <td style="color:#6366f1;font-size:16px;">${esc(d.total_amount)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    `;
}

// ── Update status via AJAX ───────────────────────────────
function updateOrderStatus(orderId, csrfToken) {
    const newStatus = document.getElementById('omStatusSelect').value;
    const flash = document.getElementById('omFlash');
    if (!flash) return;
    flash.innerHTML = '';

    const formData = new FormData();
    formData.append('update_status', '1');
    formData.append('order_id', orderId);
    formData.append('status', newStatus);
    formData.append('csrf_token', csrfToken);

    fetch('/printflow/staff/orders.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            flash.innerHTML = `<div class="om-alert om-alert-success" id="omSuccessMsg" style="font-weight:700; border: 2px solid #bbf7d0;">✅ Status successfully updated to "${res.new_status}"!</div>`;
            
            // Update the status badge in the table row immediately
            const badge = document.querySelector(`button[onclick="openOrderModal(${orderId})"]`)
                ?.closest('tr')?.querySelector('td:nth-child(5)');
            if (badge) {
                badge.innerHTML = statusBadge(res.new_status);
                // Visual highlight effect
                badge.style.transition = 'background 0.3s, transform 0.3s';
                badge.style.background = '#d1fae5';
                badge.style.transform = 'scale(1.1)';
                setTimeout(() => {
                    badge.style.background = 'transparent';
                    badge.style.transform = 'scale(1)';
                }, 1500);
            }

            // Refresh modal data AFTER a delay so the message can be read
            setTimeout(() => {
                fetch(`/printflow/staff/get_order_data.php?id=${orderId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    renderOrderModal(data);
                    // Re-inject the success message since renderOrderModal clears the flash div
                    const newFlash = document.getElementById('omFlash');
                    if (newFlash) {
                        newFlash.innerHTML = `<div class="om-alert om-alert-success" style="font-weight:700; border: 2px solid #bbf7d0;">✅ Status updated to "${data.status}"!</div>`;
                    }
                });
            }, 1200);
        } else {
            flash.innerHTML = `<div class="om-alert om-alert-error">❌ Failed to update status: ${res.error || 'Please try again.'}</div>`;
        }
    })
    .catch(() => {
        flash.innerHTML = `<div class="om-alert om-alert-error">❌ Network error. Please try again.</div>`;
    });
}



function esc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}
</script>

</body>
</html>
