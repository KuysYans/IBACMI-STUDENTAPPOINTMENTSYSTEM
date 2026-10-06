<?php
/**
 * IBA College of Mindanao, Incorporated
 * Student Appointment System — Self-Registration (students only)
 *
 * Registrar/Cashier accounts are still staff-created only (see README).
 * This lets any number of students create their own account instead of
 * relying on the single seeded demo student.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Go straight to the right dashboard.
if (current_user()) {
    $u = current_user();
    header('Location: ' . ($u['role'] === 'student' ? 'student/dashboard.php' : 'staff/dashboard.php'));
    exit;
}

$error   = '';
$old     = ['full_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $old      = ['full_name' => $fullName, 'email' => $email];

    if ($fullName === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'Please fill out every field.';
    } elseif (!preg_match("/^\p{L}[\p{L}\s.'\-]*$/u", $fullName)) {
        // Letters (incl. ñ, accents), spaces, period, apostrophe, hyphen only. No digits.
        $error = 'Full name must contain letters only. Numbers are not allowed.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/@iba\.edu\.ph$/i', $email)) {
        $error = 'Please use your @iba.edu.ph school email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Race-safe uniqueness check: rely on the UNIQUE(email) DB constraint,
        // catch the duplicate-key error instead of trusting a prior SELECT.
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, "student")'
            );
            $stmt->execute([$fullName, $email, $hash]);

            // Auto-login right after signup.
            login_user($pdo, $email, $password, 'student');
            header('Location: student/dashboard.php?welcome=1');
            exit;
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? 'An account with that email already exists. Try logging in instead.'
                : 'Could not create your account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account · IBA Appointment System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --maroon-950:#20050a; --maroon-900:#33070d; --maroon-800:#5c0e17; --maroon-700:#7a121e;
    --smoke-050:#fbf9f6; --smoke-100:#f5f1eb; --smoke-300:#ddd3c8;
    --ash-700:#463a36; --ash-500:#7a6d67;
    --gold-400:#c7a253; --gold-300:#dcc07f;
    --shadow-lg: 0 30px 60px -20px rgba(32,5,10,0.45);
    --ease: cubic-bezier(.16,.84,.44,1);
  }
  *{box-sizing:border-box;}
  html,body{ height:100%; }
  body{
    margin:0; min-height:100vh;
    display:flex; align-items:stretch; justify-content:stretch;
    font-family:'Manrope', sans-serif; color:var(--ash-700);
    background:var(--smoke-050);
  }

  /* ---- Split layout ---- */
  .split{
    display:flex;
    width:100%;
    min-height:100vh;
  }

  /* Left: image panel */
  .split-media{
    flex:1 1 50%;
    position:relative;
    background-image: url("STUDENTS.png");
    background-size: cover;
    background-position: center;
    display:none; /* shown on wider screens, see media query */
  }
  .split-media::after{
    content:"";
    position:absolute; inset:0;
    background: linear-gradient(180deg, rgba(32,5,10,0) 40%, rgba(32,5,10,0.55) 100%);
  }
  .split-media-caption{
    position:absolute; left:32px; right:32px; bottom:32px; z-index:1;
    color:#fff;
  }
  .split-media-caption .brand{
    display:flex; align-items:center; gap:12px; text-decoration:none; margin-bottom:14px;
  }
  .split-media-caption .brand-mark{
    width:42px; height:42px; border-radius:12px;
    background: radial-gradient(circle at 30% 25%, var(--gold-300), var(--gold-400) 40%, #96742a 100%);
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:700; color:var(--maroon-950); font-size:18px; flex:none;
  }
  .split-media-caption .brand-text{ line-height:1.15; }
  .split-media-caption .brand-text .b1{ color:#fff; font-weight:700; font-size:14px; display:block; }
  .split-media-caption .brand-text .b2{ color:rgba(255,255,255,0.75); font-size:10.5px; letter-spacing:0.06em; text-transform:uppercase; }
  .split-media-caption h3{ color:#fff; font-size:20px; margin-bottom:6px; }
  .split-media-caption p{ font-size:13.5px; color:rgba(255,255,255,0.8); max-width:380px; margin:0; }

  /* Right: form panel — the whole half IS the form panel now, no floating card */
  .split-form{
    flex:1 1 50%;
    display:flex; align-items:center; justify-content:center;
    padding:56px 48px;
    background:#ffffff;
    border-left:1px solid var(--smoke-300);
  }

  h1,h3{ font-family:'Fraunces', serif; color:var(--maroon-900); margin:0; }
  a{ color:inherit; }
  .card{
    background:transparent;
    width:100%; max-width:440px; padding:0;
    box-shadow:none;
    border:none;
  }
  .brand{ display:flex; align-items:center; gap:12px; text-decoration:none; margin-bottom:20px; }
  .brand-mark{
    width:42px; height:42px; border-radius:12px;
    background: radial-gradient(circle at 30% 25%, var(--gold-300), var(--gold-400) 40%, #96742a 100%);
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:700; color:var(--maroon-950); font-size:18px; flex:none;
  }
  .brand-text{ line-height:1.15; }
  .brand-text .b1{ color:var(--maroon-900); font-weight:700; font-size:14px; display:block; }
  .brand-text .b2{ color:var(--ash-500); font-size:10.5px; letter-spacing:0.06em; text-transform:uppercase; }
  .mh-icon{
    width:46px; height:46px; border-radius:12px; background:var(--smoke-100);
    color:var(--maroon-900);
    display:flex; align-items:center; justify-content:center; font-size:20px; margin-bottom:12px;
    border:1px solid var(--smoke-300);
  }
  h1{ font-size:23px; font-weight:700; }
  p.sub{ color:var(--ash-500); font-size:13.5px; margin-top:4px; }
  .field{ margin-top:16px; }
  .field label{ font-size:12px; font-weight:700; color:var(--ash-700); display:block; margin-bottom:5px; letter-spacing:0.02em; }
  .field input{
    width:100%; padding:12px 14px; border-radius:10px; border:1.5px solid var(--smoke-300);
    font-family:inherit; font-size:14px; background:#ffffff;
    color:var(--ash-700);
    transition: all 0.2s;
  }
  .field input::placeholder{
    color:rgba(70,58,54,0.35);
  }
  .field input:focus{
    outline:none;
    border-color:var(--gold-400);
    background:#ffffff;
    box-shadow: 0 0 0 3px rgba(199,162,83,0.15);
  }
  .helptext{ font-size:11px; color:rgba(70,58,54,0.45); margin-top:4px; }
  .helptext.warn{ color:var(--maroon-700); font-weight:600; }
  .submit-btn{
    width:100%; margin-top:22px; background:var(--maroon-900); color:#fff; border:none; border-radius:10px;
    padding:14px; font-weight:700; font-size:15px; cursor:pointer; transition:all .3s;
    letter-spacing:0.02em;
  }
  .submit-btn:hover{
    background:var(--maroon-700);
    transform:translateY(-2px);
    box-shadow: 0 8px 25px rgba(51,7,13,0.2);
  }
  .error-box{
    display:flex; align-items:center; gap:9px; margin-top:16px;
    background:rgba(255,0,0,0.08); border:1px solid rgba(255,0,0,0.12);
    color:#7a121e;
    font-size:12.8px; font-weight:500; padding:10px 14px; border-radius:10px;
    backdrop-filter:blur(4px);
    -webkit-backdrop-filter:blur(4px);
  }
  .foot-note{ font-size:12.5px; color:rgba(70,58,54,0.45); text-align:center; margin-top:18px; }
  .foot-note a{ color:var(--maroon-700); font-weight:700; text-decoration:none; transition:color .2s; }
  .foot-note a:hover{ color:var(--maroon-900); }

  /* Show the image panel only on wider viewports; on mobile it's form-only */
  @media (min-width: 900px){
    .split-media{ display:block; }
  }
</style>
  <link rel="icon" type="image/png" href="assets/logo.png?v=2">
</head>
<body>
  <div class="split">

    <!-- LEFT: image panel -->
    <div class="split-media">
      <div class="split-media-caption">
        <a href="index.php" class="brand">
         
        </a>
       
      </div>
    </div>

    <!-- RIGHT: form panel -->
    <div class="split-form">
      <div class="card">
        <a href="index.php" class="brand">
          
        </a>
          
        <div class="mh-icon">🎓</div>
        <h1>Create Student Account</h1>
        <p class="sub">Sign up to book appointments with the Registrar or Cashier's office.</p>

        <?php if ($error): ?>
          <div class="error-box">
            <svg width="15" height="15" viewBox="0 0 16 16" fill="none"><path d="M8 5V9M8 11.2H8.008M14.5 8A6.5 6.5 0 1 1 1.5 8A6.5 6.5 0 0 1 14.5 8Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <form method="post" action="register.php" autocomplete="off">
          <div class="field">
            <label for="full_name">Full Name</label>
            <input id="full_name" name="full_name" type="text" placeholder="Juan Delacruz" required
                   autocomplete="name" inputmode="text"
                   value="<?php echo htmlspecialchars($old['full_name']); ?>">
            <p class="helptext" id="nameHelp">Letters only. Numbers are not allowed.</p>
          </div>
          <div class="field">
            <label for="email">School Email</label>
            <input id="email" name="email" type="email" placeholder="you@iba.edu.ph" required
                   value="<?php echo htmlspecialchars($old['email']); ?>">
            <p class="helptext">Must be your @iba.edu.ph address.</p>
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="••••••••" minlength="8" required
                   autocomplete="new-password">
            <p class="helptext">At least 8 characters.</p>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm Password</label>
            <input id="confirm_password" name="confirm_password" type="password" placeholder="••••••••" minlength="8" required
                   autocomplete="new-password">
            <p class="helptext" id="confirmHelp">Type it again. Pasting is disabled.</p>
          </div>
          <button type="submit" class="submit-btn">Create Account</button>
        </form>

        <p class="foot-note">Already have an account? <a href="index.php">Log in here</a></p>
      </div>
    </div>

  </div>

<script>
(function () {

  /* ---------------------------------------------------------
     FULL NAME: no digits / symbols
     Allowed: letters (incl. ñ and accents), space, . ' -
  --------------------------------------------------------- */
  var nameInput = document.getElementById('full_name');
  var nameHelp  = document.getElementById('nameHelp');
  var nameTimer;

  // Strip anything not allowed as the user types or pastes
  var disallowed = /[^\p{L}\s.'\-]/gu;

  nameInput.addEventListener('input', function () {
    var cleaned = nameInput.value.replace(disallowed, '');

    if (cleaned !== nameInput.value) {
      nameInput.value = cleaned;

      nameHelp.textContent = 'Numbers and symbols are not allowed in the name.';
      nameHelp.classList.add('warn');

      clearTimeout(nameTimer);
      nameTimer = setTimeout(function () {
        nameHelp.textContent = 'Letters only. Numbers are not allowed.';
        nameHelp.classList.remove('warn');
      }, 2500);
    }
  });


  /* ---------------------------------------------------------
     CONFIRM PASSWORD: block paste / drop
  --------------------------------------------------------- */
  var confirmInput = document.getElementById('confirm_password');
  var confirmHelp  = document.getElementById('confirmHelp');
  var confirmTimer;

  function warnNoPaste(e) {
    e.preventDefault();

    confirmHelp.textContent = 'Pasting is not allowed. Please type your password again.';
    confirmHelp.classList.add('warn');

    clearTimeout(confirmTimer);
    confirmTimer = setTimeout(function () {
      confirmHelp.textContent = 'Type it again. Pasting is disabled.';
      confirmHelp.classList.remove('warn');
    }, 2500);
  }

  confirmInput.addEventListener('paste', warnNoPaste);   // Ctrl+V, right-click paste, mobile paste
  confirmInput.addEventListener('drop',  warnNoPaste);   // drag and drop text into the field
  confirmInput.addEventListener('dragover', function (e) { e.preventDefault(); });

  // Also stop copying/cutting from the main password field
  var passInput = document.getElementById('password');
  ['copy', 'cut'].forEach(function (evt) {
    passInput.addEventListener(evt, function (e) { e.preventDefault(); });
  });

})();
</script>
</body>
</html>