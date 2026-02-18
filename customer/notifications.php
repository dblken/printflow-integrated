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
    redirect('/printflow/customer/notifications.php');
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

<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between mb-6">
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
            <div class="space-y-3" id="notif-container">
                <?php foreach ($notifications as $index => $notif): ?>
                    <div class="card <?php echo $notif['is_read'] ? 'bg-white' : 'bg-blue-50 border-l-4 border-blue-500'; ?> <?php echo $index >= 10 ? 'notif-hidden hidden' : ''; ?>">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge bg-blue-500 text-white text-xs mb-2">NEW</span>
                                <?php endif; ?>
                                <p class="font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <p class="text-sm text-gray-600"><?php echo format_datetime($notif['created_at']); ?></p>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <a href="?mark_read=<?php echo $notif['notification_id']; ?>" class="text-sm text-blue-600 hover:text-blue-700 ml-4">
                                    Mark as Read
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($notifications) > 10): ?>
                <div class="text-center mt-8">
                    <button id="toggle-notifs" class="px-6 py-2 bg-white border border-gray-200 rounded-full text-sm font-medium text-gray-700 shadow-sm hover:shadow-md transition-all">
                        See More
                    </button>
                </div>

                <script>
                    const toggleBtn = document.getElementById('toggle-notifs');
                    const hiddenNotifs = document.querySelectorAll('.notif-hidden');
                    let isExpanded = false;

                    toggleBtn.addEventListener('click', () => {
                        isExpanded = !isExpanded;
                        hiddenNotifs.forEach(el => {
                            if (isExpanded) {
                                el.classList.remove('hidden');
                            } else {
                                el.classList.add('hidden');
                            }
                        });
                        toggleBtn.textContent = isExpanded ? 'See Less' : 'See More';
                    });
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
