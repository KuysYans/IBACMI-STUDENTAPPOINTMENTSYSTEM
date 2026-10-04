<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notify.php';

$user = require_role('student', '../index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all') {
    mark_all_read($pdo, $user['id']);
    header('Location: notifications.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_one') {
    mark_one_read($pdo, $user['id'], (int) ($_POST['notif_id'] ?? 0));
    header('Location: notifications.php');
    exit;
}

$stmt = $pdo->prepare('SELECT n.*, a.queue_code FROM notifications n
  LEFT JOIN appointments a ON a.id = n.appointment_id
  WHERE n.user_id = ? ORDER BY n.created_at DESC');
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

$unread = unread_count($pdo, $user['id']);
$pageTitle = 'Notifications';
$active = 'notifications';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1>Notifications</h1>
    <p class="sub">Updates about your appointments — confirmations, reminders, and status changes.</p>
  </div>
  <?php if ($unread > 0): ?>
    <form method="post"><input type="hidden" name="action" value="mark_all">
      <button type="submit" class="btn btn-outline">Mark all as read</button>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <?php if (!$notifs): ?>
    <p class="empty-row">No notifications yet. Book an appointment and updates will show up here.</p>
  <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:2px;">
      <?php foreach ($notifs as $n): ?>
        <div style="display:flex; align-items:flex-start; gap:14px; padding:14px 6px; border-bottom:1px solid var(--smoke-100); <?php echo $n['is_read'] ? '' : 'background:#fff8e8;'; ?>">
          <span style="font-size:18px; margin-top:2px;"><?php echo $n['is_read'] ? '🔔' : '🟡'; ?></span>
          <div style="flex:1;">
            <p style="margin:0; font-size:13.8px; color:var(--ash-700); font-weight:<?php echo $n['is_read'] ? '500' : '700'; ?>;">
              <?php echo htmlspecialchars($n['message']); ?>
            </p>
            <span style="font-size:11.5px; color:var(--ash-500);"><?php echo date('M j, Y \a\t g:i A', strtotime($n['created_at'])); ?></span>
          </div>
          <?php if (!$n['is_read']): ?>
            <form method="post">
              <input type="hidden" name="action" value="mark_one">
              <input type="hidden" name="notif_id" value="<?php echo $n['id']; ?>">
              <button type="submit" class="btn btn-sm btn-outline">Mark read</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
