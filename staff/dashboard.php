<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notify.php';

$user   = require_role(['registrar', 'cashier'], '../index.php');
$office = $user['role']; // 'registrar' or 'cashier' — role doubles as the office

// ---- Update appointment status ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $apptId    = (int) ($_POST['appointment_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['confirmed', 'done', 'cancelled', 'no_show'];

    if (in_array($newStatus, $allowed, true)) {
        $stmt = $pdo->prepare('SELECT a.*, sl.office FROM appointments a
            JOIN schedule_slots sl ON sl.id = a.slot_id WHERE a.id = ?');
        $stmt->execute([$apptId]);
        $appt = $stmt->fetch();

        if ($appt && $appt['office'] === $office) {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute([$newStatus, $apptId]);

            // Freed-up seat if cancelled / no-show after being counted.
            if (in_array($newStatus, ['cancelled', 'no_show'], true) && $appt['status'] !== 'cancelled') {
                $pdo->prepare('UPDATE schedule_slots SET booked_count = GREATEST(booked_count - 1, 0) WHERE id = ?')->execute([$appt['slot_id']]);
            }

            $labels = ['confirmed' => 'confirmed', 'done' => 'marked as done', 'cancelled' => 'cancelled', 'no_show' => 'marked as no-show'];
            push_notification(
                $pdo,
                $appt['student_id'],
                'Your appointment ' . $appt['queue_code'] . ' was ' . $labels[$newStatus] . ' by the ' . ucfirst($office) . ' office.',
                $apptId
            );
            $pdo->commit();
            header('Location: dashboard.php?date=' . urlencode($_POST['redirect_date'] ?? date('Y-m-d')));
            exit;
        }
    }
}

// ---- Date filter (defaults to today) ----
$viewDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $viewDate)) $viewDate = date('Y-m-d');

$stmt = $pdo->prepare("SELECT a.*, s.name AS service_name, u.full_name, u.email, sl.slot_time
  FROM appointments a
  JOIN services s ON s.id = a.service_id
  JOIN schedule_slots sl ON sl.id = a.slot_id
  JOIN users u ON u.id = a.student_id
  WHERE sl.office = ? AND sl.slot_date = ?
  ORDER BY sl.slot_time ASC");
$stmt->execute([$office, $viewDate]);
$queue = $stmt->fetchAll();

// ---- Quick stats for today ----
$stmt = $pdo->prepare("SELECT
    SUM(CASE WHEN a.status IN ('pending','confirmed') THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN a.status = 'done' THEN 1 ELSE 0 END) AS done,
    SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END) AS no_show,
    COUNT(*) AS total
  FROM appointments a JOIN schedule_slots sl ON sl.id = a.slot_id
  WHERE sl.office = ? AND sl.slot_date = ?");
$stmt->execute([$office, $viewDate]);
$stats = $stmt->fetch();

$pageTitle = "Today's Queue";
$active = 'dashboard';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1><?php echo ucfirst($office); ?> Queue</h1>
    <p class="sub">Appointments booked for the selected date.</p>
  </div>
  <form method="get" style="display:flex; gap:8px; align-items:center;">
    <input type="date" name="date" value="<?php echo htmlspecialchars($viewDate); ?>" style="padding:9px 12px; border-radius:9px; border:1px solid var(--smoke-300); font-family:inherit;">
    <button type="submit" class="btn btn-outline">View</button>
    <a href="dashboard.php" class="btn btn-dark">Today</a>
  </form>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="sc-label">Active</div><div class="sc-value"><?php echo (int) $stats['active']; ?></div></div>
  <div class="stat-card"><div class="sc-label">Done</div><div class="sc-value"><?php echo (int) $stats['done']; ?></div></div>
  <div class="stat-card"><div class="sc-label">No-show</div><div class="sc-value"><?php echo (int) $stats['no_show']; ?></div></div>
  <div class="stat-card"><div class="sc-label">Total Booked</div><div class="sc-value"><?php echo (int) $stats['total']; ?></div></div>
</div>

<div class="card">
  <div class="card-head">
    <div>
      <h2><?php echo date('F j, Y (D)', strtotime($viewDate)); ?></h2>
      <p>Manage each student's appointment status.</p>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Time</th><th>Queue Code</th><th>Student</th><th>Service</th><th>Status</th><th style="min-width:260px;">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (!$queue): ?>
          <tr><td colspan="6" class="empty-row">No appointments for this date.</td></tr>
        <?php else: foreach ($queue as $a): ?>
          <tr>
            <td><?php echo date('g:i A', strtotime($a['slot_time'])); ?></td>
            <td class="mono"><?php echo htmlspecialchars($a['queue_code'] ?? '—'); ?></td>
            <td>
              <div style="font-weight:700; color:var(--ash-700);"><?php echo htmlspecialchars($a['full_name']); ?></div>
              <div style="font-size:11.5px; color:var(--ash-500);"><?php echo htmlspecialchars($a['email']); ?></div>
            </td>
            <td><?php echo htmlspecialchars($a['service_name']); ?></td>
            <td><span class="badge badge-<?php echo $a['status']; ?>"><?php echo str_replace('_', ' ', $a['status']); ?></span></td>
            <td>
              <?php if (in_array($a['status'], ['pending', 'confirmed'], true)): ?>
                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                  <?php if ($a['status'] === 'pending'): ?>
                  <form method="post"><input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
                    <input type="hidden" name="new_status" value="confirmed">
                    <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($viewDate); ?>">
                    <button type="submit" class="btn btn-sm btn-dark">Confirm</button>
                  </form>
                  <?php endif; ?>
                  <form method="post"><input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
                    <input type="hidden" name="new_status" value="done">
                    <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($viewDate); ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Mark Done</button>
                  </form>
                  <form method="post"><input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
                    <input type="hidden" name="new_status" value="no_show">
                    <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($viewDate); ?>">
                    <button type="submit" class="btn btn-sm btn-outline">No-show</button>
                  </form>
                  <form method="post" onsubmit="return confirm('Cancel this appointment?');"><input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="appointment_id" value="<?php echo $a['id']; ?>">
                    <input type="hidden" name="new_status" value="cancelled">
                    <input type="hidden" name="redirect_date" value="<?php echo htmlspecialchars($viewDate); ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                  </form>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
