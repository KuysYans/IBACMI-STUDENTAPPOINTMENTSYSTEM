<?php
/**
 * queueing.php — Live queue board (TV display)
 *
 * Open on a TV / monitor:   queueing.php
 * Single office only:       queueing.php?office=registrar   (or cashier)
 *
 * - Shows today's queue for Registrar and Cashier (max 20 per office).
 * - Auto-refreshes via AJAX (polls queueing.php?ajax=1 every 3 seconds).
 * - No login needed, and only queue codes are shown (no student names).
 *
 * How it knows who is next:
 *   Order  = slot time, then booking order.
 *   Serving = first appointment still 'pending' / 'confirmed'.
 *   When staff clicks Mark Done / No-show / Cancel on their dashboard,
 *   the status changes in the database, so the board moves to the next
 *   number on its own at the next refresh.
 */
require_once __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Manila');

const QUEUE_MAX = 20;

function build_office_queue(PDO $pdo, string $office, string $date): array
{
    $stmt = $pdo->prepare(
        "SELECT a.queue_code, a.status
           FROM appointments a
           JOIN schedule_slots sl ON sl.id = a.slot_id
          WHERE sl.office = ? AND sl.slot_date = ?
            AND a.status IN ('pending','confirmed','done','no_show')
          ORDER BY sl.slot_time ASC, a.id ASC
          LIMIT " . QUEUE_MAX
    );
    $stmt->execute([$office, $date]);
    $rows = $stmt->fetchAll();

    $items   = [];
    $serving = null;
    $next    = null;
    $waiting = 0;
    $done    = 0;

    foreach ($rows as $r) {
        $code = $r['queue_code'] ?: '—';

        if ($r['status'] === 'done') {
            $state = 'done';
            $done++;
        } elseif ($r['status'] === 'no_show') {
            $state = 'skipped';
        } else { // pending / confirmed
            if ($serving === null) {
                $state   = 'serving';
                $serving = $code;
            } elseif ($next === null) {
                $state = 'next';
                $next  = $code;
                $waiting++;
            } else {
                $state = 'waiting';
                $waiting++;
            }
        }
        $items[] = ['code' => $code, 'state' => $state];
    }

    return [
        'serving' => $serving,
        'next'    => $next,
        'waiting' => $waiting,
        'done'    => $done,
        'total'   => count($rows),
        'items'   => $items,
    ];
}

// ------------------------------------------------------------------
// AJAX endpoint (JSON)
// ------------------------------------------------------------------
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    $date = date('Y-m-d');
    echo json_encode([
        'date'      => $date,
        'updated'   => date('g:i:s A'),
        'max'       => QUEUE_MAX,
        'registrar' => build_office_queue($pdo, 'registrar', $date),
        'cashier'   => build_office_queue($pdo, 'cashier', $date),
    ]);
    exit;
}

