<?php
/**
 * Customer Orders Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

$customer_id = get_user_id();

// TikTok style tabs
$active_tab = $_GET['tab'] ?? 'all';

// Tab mappings to exact statuses
$tab_status_map = [
    'pending' => ['Pending', 'Pending Approval', 'Pending Review', 'For Revision'],
    'topay' => ['To Pay'],
    'production' => ['In Production', 'Processing', 'Printing'], // include legacy for safety
    'pickup' => ['Ready for Pickup'],
    'completed' => ['Completed']
];

// Build query
$sql = "SELECT * FROM orders WHERE customer_id = ?";
$count_sql = "SELECT COUNT(*) as total FROM orders WHERE customer_id = ?";
$params = [$customer_id];
$count_params = [$customer_id]; // Need this for the count query
$types = 'i';
$count_types = 'i'; // Need this for the count query

if ($active_tab !== 'all' && isset($tab_status_map[$active_tab])) {
    $statuses = $tab_status_map[$active_tab];
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));
    
    $sql .= " AND status IN ($placeholders)";
    $count_sql .= " AND status IN ($placeholders)";
    
    foreach ($statuses as $s) {
        $params[] = $s;
        $count_params[] = $s; // Also add to count params
        $types .= 's';
        $count_types .= 's'; // Also add to count types
    }
}

// Pagination settings (restored)
$items_per_page = 10;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($current_page - 1) * $items_per_page;

$total_result = db_query($count_sql, $count_types, $count_params);
$total_items = $total_result[0]['total'] ?? 0;
$total_pages = ceil($total_items / $items_per_page);

$sql .= " ORDER BY order_date DESC LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= 'ii';

$orders = db_query($sql, $types, $params);

$page_title = 'My Orders - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* TikTok Style Orders Nav */
.tt-tabs-wrapper {
    position: sticky; top: 72px; z-index: 40;
    background: #fff; border-bottom: 5px solid #f3f4f6;
    margin: -2rem -1rem 1.5rem -1rem; padding: 0 1rem;
    overflow-x: auto; white-space: nowrap; scrollbar-width: none;
}
.tt-tabs-wrapper::-webkit-scrollbar { display: none; }
.tt-tabs {
    display: flex; gap: 1.5rem; padding: 0.5rem 0 0 0;
}
.tt-tab {
    padding: 0.75rem 0.25rem; font-size: 0.9375rem; color: #64748b; font-weight: 500;
    border-bottom: 2px solid transparent; text-decoration: none; position: relative;
    transition: color 0.2s;
}
.tt-tab:hover { color: #1e293b; }
.tt-tab.active {
    color: #111827; font-weight: 700;
}
.tt-tab.active::after {
    content: ''; position: absolute; bottom: -2px; left: 0; right: 0;
    height: 2px; background: #000; border-radius: 2px 2px 0 0;
}

/* TikTok Style Empty State */
.tt-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 4rem 1rem; text-align: center;
}
.tt-empty-icon {
    width: 120px; height: 120px; margin-bottom: 1rem; opacity: 0.7;
}
.tt-empty-title {
    font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 0.25rem;
}
.tt-empty-sub {
    font-size: 0.9rem; color: #6b7280; font-weight: 500;
}

