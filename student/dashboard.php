<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notify.php';

$user = require_role('student', '../index.php');

// ---- Cancel from the dashboard (same action used on history.php) ----
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
        header('Location: dashboard.php?cancelled=1');
        exit;
    }
}

// ---- Stats ----
$stmt = $pdo->prepare("SELECT
    SUM(CASE WHEN status IN ('pending','confirmed') AND slot_id IN (SELECT id FROM schedule_slots WHERE slot_date >= CURDATE()) THEN 1 ELSE 0 END) AS upcoming,
    SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS done,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
    COUNT(*) AS total
  FROM appointments WHERE student_id = ?");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();

// ---- Upcoming appointments ----
$stmt = $pdo->prepare("SELECT a.*, s.name AS service_name, s.office, sl.slot_date, sl.slot_time
  FROM appointments a
  JOIN services s ON s.id = a.service_id
  JOIN schedule_slots sl ON sl.id = a.slot_id
  WHERE a.student_id = ? AND a.status IN ('pending','confirmed') AND sl.slot_date >= CURDATE()
  ORDER BY sl.slot_date ASC, sl.slot_time ASC");
$stmt->execute([$user['id']]);
$upcoming = $stmt->fetchAll();

$unread = unread_count($pdo, $user['id']);
$pageTitle = 'Dashboard';
$active = 'dashboard';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1>Kumusta, <?php echo htmlspecialchars(explode(' ', $user['full_name'] ?? $user['email'])[0]); ?> 👋</h1>
    <p class="sub">Here's a quick look at your appointments.</p>
  </div>
  <a href="book.php" class="btn btn-primary">+ Book an Appointment</a>
</div>

<?php if (isset($_GET['welcome'])): ?>
  <div class="alert alert-success">Welcome to the IBA Appointment System! Your account is ready — book your first appointment below.</div>
<?php endif; ?>
<?php if (isset($_GET['cancelled'])): ?>
  <div class="alert alert-info">Appointment cancelled.</div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card"><div class="sc-label">Upcoming</div><div class="sc-value"><?php echo (int) $stats['upcoming']; ?></div></div>
  <div class="stat-card"><div class="sc-label">Completed</div><div class="sc-value"><?php echo (int) $stats['done']; ?></div></div>
  <div class="stat-card"><div class="sc-label">Cancelled</div><div class="sc-value"><?php echo (int) $stats['cancelled']; ?></div></div>
  <div class="stat-card"><div class="sc-label">Total Appointments</div><div class="sc-value"><?php echo (int) $stats['total']; ?></div></div>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h2>Upcoming Appointments</h2>
      <p>Pending and confirmed bookings from today onward.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Queue Code</th><th>Office</th><th>Service</th><th>Date &amp; Time</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (!$upcoming): ?>
          <tr><td colspan="6" class="empty-row">No upcoming appointments yet. <a href="book.php" style="color:var(--maroon-700); font-weight:700;">Book one now →</a></td></tr>
        <?php else: foreach ($upcoming as $a): ?>
          <tr>
            <td class="mono"><?php echo htmlspecialchars($a['queue_code'] ?? '—'); ?></td>
            <td style="text-transform:capitalize;"><?php echo htmlspecialchars($a['office']); ?></td>
            <td><?php echo htmlspecialchars($a['service_name']); ?></td>
            <td><?php echo date('M j, Y', strtotime($a['slot_date'])) . ' · ' . date('g:i A', strtotime($a['slot_time'])); ?></td>
            <td><span class="badge badge-<?php echo $a['status']; ?>"><?php echo str_replace('_', ' ', $a['status']); ?></span></td>
            <td>
              <form method="post" onsubmit="return confirm('Cancel this appointment?');">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
