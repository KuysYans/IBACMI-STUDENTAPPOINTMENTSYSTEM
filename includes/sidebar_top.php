<?php
/**
 * Shared "app shell" header — sidebar nav + opens <main>.
 * Expects, before including:
 *   $user    = current_user()
 *   $active  = string key of the current nav item
 *   $base    = '' if this page lives in /student or /staff (one level deep)
 *   $unread  = (optional, students only) unread notification count
 */
$roleLabels = ['student' => 'Student', 'registrar' => 'Registrar', 'cashier' => 'Cashier'];
$initial    = strtoupper(substr($user['full_name'] ?? $user['email'], 0, 1));
$unread     = $unread ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · IBA' : 'IBA Appointment System'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/theme.css">
  <link rel="icon" type="image/png" href="../assets/logo.png?v=2">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">

    <!-- BRAND (link only: logo + school name) -->
    <a href="dashboard.php" class="sb-brand">

      <img src="../assets/logo.png"
           alt="IBA College of Mindanao Logo"
           class="sb-logo"
           width="48" height="48">

      <span class="sb-text">
        <span class="sb-b1">Irene B. Antonio College of Mindanao</span>
        <span class="sb-b2">Incorporated</span>
      </span>

    </a>

    <!-- BURGER (OUTSIDE the link, so tapping it never navigates) -->
    <button class="sb-burger" id="sbBurger" type="button"
            aria-label="Toggle menu" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <nav class="side-nav">
      <?php if ($user['role'] === 'student'): ?>
        <a href="dashboard.php" class="<?php echo $active === 'dashboard' ? 'active' : ''; ?>"><span class="ic"></span> Dashboard</a>
        <a href="book.php" class="<?php echo $active === 'book' ? 'active' : ''; ?>"><span class="ic"></span> Book Appointment</a>
        <a href="history.php" class="<?php echo $active === 'history' ? 'active' : ''; ?>"><span class="ic"></span> Appointment History</a>
        <a href="notifications.php" class="<?php echo $active === 'notifications' ? 'active' : ''; ?>">
          <span class="ic">🔔</span> Notifications
          <?php if ($unread > 0): ?><span class="count"><?php echo $unread; ?></span><?php endif; ?>
        </a>
      <?php else: ?>
        <a href="dashboard.php" class="<?php echo $active === 'dashboard' ? 'active' : ''; ?>"><span class="ic"></span> Today's Queue</a>
        <a href="schedule.php" class="<?php echo $active === 'schedule' ? 'active' : ''; ?>"><span class="ic"></span> Schedule Management</a>
        <a href="history.php" class="<?php echo $active === 'history' ? 'active' : ''; ?>"><span class="ic"></span> Appointment History</a>
      <?php endif; ?>
    </nav>

    <div class="side-foot">
      <div class="who">
        <span class="av"><?php echo htmlspecialchars($initial); ?></span>
        <span class="meta">
          <span class="em"><?php echo htmlspecialchars($user['full_name'] ?? $user['email']); ?></span>
          <span class="rl"><?php echo $roleLabels[$user['role']]; ?></span>
        </span>
      </div>
      <a href="../logout.php" class="logout-link"><span class="ic">↩</span> Log out</a>
    </div>
  </aside>

  <main class="main">