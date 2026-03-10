<?php
/**
 * Customer Notifications Page
 * PrintFlow - Printing Shop PWA
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('Customer');

$customer_id = get_user_id();

// Mark notification as read
if (isset($_GET['mark_read'])) {
    $notification_id = (int)$_GET['mark_read'];
    db_execute("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND customer_id = ?", 'ii', [$notification_id, $customer_id]);
    $back_filter = isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '';
    redirect('/printflow/customer/notifications.php' . $back_filter);
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    db_execute("UPDATE notifications SET is_read = 1 WHERE customer_id = ? AND is_read = 0", 'i', [$customer_id]);
    redirect('/printflow/customer/notifications.php');
}

// Get all notifications
$notifications = db_query("SELECT * FROM notifications WHERE customer_id = ? ORDER BY created_at DESC LIMIT 100", 'i', [$customer_id]);

$page_title = 'Notifications - PrintFlow';
$use_customer_css = true;
require_once __DIR__ . '/../includes/header.php';
?>

<link rel="stylesheet" href="/printflow/public/assets/css/chat.css">

<style>
    /* Filter Tabs */
    .notif-filter-bar {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    .notif-tab {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 7px 15px;
        border-radius: 99px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        border: 1.5px solid #e5e7eb;
        background: white;
        color: #6b7280;
        text-decoration: none;
        transition: all 0.18s;
        white-space: nowrap;
    }
    .notif-tab:hover { border-color: #6366f1; color: #6366f1; background: #f0f0ff; }
    .notif-tab.active { background: #6366f1; color: white; border-color: #6366f1; box-shadow: 0 2px 8px rgba(99,102,241,0.25); }
    .notif-tab .tab-cnt {
        background: rgba(255,255,255,0.3);
        font-size: 10px; font-weight: 700;
        padding: 1px 6px; border-radius: 99px; min-width: 18px; text-align: center;
    }
    .notif-tab:not(.active) .tab-cnt { background: #f3f4f6; color: #9ca3af; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.7} }
</style>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-3xl font-bold text-gray-900">Notifications</h1>
            <?php if (!empty($notifications) && array_search(0, array_column($notifications, 'is_read')) !== false): ?>
                <a href="?mark_all_read=1" class="text-sm font-medium text-blue-600 hover:text-blue-700 bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm transition-all hover:shadow-md">
                    Mark all as read
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="card text-center py-12">
                <p class="text-gray-600">No notifications yet</p>
            </div>
        <?php else: ?>

            <?php
            // ── Filter definitions ─────────────────────────────────────────────
            $filters = [
                'all'        => ['label' => '📋 All',             'keywords' => []],
                'new_order'  => ['label' => '🆕 New Order',        'keywords' => ['new order', 'received from', 'placed successfully', 'order placed', 'order confirmed']],
                'pending'    => ['label' => '⏳ Pending',          'keywords' => ['pending']],
                'to_pay'     => ['label' => '💳 To Pay',           'keywords' => ['to pay', 'topay', 'payment due', 'balance due']],
                'payment'    => ['label' => '✅ Payment',          'keywords' => ['payment', 'downpayment', 'paid', 'gcash', 'cash', 'rejected']],
                'production' => ['label' => '⚙️ In Production',    'keywords' => ['in production', 'processing', 'production', 'printing']],
                'revision'   => ['label' => '✏️ Revision',         'keywords' => ['revision', 'for revision', 'revise']],
                'pickup'     => ['label' => '📦 Ready for Pickup', 'keywords' => ['ready for pickup', 'ready to pickup', 'ready']],
                'completed'  => ['label' => '✔️ Completed',        'keywords' => ['completed', 'complete']],
                'cancelled'  => ['label' => '❌ Cancelled',        'keywords' => ['cancelled', 'cancel']],
                'message'    => ['label' => '💬 New Message',       'keywords' => ['message', 'chat', 'replied']],
            ];

            // Count per tab
            $counts = [];
            foreach ($filters as $key => $f) {
                if ($key === 'all') { $counts[$key] = count($notifications); continue; }
                $counts[$key] = 0;
                foreach ($notifications as $n) {
                    $ml = strtolower($n['message']);
                    foreach ($f['keywords'] as $kw) {
                        if (strpos($ml, $kw) !== false) { $counts[$key]++; break; }
                    }
                }
            }

            ?>
            <!-- Filter Bar Removed -->


            <!-- Notification cards -->
            <div class="space-y-3" id="notif-container">
                <?php
                $shown = 0;
                foreach ($notifications as $notif):
                    $msg_lower = strtolower($notif['message']);


                    $shown++;

                    // Get unread chat count for this order
                    $chat_unread = 0;
                    if (!empty($notif['data_id']) && $notif['type'] === 'Order') {
                        $chat_unread = get_unread_chat_count($notif['data_id'], 'Customer');
                    }
                ?>
                    <div class="card <?php echo $notif['is_read'] ? 'bg-white' : 'bg-blue-50 border-l-4 border-blue-500'; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                                    <?php if (!$notif['is_read']): ?>
                                        <span class="badge bg-blue-500 text-white text-xs">NEW</span>
                                    <?php endif; ?>
                                    <?php if ($chat_unread > 0): ?>
                                        <span style="display:inline-flex; align-items:center; gap:4px; background:#ef4444; color:white; font-size:11px; font-weight:700; padding:2px 8px; border-radius:99px; animation:pulse 2s infinite;">
                                            💬 <?php echo $chat_unread; ?> new message<?php echo $chat_unread > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <p class="text-sm text-gray-600 mb-3"><?php echo format_datetime($notif['created_at']); ?></p>
                                
                                <?php if (!empty($notif['data_id']) && $notif['type'] === 'Order'): ?>
                                    <div class="flex gap-2" style="flex-wrap:wrap;">
                                        <?php if (strpos($notif['message'], 'To Pay') !== false || strpos($notif['message'], 'rejected') !== false): ?>
                                            <a href="/printflow/customer/order_details.php?id=<?php echo $notif['data_id']; ?>&pay=1" class="text-xs bg-indigo-600 text-white px-3 py-1.5 rounded-md font-semibold hover:bg-indigo-700 transition-colors inline-flex items-center gap-1">
                                                <span>💳</span> Pay Now
                                            </a>
                                        <?php else: ?>
                                            <a href="/printflow/customer/order_details.php?id=<?php echo $notif['data_id']; ?>" class="text-xs bg-gray-100 text-gray-700 px-3 py-1.5 rounded-md font-semibold hover:bg-gray-200 transition-colors inline-flex items-center gap-1">
                                                <span>📂</span> View Order
                                            </a>
                                        <?php endif; ?>
                                        <!-- Chat Button -->
                                        <button
                                            onclick="openOrderChat(<?php echo (int)$notif['data_id']; ?>, 'PrintFlow Support')"
                                            style="display:inline-flex; align-items:center; gap:5px; background:<?php echo $chat_unread > 0 ? '#ef4444' : '#4f46e5'; ?>; color:white; font-size:12px; font-weight:700; padding:6px 12px; border-radius:8px; border:none; cursor:pointer; transition:opacity 0.2s;"
                                            onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                                            💬 <?php echo $chat_unread > 0 ? 'Reply (' . $chat_unread . ')' : 'Message Shop'; ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <a href="?mark_read=<?php echo $notif['notification_id']; ?>" class="text-sm text-blue-600 hover:text-blue-700 ml-4 whitespace-nowrap">
                                    Mark as Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>


            </div>

        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/order_chat.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
