<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notify.php';

$user = require_role('student', '../index.php');

// ---- Cancel action ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $apptId = (int) ($_POST['appointment_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM appointments WHERE id = ? AND student_id = ?');
    $stmt->execute([$apptId, $user['id']]);
    $appt = $stmt->fetch();
    if ($appt && in_array($appt['status'], ['pending', 'confirmed'], true)) {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE appointments SET status = "cancelled" WHERE id = ?')->execute([$apptId]);
        $pdo->prepare('UPDATE schedule_slots SET booked_count = GREATEST(booked_count - 1, 0) WHERE id = ?')->execute([$appt['slot_id']]);
        push_notification($pdo, $user['id'], 'You cancelled appointment ' . $appt['queue_code'] . '.', $apptId);
        $pdo->commit();
        header('Location: history.php?cancelled=1');
        exit;
    }
}

// ---- Filter ----
$statusFilter = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'confirmed', 'done', 'cancelled', 'no_show'];
if (!in_array($statusFilter, $allowed, true)) $statusFilter = 'all';

$sql = "SELECT a.*, s.name AS service_name, s.office, sl.slot_date, sl.slot_time
  FROM appointments a
  JOIN services s ON s.id = a.service_id
  JOIN schedule_slots sl ON sl.id = a.slot_id
  WHERE a.student_id = ?";
$params = [$user['id']];
if ($statusFilter !== 'all') {
    $sql .= ' AND a.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY sl.slot_date DESC, sl.slot_time DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$unread = unread_count($pdo, $user['id']);
$pageTitle = 'Appointment History';
$active = 'history';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1>Appointment History</h1>
    <p class="sub">Every appointment you've made, past and upcoming.</p>
  </div>
  <a href="book.php" class="btn btn-primary">+ Book an Appointment</a>
</div>

<?php if (isset($_GET['booked'])): ?>
  <div class="alert alert-success">Booked! Your queue code is <strong><?php echo htmlspecialchars($_GET['booked']); ?></strong>. Check Notifications for the confirmation.</div>
<?php endif; ?>
<?php if (isset($_GET['cancelled'])): ?>
  <div class="alert alert-info">Appointment cancelled.</div>
<?php endif; ?>

<div class="card">
  <div class="card-head">
    <div><h2>All Appointments</h2></div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <?php foreach (['all' => 'All', 'confirmed' => 'Confirmed', 'done' => 'Done', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $key => $label): ?>
        <a href="?status=<?php echo $key; ?>" class="btn btn-sm <?php echo $statusFilter === $key ? 'btn-dark' : 'btn-outline'; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Queue Code</th><th>Office</th><th>Service</th><th>Date &amp; Time</th><th>Status</th><th>Booked On</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="empty-row">No appointments found for this filter.</td></tr>
        <?php else: foreach ($rows as $a):
          $isUpcoming = in_array($a['status'], ['pending', 'confirmed'], true) && strtotime($a['slot_date']) >= strtotime(date('Y-m-d'));
        ?>
          <tr>
            <td class="mono"><?php echo htmlspecialchars($a['queue_code'] ?? '—'); ?></td>
            <td style="text-transform:capitalize;"><?php echo htmlspecialchars($a['office']); ?></td>
            <td><?php echo htmlspecialchars($a['service_name']); ?></td>
            <td><?php echo date('M j, Y', strtotime($a['slot_date'])) . ' · ' . date('g:i A', strtotime($a['slot_time'])); ?></td>
            <td><span class="badge badge-<?php echo $a['status']; ?>"><?php echo str_replace('_', ' ', $a['status']); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($a['created_at'])); ?></td>
            <td>
              <?php if ($isUpcoming): ?>
                <form method="post" onsubmit="return confirm('Cancel this appointment?');">
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