@media (min-width: 768px) {
    .tt-tabs-wrapper { margin: -1rem 0 2rem 0; padding: 0; border-bottom: 1px solid #e5e7eb; }
}
</style>

<div class="min-h-screen py-4 md:py-8 bg-gray-50 md:bg-transparent">
    <div class="container mx-auto" style="max-width:800px;">

        <!-- TikTok Tabs -->
        <div class="tt-tabs-wrapper">
            <div class="tt-tabs">
                <a href="?tab=all" class="tt-tab <?php echo $active_tab === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?tab=pending" class="tt-tab <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?tab=topay" class="tt-tab <?php echo $active_tab === 'topay' ? 'active' : ''; ?>">To pay</a>
                <a href="?tab=production" class="tt-tab <?php echo $active_tab === 'production' ? 'active' : ''; ?>">In Production</a>
                <a href="?tab=pickup" class="tt-tab <?php echo $active_tab === 'pickup' ? 'active' : ''; ?>">Ready for pickup</a>
                <a href="?tab=completed" class="tt-tab <?php echo $active_tab === 'completed' ? 'active' : ''; ?>">Completed</a>
            </div>
        </div>

        <!-- Orders List -->
        <?php if (empty($orders)): ?>
            <div class="tt-empty">
                <!-- SVG Shopping Bag Empty State mimicking TikTok -->
                <svg class="tt-empty-icon" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M70 70 L60 140 L130 140 L140 70 Z" stroke="#9ca3af" stroke-width="4" stroke-linejoin="round"/>
                    <path d="M85 70 V55 C85 45 115 45 115 55 V70" stroke="#9ca3af" stroke-width="4" stroke-linecap="round"/>
                    <path d="M85 90 C85 105 115 105 115 90" stroke="#9ca3af" stroke-width="4" stroke-linecap="round"/>
                    <path d="M50 40 L65 55" stroke="#9ca3af" stroke-width="4" stroke-linecap="round"/>
                    <path d="M120 30 L135 45 M135 30 L120 45" stroke="#9ca3af" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="140" cy="50" r="4" fill="#9ca3af"/>
                    <circle cx="55" cy="80" r="3" fill="#9ca3af"/>
                    <path d="M145 90 C155 90 155 100 145 100 C135 100 135 110 145 110" stroke="#9ca3af" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M45 100 C35 100 35 110 45 110 C55 110 55 120 45 120" stroke="#9ca3af" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="tt-empty-title">No orders yet</div>
                <div class="tt-empty-sub">Start shopping!</div>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $index => $order): ?>
                <div class="ct-order-card" id="order-card-<?php echo $order['order_id']; ?>">
                    <div class="ct-order-header" style="margin-bottom: 0; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                        <div style="flex: 1;">
                            <p class="ct-order-id" style="font-size: 1.1rem; color: #1e293b;">Order #<?php echo $order['order_id']; ?></p>
                            <p class="ct-order-date" style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;"><?php echo format_datetime($order['order_date']); ?></p>
                        </div>
                        <div style="text-align:right;">
                            <p class="ct-order-amount" style="font-size: 1.25rem; font-weight: 800; color: #4f46e5;"><?php echo format_currency($order['total_amount']); ?></p>
                            <div style="margin-top:4px;"><?php echo status_badge($order['status'], 'order'); ?></div>
                        </div>
                    </div>

                    <button class="ct-toggle-btn" onclick="toggleOrderDetails(<?php echo $order['order_id']; ?>)">
                        <span>See More</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="ct-order-meta hidden" id="order-meta-<?php echo $order['order_id']; ?>">
                        <div>
                            <p class="ct-order-meta-label">Payment Status</p>
                            <p class="ct-order-meta-value"><?php echo status_badge($order['payment_status'], 'payment'); ?></p>
                        </div>
                        <div>
                            <p class="ct-order-meta-label">Estimated Completion</p>
                            <p class="ct-order-meta-value"><?php echo ($order['estimated_completion'] ?? null) ? format_date($order['estimated_completion']) : 'TBD'; ?></p>
                        </div>
                        <div style="display:flex; align-items:center;">
                            <button
                                onclick="openItemsModal(<?php echo $order['order_id']; ?>)"
                                class="ct-view-link"
                                style="background:none;border:none;cursor:pointer;padding:0;font-family:inherit;"
                            >View Details →</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <div class="mt-8">
                <?php echo get_pagination_links($current_page, $total_pages, ['tab' => $active_tab]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle individual order details
function toggleOrderDetails(orderId) {
    const meta = document.getElementById('order-meta-' + orderId);
    const btn = document.querySelector(`#order-card-${orderId} .ct-toggle-btn`);
    const span = btn.querySelector('span');
    const svg = btn.querySelector('svg');
    
    if (meta.classList.contains('hidden')) {
        meta.classList.remove('hidden');
        span.textContent = 'See Less';
        svg.style.transform = 'rotate(180deg)';
    } else {
        meta.classList.add('hidden');
        span.textContent = 'See More';
        svg.style.transform = 'rotate(0deg)';
    }
}

// Trigger success modal if success message exists
window.addEventListener('DOMContentLoaded', () => {
    <?php if (isset($_SESSION['success'])): 
        $msg = $_SESSION['success'];
        unset($_SESSION['success']);
    ?>
    showSuccessModal(
        '✅ Action Completed',
        '<?php echo addslashes($msg); ?>',
        '#', // primary doesn't matter much here, maybe just refresh
        'dashboard.php',
        'Close',
        'Go to Dashboard'
    );
    <?php endif; ?>
});
</script>

<!-- ══ Order Items Modal ══ -->
<style>
/* Base modal */
#itemsModal {
    position:fixed; inset:0; z-index:9999;
    display:flex; align-items:center; justify-content:center;
    padding:16px;
    opacity:0; pointer-events:none;
    transition:opacity 0.25s ease;
}
#itemsModal.open { opacity:1; pointer-events:all; }