$only = $_GET['office'] ?? '';
$only = in_array($only, ['registrar', 'cashier'], true) ? $only : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Queue Board · IBA</title>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;700;800&family=IBM+Plex+Mono:wght@500;600&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #0f1419; --panel: #182029; --line: #263240;
    --text: #f2f5f8; --muted: #8a9bad;
    --serving: #22c55e; --next: #f59e0b; --done: #475569; --wait: #e2e8f0;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body {
    background: var(--bg); color: var(--text);
    font-family: 'Manrope', system-ui, sans-serif;
    display: flex; flex-direction: column; padding: 2vh 2vw; gap: 2vh;
  }
  header { display: flex; justify-content: space-between; align-items: center; }
  header h1 { font-size: clamp(22px, 3vw, 44px); font-weight: 800; letter-spacing: .5px; }
  header .clock { text-align: right; color: var(--muted); font-size: clamp(14px, 1.6vw, 24px); }
  header .clock b { display: block; color: var(--text); font-size: clamp(20px, 2.6vw, 38px); }

  .boards { flex: 1; display: grid; gap: 2vw; grid-template-columns: repeat(<?php echo $only ? 1 : 2; ?>, 1fr); min-height: 0; }
  .board { background: var(--panel); border: 1px solid var(--line); border-radius: 18px;
           padding: 2vh 1.6vw; display: flex; flex-direction: column; gap: 2vh; min-height: 0; }
  .board h2 { font-size: clamp(18px, 2.2vw, 32px); font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: var(--muted); }

  .now { text-align: center; padding: 2vh 0; border-radius: 14px; background: #0c1116; border: 2px solid var(--serving); }
  .now .label { color: var(--serving); font-weight: 800; letter-spacing: 3px; font-size: clamp(14px, 1.6vw, 24px); }
  .now .code { font-family: 'IBM Plex Mono', monospace; font-weight: 600; font-size: clamp(44px, 7vw, 120px); line-height: 1.1; }
  .now.empty { border-color: var(--line); }
  .now.empty .label { color: var(--muted); }
  .now.empty .code { color: var(--muted); font-size: clamp(24px, 3vw, 48px); }
  .now.flash { animation: flash 1.2s ease; }
  @keyframes flash { 0%,100% { background: #0c1116; } 30% { background: #14532d; } }

  .nextbox { display: flex; justify-content: space-between; align-items: center;
             padding: 1.4vh 1.4vw; border-radius: 12px; border: 2px solid var(--next); }
  .nextbox .label { color: var(--next); font-weight: 800; letter-spacing: 2px; font-size: clamp(13px, 1.4vw, 22px); }
  .nextbox .code { font-family: 'IBM Plex Mono', monospace; font-weight: 600; font-size: clamp(26px, 3.6vw, 60px); }
  .nextbox.empty { border-color: var(--line); }
  .nextbox.empty .label, .nextbox.empty .code { color: var(--muted); }

  .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: .8vw; align-content: start; flex: 1; overflow: hidden; }
  .tile { font-family: 'IBM Plex Mono', monospace; font-weight: 600; text-align: center;
          padding: 1vh 0; border-radius: 10px; font-size: clamp(13px, 1.5vw, 24px);
          border: 2px solid var(--line); color: var(--wait); }
  .tile.done    { color: var(--done); text-decoration: line-through; border-color: transparent; background: #121921; }
  .tile.skipped { color: var(--done); border-style: dashed; }
  .tile.serving { background: var(--serving); color: #052e16; border-color: var(--serving); }
  .tile.next    { border-color: var(--next); color: var(--next); }

  .stats { display: flex; justify-content: space-between; color: var(--muted); font-size: clamp(12px, 1.3vw, 20px); }
  .stats b { color: var(--text); }
  footer { text-align: center; color: var(--muted); font-size: clamp(11px, 1.1vw, 16px); }
  footer .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: var(--serving); margin-right: 6px; }
  footer .dot.off { background: #ef4444; }
</style>
</head>
<body>

<header>
  <h1>IBA College of Mindanao · Queue</h1>
  <div class="clock"><b id="clock">--:--:--</b><span id="date"></span></div>
</header>

<div class="boards" id="boards">
  <?php foreach (($only ? [$only] : ['registrar', 'cashier']) as $office): ?>
  <section class="board" id="b-<?php echo $office; ?>">
    <h2><?php echo ucfirst($office); ?></h2>
    <div class="now empty" id="<?php echo $office; ?>-now"><div class="label">NOW SERVING</div><div class="code">—</div></div>
    <div class="nextbox empty" id="<?php echo $office; ?>-next"><span class="label">NEXT</span><span class="code">—</span></div>
    <div class="grid" id="<?php echo $office; ?>-grid"></div>
    <div class="stats" id="<?php echo $office; ?>-stats"></div>
  </section>
  <?php endforeach; ?>
</div>

<footer><span class="dot" id="dot"></span><span id="status">Connecting…</span></footer>

<script>
const OFFICES = <?php echo json_encode($only ? [$only] : ['registrar', 'cashier']); ?>;
const POLL_MS = 3000;
const lastServing = {};

function esc(s) { return String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

function render(office, q) {
  const now  = document.getElementById(office + '-now');
  const next = document.getElementById(office + '-next');

  if (q.serving) {
    now.className = 'now';
    now.innerHTML = '<div class="label">NOW SERVING</div><div class="code">' + esc(q.serving) + '</div>';
    if (lastServing[office] !== undefined && lastServing[office] !== q.serving) {
      now.classList.add('flash');
    }
  } else {
    now.className = 'now empty';
    now.innerHTML = '<div class="label">NOW SERVING</div><div class="code">' + (q.total ? 'All done' : 'No queue yet') + '</div>';
  }
  lastServing[office] = q.serving;

  if (q.next) {
    next.className = 'nextbox';
    next.innerHTML = '<span class="label">NEXT</span><span class="code">' + esc(q.next) + '</span>';
  } else {
    next.className = 'nextbox empty';
    next.innerHTML = '<span class="label">NEXT</span><span class="code">—</span>';
  }

  document.getElementById(office + '-grid').innerHTML =
    q.items.map(i => '<div class="tile ' + i.state + '">' + esc(i.code) + '</div>').join('');

  document.getElementById(office + '-stats').innerHTML =
    '<span>Waiting: <b>' + q.waiting + '</b></span><span>Done: <b>' + q.done + '</b></span><span>Total: <b>' + q.total + '</b></span>';
}

async function poll() {
  const dot = document.getElementById('dot'), st = document.getElementById('status');
  try {
    const res  = await fetch('queueing.php?ajax=1&t=' + Date.now(), { cache: 'no-store' });
    const data = await res.json();
    OFFICES.forEach(o => render(o, data[o]));
    document.getElementById('date').textContent = data.date;
    dot.className = 'dot';
    st.textContent = 'Live · updated ' + data.updated;
  } catch (e) {
    dot.className = 'dot off';
    st.textContent = 'Reconnecting…';
  }
}

function tick() { document.getElementById('clock').textContent = new Date().toLocaleTimeString(); }

poll(); setInterval(poll, POLL_MS);
tick(); setInterval(tick, 1000);
</script>
</body>
</html>
