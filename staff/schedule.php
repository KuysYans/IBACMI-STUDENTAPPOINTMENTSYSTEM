<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$user   = require_role(['registrar', 'cashier'], '../index.php');
$office = $user['role'];
$error  = '';
$success = '';

// ---- Add a new slot ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_slot') {
    $date     = $_POST['slot_date'] ?? '';
    $time     = $_POST['slot_time'] ?? '';
    $capacity = max(1, (int) ($_POST['capacity'] ?? 1));

    if (!$date || !$time) {
        $error = 'Please provide both a date and a time.';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'The date can\'t be in the past.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO schedule_slots (office, slot_date, slot_time, capacity, created_by) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$office, $date, $time, $capacity, $user['id']]);
            $success = 'Slot added.';
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? 'That exact date/time slot already exists for your office.'
                : 'Could not add the slot.';
        }
    }
}

// ---- Delete a slot (only if nobody has booked it) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_slot') {
    $slotId = (int) ($_POST['slot_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM schedule_slots WHERE id = ? AND office = ?');
    $stmt->execute([$slotId, $office]);
    $slot = $stmt->fetch();
    if ($slot && (int) $slot['booked_count'] === 0) {
        $pdo->prepare('DELETE FROM schedule_slots WHERE id = ?')->execute([$slotId]);
        $success = 'Slot removed.';
    } else {
        $error = 'Can\'t remove a slot that already has bookings — cancel those appointments first.';
    }
}

// ---- List upcoming slots ----
$stmt = $pdo->prepare('SELECT * FROM schedule_slots WHERE office = ? AND slot_date >= CURDATE() ORDER BY slot_date ASC, slot_time ASC');
$stmt->execute([$office]);
$slots = $stmt->fetchAll();

$pageTitle = 'Schedule Management';
$active = 'schedule';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1>Schedule Management</h1>
    <p class="sub">Open up time slots students can book into for the <?php echo $office; ?> office.</p>
  </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<div class="card">
  <div class="card-head"><div><h2>Add a New Slot</h2></div></div>
  <form method="post">
    <input type="hidden" name="action" value="add_slot">
    <div class="field-row">
      <div class="field">
        <label for="slot_date">Date</label>
        <input type="date" id="slot_date" name="slot_date" min="<?php echo date('Y-m-d'); ?>" required>
      </div>
      <div class="field">
        <label for="slot_time">Time</label>
        <input type="time" id="slot_time" name="slot_time" required>
      </div>
    </div>
    <div class="field" style="max-width:200px;">
      <label for="capacity">Capacity (seats)</label>
      <input type="number" id="capacity" name="capacity" min="1" value="5" required>
    </div>
    <button type="submit" class="btn btn-primary">Add Slot</button>
  </form>
</div>

<div class="card">
  <div class="card-head"><div><h2>Upcoming Slots</h2><p>Slots with active bookings can't be deleted directly — cancel those appointments first.</p></div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Date</th><th>Time</th><th>Capacity</th><th>Booked</th><th>Remaining</th><th></th></tr></thead>
      <tbody>
        <?php if (!$slots): ?>
          <tr><td colspan="6" class="empty-row">No upcoming slots yet. Add one above.</td></tr>
        <?php else: foreach ($slots as $sl): $remaining = $sl['capacity'] - $sl['booked_count']; ?>
          <tr>
            <td><?php echo date('M j, Y (D)', strtotime($sl['slot_date'])); ?></td>
            <td><?php echo date('g:i A', strtotime($sl['slot_time'])); ?></td>
            <td><?php echo (int) $sl['capacity']; ?></td>
            <td><?php echo (int) $sl['booked_count']; ?></td>
            <td><?php echo $remaining; ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Remove this slot?');">
                <input type="hidden" name="action" value="delete_slot">
                <input type="hidden" name="slot_id" value="<?php echo $sl['id']; ?>">
                <button type="submit" class="btn btn-sm btn-danger" <?php echo $sl['booked_count'] > 0 ? 'disabled title="Has bookings"' : ''; ?>>Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
