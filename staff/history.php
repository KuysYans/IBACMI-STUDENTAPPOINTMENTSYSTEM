<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$user   = require_role(['registrar', 'cashier'], '../index.php');
$office = $user['role'];

$statusFilter = $_GET['status'] ?? 'all';
$allowed = ['all', 'pending', 'confirmed', 'done', 'cancelled', 'no_show'];
if (!in_array($statusFilter, $allowed, true)) $statusFilter = 'all';

$sql = "SELECT a.*, s.name AS service_name, u.full_name, u.email, sl.slot_date, sl.slot_time
  FROM appointments a
  JOIN services s ON s.id = a.service_id
  JOIN schedule_slots sl ON sl.id = a.slot_id
  JOIN users u ON u.id = a.student_id
  WHERE sl.office = ?";
$params = [$office];
if ($statusFilter !== 'all') {
    $sql .= ' AND a.status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY sl.slot_date DESC, sl.slot_time DESC LIMIT 300';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Appointment History';
$active = 'history';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1>Appointment History</h1>
    <p class="sub">Full log of every appointment booked into the <?php echo $office; ?> office.</p>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <div><h2>Records</h2></div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <?php foreach (['all' => 'All', 'confirmed' => 'Confirmed', 'done' => 'Done', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $key => $label): ?>
        <a href="?status=<?php echo $key; ?>" class="btn btn-sm <?php echo $statusFilter === $key ? 'btn-dark' : 'btn-outline'; ?>"><?php echo $label; ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Queue Code</th><th>Student</th><th>Service</th><th>Date &amp; Time</th><th>Status</th><th>Booked On</th></tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="6" class="empty-row">No records found for this filter.</td></tr>
        <?php else: foreach ($rows as $a): ?>
          <tr>
            <td class="mono"><?php echo htmlspecialchars($a['queue_code'] ?? '—'); ?></td>
            <td>
              <div style="font-weight:700; color:var(--ash-700);"><?php echo htmlspecialchars($a['full_name']); ?></div>
              <div style="font-size:11.5px; color:var(--ash-500);"><?php echo htmlspecialchars($a['email']); ?></div>
            </td>
            <td><?php echo htmlspecialchars($a['service_name']); ?></td>
            <td><?php echo date('M j, Y', strtotime($a['slot_date'])) . ' · ' . date('g:i A', strtotime($a['slot_time'])); ?></td>
            <td><span class="badge badge-<?php echo $a['status']; ?>"><?php echo str_replace('_', ' ', $a['status']); ?></span></td>
            <td><?php echo date('M j, Y', strtotime($a['created_at'])); ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
