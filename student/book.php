<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notify.php';

$user = require_role('student', '../index.php');

$office = ($_GET['office'] ?? 'registrar') === 'cashier' ? 'cashier' : 'registrar';
$error  = '';
$success = '';

// ---- Handle booking submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    $office    = $_POST['office'] === 'cashier' ? 'cashier' : 'registrar';
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $slotId    = (int) ($_POST['slot_id'] ?? 0);

    if (!$serviceId || !$slotId) {
        $error = 'Please choose a service and an available time slot.';
    } else {
        try {
            $pdo->beginTransaction();

            // Lock the slot row so two students can't double-book the last seat.
            $stmt = $pdo->prepare('SELECT * FROM schedule_slots WHERE id = ? AND office = ? FOR UPDATE');
            $stmt->execute([$slotId, $office]);
            $slot = $stmt->fetch();

            // Confirm the service really belongs to this office.
            $stmt2 = $pdo->prepare('SELECT * FROM services WHERE id = ? AND office = ? AND is_active = 1');
            $stmt2->execute([$serviceId, $office]);
            $service = $stmt2->fetch();

            if (!$slot || !$service) {
                throw new Exception('That service or time slot is no longer available.');
            }
            if ($slot['booked_count'] >= $slot['capacity']) {
                throw new Exception('Sorry, that time slot just got full. Please pick another.');
            }

            $pdo->prepare('UPDATE schedule_slots SET booked_count = booked_count + 1 WHERE id = ?')->execute([$slotId]);

            $pdo->prepare('INSERT INTO appointments (student_id, service_id, slot_id, status) VALUES (?, ?, ?, "confirmed")')
                ->execute([$user['id'], $serviceId, $slotId]);
            $newId = (int) $pdo->lastInsertId();

            $queueCode = 'IBA-' . str_pad((string) $newId, 4, '0', STR_PAD_LEFT);
            $pdo->prepare('UPDATE appointments SET queue_code = ? WHERE id = ?')->execute([$queueCode, $newId]);

            $when = date('M j, Y', strtotime($slot['slot_date'])) . ' at ' . date('g:i A', strtotime($slot['slot_time']));
            push_notification(
                $pdo,
                $user['id'],
                "Your {$service['name']} appointment is confirmed for {$when}. Queue code: {$queueCode}.",
                $newId
            );

            $pdo->commit();
            header('Location: history.php?booked=' . urlencode($queueCode));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// ---- Data for the form ----
$stmt = $pdo->prepare('SELECT * FROM services WHERE office = ? AND is_active = 1 ORDER BY name');
$stmt->execute([$office]);
$services = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM schedule_slots
  WHERE office = ? AND slot_date >= CURDATE() AND booked_count < capacity
  ORDER BY slot_date ASC, slot_time ASC");
$stmt->execute([$office]);
$slots = $stmt->fetchAll();

$unread = unread_count($pdo, $user['id']);
$pageTitle = 'Book Appointment';
$active = 'book';
require __DIR__ . '/../includes/sidebar_top.php';
?>

<div class="topbar">
  <div>
    <h1>Book an Appointment</h1>
    <p class="sub">Pick an office, a service, then an open time slot.</p>
  </div>
</div>

<?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

<div class="card">
  <div class="card-head">
    <div>
      <h2>1. Choose an Office</h2>
    </div>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="?office=registrar" class="btn <?php echo $office === 'registrar' ? 'btn-dark' : 'btn-outline'; ?>">📋 Registrar</a>
    <a href="?office=cashier" class="btn <?php echo $office === 'cashier' ? 'btn-dark' : 'btn-outline'; ?>">🧾 Cashier</a>
  </div>
</div>

<form method="post">
  <input type="hidden" name="action" value="book">
  <input type="hidden" name="office" value="<?php echo htmlspecialchars($office); ?>">

  <div class="card">
    <div class="card-head"><div><h2>2. Choose a Service</h2></div></div>
    <div class="field" style="max-width:420px;">
      <label for="service_id">Service</label>
      <select name="service_id" id="service_id" required>
        <option value="">— Select a service —</option>
        <?php foreach ($services as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <div>
        <h2>3. Choose a Time Slot</h2>
        <p>Only slots with open seats are shown. Full or past slots are hidden.</p>
      </div>
    </div>
    <?php if (!$slots): ?>
      <p class="helptext">No open slots for this office right now. Please check back later — the office may not have opened its schedule yet.</p>
    <?php else: ?>
      <div class="slot-grid">
        <?php foreach ($slots as $sl): $remaining = $sl['capacity'] - $sl['booked_count']; ?>
          <label class="slot-opt">
            <input type="radio" name="slot_id" value="<?php echo $sl['id']; ?>" required>
            <div class="box">
              <div class="d"><?php echo date('M j, Y (D)', strtotime($sl['slot_date'])); ?></div>
              <div class="t"><?php echo date('g:i A', strtotime($sl['slot_time'])); ?></div>
              <div class="r"><?php echo $remaining; ?> seat<?php echo $remaining === 1 ? '' : 's'; ?> left</div>
            </div>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn btn-primary" <?php echo !$slots ? 'disabled' : ''; ?>>Confirm Appointment</button>
</form>

<?php require __DIR__ . '/../includes/sidebar_bottom.php'; ?>