.im-backdrop {
    position:absolute; inset:0;
    background:rgba(15,23,42,0.55);
    backdrop-filter:blur(4px);
}
.im-panel {
    position:relative; z-index:1;
    background:#fff; border-radius:20px;
    width:100%;
    max-width:560px;      /* compact: items only */
    max-height:88vh; overflow-y:auto;
    box-shadow:0 25px 60px rgba(0,0,0,0.2);
    opacity:0; transform:translateY(22px) scale(0.97);
    transition:
        max-width 0.4s cubic-bezier(.34,1.2,.64,1),
        transform 0.32s cubic-bezier(.34,1.56,.64,1),
        opacity 0.25s ease;
}
#itemsModal.open .im-panel { opacity:1; transform:translateY(0) scale(1); }
/* Expanded state – wider panel */
#itemsModal.expanded .im-panel { max-width:780px; }

.im-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:20px 24px 16px;
    border-bottom:1px solid #f1f5f9;
    position:sticky; top:0; background:#fff;
    border-radius:20px 20px 0 0; z-index:2;
    gap:12px;
}
.im-title { font-size:1.1rem; font-weight:800; color:#1e293b; flex:1; min-width:0; }
.im-subtitle { font-size:0.75rem; color:#94a3b8; margin-top:2px; }

.im-close {
    width:32px; height:32px; border-radius:50%; flex-shrink:0;
    border:none; background:#f1f5f9; color:#64748b;
    cursor:pointer; font-size:1rem;
    display:flex; align-items:center; justify-content:center;
    transition:background 0.15s;
}
.im-close:hover { background:#e2e8f0; }

.im-body { padding:20px 24px 24px; }

/* Items table */
.im-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.im-table th {
    text-align:left; padding:8px 10px;
    font-size:0.65rem; font-weight:700; text-transform:uppercase;
    letter-spacing:0.06em; color:#94a3b8;
    border-bottom:2px solid #e2e8f0;
}
.im-table td { padding:11px 10px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.im-table tbody tr:last-child td { border-bottom:none; }
.im-total-row { border-top:2px solid #e2e8f0 !important; font-weight:800; }

/* Expand section */
.im-expand-btn {
    display:flex; align-items:center; justify-content:center; gap:6px;
    width:100%; margin-top:16px; padding:10px;
    background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
    font-size:13px; font-weight:700; color:#6366f1;
    cursor:pointer; transition:background 0.15s, border-color 0.15s;
}
.im-expand-btn:hover { background:#eef2ff; border-color:#c7d2fe; }
.im-expand-icon { transition:transform 0.3s ease; font-size:11px; }
.im-expand-btn.active .im-expand-icon { transform:rotate(180deg); }

/* Full details section – slide open */
.im-full-details {
    overflow:hidden;
    max-height:0;
    transition:max-height 0.5s cubic-bezier(0.4,0,0.2,1), opacity 0.3s ease;
    opacity:0;
}
.im-full-details.open { max-height:2000px; opacity:1; }
.im-full-details-inner { padding-top:20px; border-top:1px solid #f1f5f9; margin-top:18px; }

/* Info grid */
.im-info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:16px; }
@media (max-width:500px) { .im-info-grid { grid-template-columns:1fr; } }
.im-info-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:14px; }
.im-info-label { font-size:0.7rem; color:#94a3b8; margin-bottom:4px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
.im-info-value { font-size:13.5px; font-weight:700; color:#1e293b; }

/* Notes box */
.im-notes {
    margin-bottom:16px; padding:14px 16px;
    background:linear-gradient(135deg,#fffbeb,#fef3c7);
    border:1px solid #fde68a; border-radius:12px;
}
.im-notes-title { font-size:12px; font-weight:800; color:#92400e; margin-bottom:6px; }

/* Design thumb */
.im-design-thumb { max-width:100px; border-radius:8px; border:2px solid #e2e8f0; display:block; margin-top:6px; cursor:zoom-in; transition:transform 0.2s; }
.im-design-thumb:hover { transform:scale(1.05); }

/* Custom chips */
.im-chips { display:flex; flex-wrap:wrap; gap:5px; margin-top:5px; }
.im-chip { background:#e0e7ff; color:#4338ca; border-radius:99px; padding:2px 9px; font-size:11px; font-weight:600; }

/* Status badges */
.im-badge { display:inline-block; padding:2px 10px; border-radius:99px; font-size:11px; font-weight:700; }
.im-badge-green { background:#d1fae5; color:#065f46; }
.im-badge-yellow { background:#fef3c7; color:#92400e; }
.im-badge-red { background:#fee2e2; color:#991b1b; }
.im-badge-blue { background:#dbeafe; color:#1e40af; }
.im-badge-gray { background:#f3f4f6; color:#374151; }
.im-badge-purple { background:#ede9fe; color:#5b21b6; }

/* Loader */
.im-loader { text-align:center; padding:48px 0; }
.im-spinner {
    width:36px; height:36px; border-radius:50%;
    border:3px solid #e2e8f0; border-top-color:#6366f1;
    animation:im-spin 0.7s linear infinite; margin:0 auto 10px;
}
@keyframes im-spin { to { transform:rotate(360deg); } }
</style>

<div id="itemsModal" role="dialog" aria-modal="true">
    <div class="im-backdrop" onclick="closeItemsModal()"></div>
    <div class="im-panel">
        <div class="im-header">
            <div>
                <div class="im-title" id="imTitle">Order Items</div>
                <div class="im-subtitle" id="imSubtitle"></div>
            </div>
            <button class="im-close" onclick="closeItemsModal()">✕</button>
        </div>
        <div class="im-body" id="imBody">
            <div class="im-loader"><div class="im-spinner"></div></div>
        </div>
    </div>
</div>

<script>
let imExpanded = false;

function imBadge(val) {
    const m = {
        'Completed':'im-badge-green','Pending':'im-badge-yellow',
        'Pending Review':'im-badge-yellow','Processing':'im-badge-blue',
        'In Production':'im-badge-blue','Printing':'im-badge-blue',
        'Ready for Pickup':'im-badge-purple','Cancelled':'im-badge-red',
        'For Revision':'im-badge-blue','Paid':'im-badge-green',
        'Unpaid':'im-badge-gray','Partial':'im-badge-yellow',
    };
    return `<span class="im-badge ${m[val]||'im-badge-gray'}">${escIM(val)}</span>`;
}

function openItemsModal(orderId) {
    imExpanded = false;
    const modal = document.getElementById('itemsModal');
    modal.classList.remove('expanded');
    document.getElementById('imTitle').textContent = `Order #${orderId}`;
    document.getElementById('imSubtitle').textContent = '';
    document.getElementById('imBody').innerHTML =
        `<div class="im-loader"><div class="im-spinner"></div><div style="color:#94a3b8;font-size:13px;margin-top:6px;">Loading…</div></div>`;
    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    fetch(`/printflow/customer/get_order_items.php?id=${orderId}`)
    .then(r => r.json())
    .then(data => {
        if (data.error) {
            document.getElementById('imBody').innerHTML =
                `<p style="color:#ef4444;font-size:13px;">${escIM(data.error)}</p>`;
            return;
        }

        document.getElementById('imSubtitle').textContent = data.order_date;

        // ── Items table rows ──────────────────────────────────
        const rows = data.items.map(item => {
            let chips = '';
            if (item.customization && Object.keys(item.customization).length) {
                chips = `<div class="im-chips">` +
                    Object.entries(item.customization)
                        .filter(([,v]) => v)
                        .map(([k,v]) => `<span class="im-chip">${escIM(k.replace(/_/g,' '))}: ${escIM(String(v))}</span>`)
                        .join('') + `</div>`;
            }
            const design = item.has_design
                ? `<a href="${escIM(item.design_url)}" target="_blank">
                      <img src="${escIM(item.design_url)}" class="im-design-thumb"
                           alt="Design"
                           onerror="this.outerHTML='<span style=\\'color:#9ca3af;font-size:11px;\\'>⚠️ No preview</span>'">
                   </a>`
                : `<div style="font-size:11px;color:#9ca3af;margin-top:4px;">No design file</div>`;

            return `<tr>
                <td>
                    <div style="font-weight:700;color:#1e293b;">${escIM(item.product_name)}</div>
                    ${item.category ? `<div style="font-size:11px;color:#9ca3af;">${escIM(item.category)}</div>` : ''}
                    ${chips}
                    ${design}
                </td>
                <td style="text-align:center;">${item.quantity}</td>
                <td>${escIM(item.unit_price)}</td>
                <td style="font-weight:700;color:#4f46e5;">${escIM(item.subtotal)}</td>
            </tr>`;
        }).join('');

        // ── Full details (hidden initially) ──────────────────
        let notesHTML = '';
        if (data.notes) {
            notesHTML = `<div class="im-notes">
                <div class="im-notes-title">📝 Your Order Notes</div>
                <div style="font-size:13px;color:#b45309;">${escIM(data.notes).replace(/\n/g,'<br>')}</div>
            </div>`;
        }

        let cancelHTML = '';
        if (data.status === 'Cancelled' && (data.cancelled_by || data.cancel_reason)) {
            cancelHTML = `<div style="margin-top:12px;padding:12px;background:#fef2f2;border:1px solid #fee2e2;border-radius:10px;font-size:12px;color:#b91c1c;">
                <b>Cancelled by:</b> ${escIM(data.cancelled_by)}<br>
                <b>Reason:</b> ${escIM(data.cancel_reason)}
                ${data.cancelled_at ? `<br><b>Date:</b> ${escIM(data.cancelled_at)}` : ''}
            </div>`;
        }

        let revisionHTML = '';
        if (data.status === 'For Revision' && data.revision_reason) {
            revisionHTML = `<div style="margin-top:12px;padding:12px;background:#eff6ff;border:1px solid #dbeafe;border-radius:10px;font-size:12px;color:#1e40af;">
                <b>Revision needed:</b> ${escIM(data.revision_reason)}
            </div>`;
        }

        document.getElementById('imBody').innerHTML = `
            <table class="im-table">
                <thead><tr>
                    <th>Product</th>
                    <th style="text-align:center;">Qty</th>
                    <th>Unit Price</th>
                    <th>Subtotal</th>
                </tr></thead>
                <tbody>${rows}</tbody>
                <tfoot><tr>
                    <td colspan="3" style="text-align:right;padding:12px 10px;" class="im-total-row">Total</td>
                    <td style="padding:12px 10px;color:#4f46e5;font-size:15px;" class="im-total-row">${escIM(data.total_amount)}</td>
                </tr></tfoot>
            </table>

            <!-- Expand button -->
            <button class="im-expand-btn" id="imExpandBtn" onclick="toggleFullDetails()">
                <span>View Full Order Details</span>
                <span class="im-expand-icon">▼</span>
            </button>

            <!-- Full details panel (hidden) -->
            <div class="im-full-details" id="imFullDetails">
                <div class="im-full-details-inner">
                    ${notesHTML}
                    <div class="im-info-grid">
                        <div class="im-info-card">
                            <div class="im-info-label">Order Status</div>
                            <div class="im-info-value">${imBadge(data.status)}</div>
                        </div>
                        <div class="im-info-card">
                            <div class="im-info-label">Payment</div>
                            <div class="im-info-value">${imBadge(data.payment_status)}</div>
                        </div>
                        <div class="im-info-card">
                            <div class="im-info-label">Estimated Completion</div>
                            <div class="im-info-value">${escIM(data.estimated_comp)}</div>
                        </div>
                        <div class="im-info-card">
                            <div class="im-info-label">Date Placed</div>
                            <div class="im-info-value">${escIM(data.order_date)}</div>
                        </div>
                    </div>
                    ${cancelHTML}
                    ${revisionHTML}
                </div>
            </div>`;
    })
    .catch(() => {
        document.getElementById('imBody').innerHTML =
            `<p style="color:#ef4444;font-size:13px;">Failed to load. Please try again.</p>`;
    });
}

function toggleFullDetails() {
    imExpanded = !imExpanded;
    const modal = document.getElementById('itemsModal');
    const panel = document.getElementById('imFullDetails');
    const btn   = document.getElementById('imExpandBtn');

    panel.classList.toggle('open', imExpanded);
    btn.classList.toggle('active', imExpanded);
    modal.classList.toggle('expanded', imExpanded);

    const spanText = btn.querySelector('span');
    spanText.textContent = imExpanded ? 'Hide Order Details' : 'View Full Order Details';
}

function closeItemsModal() {
    const modal = document.getElementById('itemsModal');
    modal.classList.remove('open','expanded');
    document.body.style.overflow = '';
    imExpanded = false;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeItemsModal(); });

function escIM(str) {
    return String(str || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

