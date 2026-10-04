<?php

/**
 * IBA College of Mindanao, Incorporated
 * Student Appointment System — Landing Page
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$roleLabels = [
    'student'   => 'Student',
    'registrar' => 'Registrar',
    'cashier'   => 'Cashier'
];

$roleIcons = [
    'student'   => '🎓',
    'registrar' => '📋',
    'cashier'   => '🧾'
];

// Already logged in? Go straight to the right dashboard.
if (current_user()) {
    $u = current_user();

    header(
        'Location: ' .
        ($u['role'] === 'student'
            ? 'student/dashboard.php'
            : 'staff/dashboard.php')
    );

    exit;
}

$login_error = '';
$last_role   = 'student';

// --------------------------------------------------
// HANDLE LOGIN POST
// --------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'login'
) {

    $role     = $_POST['role'] ?? '';
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $last_role = in_array($role, ['student', 'registrar', 'cashier'], true)
        ? $role
        : 'student';

    if (!in_array($role, ['student', 'registrar', 'cashier'], true)) {

        $login_error = 'Invalid role selected.';

    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $login_error = 'Please enter a valid email address.';

    } elseif ($password === '') {

        $login_error = 'Please enter your password.';

    } elseif (!login_user($pdo, $email, $password, $role)) {

        $login_error = 'Incorrect email or password for ' . $roleLabels[$role] . '.';

    } else {

        header(
            'Location: ' .
            ($role === 'student'
                ? 'student/dashboard.php'
                : 'staff/dashboard.php')
        );

        exit;
    }
}

$isLoggedIn = false;

$year = date('Y');

?>

<!DOCTYPE html>

<html lang="en">

<head>

```
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>IBA College of Mindanao, Inc. — Student Appointment System</title>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,500&family=Manrope:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
```

<style>

:root {
    --maroon-950: #20050a;
    --maroon-900: #33070d;
    --maroon-800: #5c0e17;
    --maroon-700: #7a121e;
    --maroon-600: #951525;

    --smoke-050: #fbf9f6;
    --smoke-100: #f5f1eb;
    --smoke-300: #ddd3c8;

    --ash-700: #463a36;
    --ash-500: #7a6d67;

    --gold-400: #c7a253;
    --gold-300: #dcc07f;

    --shadow-lg: 0 30px 60px -20px rgba(32, 5, 10, 0.45);
    --ease: cubic-bezier(.16, .84, .44, 1);
}

* { box-sizing: border-box; }

html { scroll-behavior: smooth; }

html, body {
    max-width: 100%;
    overflow-x: hidden;
}

body {
    margin: 0;
    background: var(--smoke-100);
    color: var(--ash-700);
    font-family: 'Manrope', sans-serif;
    -webkit-font-smoothing: antialiased;
}

h1, h2, h3, .display {
    font-family: 'Fraunces', serif;
    color: var(--maroon-900);
    margin: 0;
    letter-spacing: -0.01em;
}

.mono {
    font-family: 'IBM Plex Mono', monospace;
    letter-spacing: 0.04em;
}

a { color: inherit; }

img {
    max-width: 100%;
    display: block;
}

button {
    font-family: inherit;
    cursor: pointer;
}

.wrap {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 28px;
}

section {
    position: relative;
}

::selection {
    background: var(--maroon-700);
    color: var(--smoke-050);
}

:focus-visible {
    outline: 2px solid var(--gold-400);
    outline-offset: 3px;
    border-radius: 4px;
}

@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* =========================================================
   NAVBAR
========================================================= */

header.nav {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(32, 5, 10, 0.94);
    backdrop-filter: blur(14px) saturate(140%);
    -webkit-backdrop-filter: blur(14px) saturate(140%);
    border-bottom: 1px solid rgba(220, 192, 127, 0.18);
}

.nav-inner {
    max-width: 1180px;
    min-height: 74px;
    margin: 0 auto;
    padding: 10px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    flex: 0 1 auto;
    min-width: 0;
}

.brand-logo {
    width: 52px;
    height: 52px;
    object-fit: contain;
    object-position: center;
    background: transparent;
    flex: none;
}

.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.15;
    min-width: 0;
}

.brand-text .b1 {
    color: var(--smoke-050);
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.brand-text .b2 {
    color: var(--gold-300);
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-top: 4px;
}

.nav-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 22px;
    margin-left: auto;
    flex: 0 0 auto;
}

.main-nav {
    display: flex;
    align-items: center;
}

.nav-links {
    display: flex;
    align-items: center;
    gap: 28px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-links a {
    position: relative;
    display: inline-block;
    padding: 8px 0;
    color: var(--smoke-300);
    text-decoration: none;
    font-size: 13.5px;
    font-weight: 600;
    transition: color 0.2s var(--ease);
}

.nav-links a:hover {
    color: var(--smoke-050);
}

.nav-links a::after {
    content: "";
    position: absolute;
    left: 0;
    right: 100%;
    bottom: 0;
    height: 2px;
    background: var(--gold-400);
    transition: right 0.25s var(--ease);
}

.nav-links a:hover::after {
    right: 0;
}

.portal-dd {
    position: relative;
    flex: none;
}

.portal-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(180deg, var(--gold-300), var(--gold-400));
    color: var(--maroon-950);
    border: none;
    border-radius: 9px;
    padding: 10px 16px;
    font-weight: 700;
    font-size: 13.5px;
    box-shadow: 0 8px 18px -8px rgba(199, 162, 83, 0.6);
    transition: transform 0.18s var(--ease), box-shadow 0.18s var(--ease);
}

.portal-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 22px -8px rgba(199, 162, 83, 0.75);
}

.portal-btn svg {
    transition: transform 0.22s var(--ease);
}

.portal-dd[data-open="true"] .portal-btn svg {
    transform: rotate(180deg);
}

.portal-menu {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 280px;
    max-width: calc(100vw - 40px);
    background: var(--smoke-050);
    border-radius: 14px;
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--smoke-300);
    padding: 8px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-8px) scale(.98);
    transition: all .2s var(--ease);
}

.portal-dd[data-open="true"] .portal-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

.portal-menu .pm-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    border-radius: 10px;
    padding: 11px 12px;
    color: var(--ash-700);
    text-decoration: none;
    transition: background 0.15s var(--ease);
}

.portal-menu .pm-item:hover {
    background: var(--smoke-100);
}

.pm-ic {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--maroon-800);
    color: var(--gold-300);
    font-size: 15px;
    flex: none;
}

.pm-item .pm-t {
    font-weight: 700;
    font-size: 13.5px;
    color: var(--maroon-900);
    display: block;
}

.pm-item .pm-s {
    font-size: 11.5px;
    color: var(--ash-500);
    display: block;
    margin-top: 2px;
}

.portal-menu .pm-divider {
    height: 1px;
    background: var(--smoke-300);
    margin: 6px 4px;
}

.burger {
    display: none;
    background: none;
    border: none;
    padding: 6px;
}

.burger span {
    display: block;
    width: 22px;
    height: 2px;
    background: var(--smoke-050);
    margin: 5px 0;
    border-radius: 2px;
}

/* =========================================================
   WELCOME BANNER
========================================================= */

.welcome-banner {
    background: var(--maroon-800);
    border-bottom: 1px solid rgba(220, 192, 127, 0.18);
}

.wb-inner {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 22px 0;
    flex-wrap: wrap;
}

.wb-ic {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: rgba(245, 241, 235, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex: none;
}

.wb-text {
    flex: 1 1 280px;
    min-width: 0;
}

.wb-eyebrow {
    display: block;
    color: var(--gold-300);
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.wb-text h2 {
    color: var(--smoke-050);
    font-size: 20px;
    font-weight: 600;
}

.wb-text p {
    color: var(--smoke-300);
    font-size: 13.5px;
    margin-top: 6px;
    line-height: 1.5;
    max-width: 520px;
}

/* =========================================================
   HERO
========================================================= */

.hero {
    background:
        radial-gradient(
            ellipse 60% 45% at 15% 8%,
            rgba(255,255,255,0.10),
            transparent 60%
        ),
        linear-gradient(
            180deg,
            var(--maroon-950) 0%,
            var(--maroon-900) 55%,
            var(--maroon-800) 100%
        );
    overflow: hidden;
    padding: 96px 0 130px;
}

.smoke-layer {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.smoke-blob {
    position: absolute;
    border-radius: 50%;
    background:
        radial-gradient(
            circle,
            rgba(255,255,255,0.55),
            rgba(255,255,255,0) 70%
        );
    filter: blur(40px);
    mix-blend-mode: screen;
    opacity: 0.16;
    animation: drift 26s ease-in-out infinite;
}

.smoke-blob.b1 {
    width: 480px;
    height: 480px;
    top: -140px;
    left: -100px;
    animation-duration: 30s;
}

.smoke-blob.b2 {
    width: 380px;
    height: 380px;
    top: 20%;
    right: -80px;
    animation-duration: 24s;
    animation-delay: -6s;
    opacity: 0.12;
}

.smoke-blob.b3 {
    width: 560px;
    height: 560px;
    bottom: -260px;
    left: 30%;
    animation-duration: 34s;
    animation-delay: -14s;
    opacity: 0.10;
}

@keyframes drift {
    0% {
        transform: translate(0,0) scale(1);
    }

    50% {
        transform: translate(60px,-40px) scale(1.12);
    }

    100% {
        transform: translate(0,0) scale(1);
    }
}

.hero-inner {
    position: relative;
    z-index: 2;
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    gap: 50px;
    align-items: center;
}

.eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--gold-300);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    margin-bottom: 22px;
}

.eyebrow .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--gold-400);
}

.hero h1 {
    color: var(--smoke-050);
    font-size: clamp(38px, 5.2vw, 62px);
    line-height: 1.04;
    font-weight: 700;
}

.hero h1 em {
    font-style: italic;
    color: var(--gold-300);
}

.hero p.lead {
    color: var(--smoke-300);
    font-size: 17px;
    line-height: 1.65;
    max-width: 520px;
    margin-top: 22px;
}

.hero-ctas {
    display: flex;
    gap: 14px;
    margin-top: 34px;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    padding: 14px 24px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 14.5px;
    border: 1px solid transparent;
    transition:
        transform .18s var(--ease),
        box-shadow .18s var(--ease),
        background .18s var(--ease);
}

.btn-primary {
    background: linear-gradient(
        180deg,
        var(--gold-300),
        var(--gold-400)
    );
    color: var(--maroon-950);
    box-shadow: 0 14px 26px -10px rgba(199,162,83,0.55);
}

.btn-primary:hover {
    transform: translateY(-2px);
}

.btn-ghost {
    color: var(--smoke-050);
    border-color: rgba(245,241,235,0.35);
    background: rgba(245,241,235,0.04);
}

.btn-ghost:hover {
    background: rgba(245,241,235,0.1);
    transform: translateY(-2px);
}

/* =========================================================
   TICKET
========================================================= */

.ticket {
    position: relative;
    background: var(--smoke-050);
    border-radius: 18px;
    box-shadow: var(--shadow-lg);
    padding: 26px 26px 22px;
    max-width: 360px;
    margin-left: auto;
    transform: rotate(2.5deg);
    animation: floaty 6s ease-in-out infinite;
}

@keyframes floaty {
    0%, 100% {
        transform: rotate(2.5deg) translateY(0);
    }

    50% {
        transform: rotate(2.5deg) translateY(-10px);
    }
}

.ticket::before,
.ticket::after {
    content: "";
    position: absolute;
    width: 26px;
    height: 26px;
    background: var(--maroon-950);
    border-radius: 50%;
    top: 50%;
    transform: translateY(-50%);
}

.ticket::before {
    left: -13px;
}

.ticket::after {
    right: -13px;
}

.ticket-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.ticket-top .tt-label {
    font-size: 10.5px;
    letter-spacing: 0.12em;
    color: var(--ash-500);
    text-transform: uppercase;
    font-weight: 700;
}

.ticket-top .tt-office {
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 20px;
    color: var(--maroon-900);
    margin-top: 4px;
}

.status-pill {
    background: #e8f3ea;
    color: #1f7a41;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 10px;
    border-radius: 20px;
}

.perforation {
    border-top: 2px dashed var(--smoke-300);
    margin: 18px 0;
    position: relative;
}

.ticket-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 10px;
}

.tg-item .tg-l {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--ash-500);
    font-weight: 700;
}

.tg-item .tg-v {
    font-size: 14px;
    color: var(--maroon-900);
    font-weight: 700;
    margin-top: 3px;
}

.ticket-code {
    margin-top: 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--smoke-100);
    border-radius: 10px;
    padding: 10px 12px;
}

.ticket-code .code {
    font-size: 13px;
    color: var(--maroon-800);
    font-weight: 700;
}

.qr {
    width: 44px;
    height: 44px;
    border-radius: 6px;
    background:
        repeating-linear-gradient(
            90deg,
            var(--maroon-950) 0 4px,
            transparent 4px 8px
        ),
        repeating-linear-gradient(
            0deg,
            var(--maroon-950) 0 4px,
            transparent 4px 8px
        );
    background-blend-mode: multiply;
    opacity: 0.85;
}

/* =========================================================
   SECTIONS
========================================================= */

.section {
    padding: 100px 0;
}

.section-head {
    max-width: 640px;
    margin-bottom: 56px;
}

.section-head .eyebrow {
    color: var(--maroon-700);
}

.section-head .eyebrow .dot {
    background: var(--maroon-700);
}

.section-head h2 {
    font-size: clamp(28px, 3.4vw, 40px);
    font-weight: 600;
}

.section-head p {
    color: var(--ash-500);
    font-size: 16px;
    margin-top: 14px;
    line-height: 1.6;
}

/* =========================================================
   PORTALS
========================================================= */

.portals {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

.portal-card {
    background: var(--smoke-050);
    border: 1px solid var(--smoke-300);
    border-radius: 18px;
    padding: 30px 26px;
    position: relative;
    overflow: hidden;
    transition:
        transform .25s var(--ease),
        box-shadow .25s var(--ease),
        border-color .25s var(--ease);
}

.portal-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: transparent;
}

.portal-card .pc-glow {
    position: absolute;
    inset: -40% -40% auto auto;
    width: 200px;
    height: 200px;
    border-radius: 50%;
    background:
        radial-gradient(
            circle,
            rgba(149,21,37,0.14),
            transparent 70%
        );
    pointer-events: none;
}

.pc-icon {
    width: 52px;
    height: 52px;
    border-radius: 13px;
    background: var(--maroon-900);
    color: var(--gold-300);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    margin-bottom: 20px;
}

.pc-badge {
    position: absolute;
    top: 26px;
    right: 26px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    padding: 5px 9px;
    border-radius: 20px;
    background: var(--smoke-100);
    color: var(--ash-500);
}

.portal-card h3 {
    font-size: 21px;
    font-weight: 600;
    margin-bottom: 10px;
}

.portal-card p.desc {
    color: var(--ash-500);
    font-size: 14.5px;
    line-height: 1.6;
    margin-bottom: 20px;
    min-height: 66px;
}

.pc-list {
    list-style: none;
    margin: 0 0 24px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.pc-list li {
    display: flex;
    gap: 9px;
    align-items: flex-start;
    font-size: 13.5px;
    color: var(--ash-700);
}

.pc-list li svg {
    flex: none;
    margin-top: 2px;
    color: var(--maroon-700);
}

.pc-cta {
    width: 100%;
    background: var(--maroon-900);
    color: var(--smoke-050);
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-weight: 700;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background .2s var(--ease);
}

.pc-cta:hover {
    background: var(--maroon-700);
}

/* =========================================================
   HOW IT WORKS
========================================================= */

.how {
    background: var(--maroon-950);
    color: var(--smoke-100);
    position: relative;
    overflow: hidden;
}

.how .section-head .eyebrow {
    color: var(--gold-300);
}

.how .section-head .eyebrow .dot {
    background: var(--gold-400);
}

.how .section-head h2 {
    color: var(--smoke-050);
}

.how .section-head p {
    color: var(--smoke-300);
}

.steps {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0;
    position: relative;
}

.steps::before {
    content: "";
    position: absolute;
    top: 26px;
    left: 8%;
    right: 8%;
    height: 1px;
    background:
        repeating-linear-gradient(
            90deg,
            rgba(220,192,127,0.4) 0 8px,
            transparent 8px 16px
        );
}

.step {
    position: relative;
    padding-right: 20px;
}

.step .num {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--maroon-800);
    border: 1px solid rgba(220,192,127,0.35);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 19px;
    color: var(--gold-300);
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.step h4 {
    color: var(--smoke-050);
    font-family: 'Fraunces', serif;
    font-weight: 600;
    font-size: 18px;
    margin-bottom: 8px;
}

.step p {
    color: var(--smoke-300);
    font-size: 13.8px;
    line-height: 1.6;
    margin: 0;
}

/* =========================================================
   FOOTER
========================================================= */

footer {
    background: var(--maroon-950);
    border-top: 1px solid rgba(220,192,127,0.15);
    padding: 56px 0 30px;
}

.foot-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 40px;
    padding-bottom: 36px;
}

.foot-brand p {
    color: var(--smoke-300);
    font-size: 13.5px;
    line-height: 1.7;
    margin-top: 14px;
    max-width: 320px;
}

.foot-col h5 {
    color: var(--gold-300);
    font-size: 12px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 16px;
}

.foot-col ul {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 11px;
}

.foot-col a {
    color: var(--smoke-300);
    text-decoration: none;
    font-size: 13.8px;
    transition: color .2s;
}

.foot-col a:hover {
    color: var(--gold-300);
}

.foot-bottom {
    border-top: 1px solid rgba(220,192,127,0.12);
    padding-top: 22px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    color: var(--smoke-300);
    font-size: 12.5px;
}

/* =========================================================
   MODAL
========================================================= */

.modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(32,5,10,0.6);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    opacity: 0;
    visibility: hidden;
    transition: all .22s var(--ease);
    padding: 20px;
}

.modal-backdrop[data-open="true"] {
    opacity: 1;
    visibility: visible;
}

.modal {
    background: var(--smoke-050);
    border-radius: 18px;
    width: 100%;
    max-width: 400px;
    padding: 30px;
    box-shadow: var(--shadow-lg);
    transform: translateY(14px) scale(.97);
    transition: transform .22s var(--ease);
    max-height: 100%;
    overflow-y: auto;
}

.modal-backdrop[data-open="true"] .modal {
    transform: translateY(0) scale(1);
}

.modal-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 22px;
}

.modal-head .mh-icon {
    width: 44px;
    height: 44px;
    border-radius: 11px;
    background: var(--maroon-900);
    color: var(--gold-300);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    margin-bottom: 14px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: var(--ash-500);
    line-height: 1;
    padding: 4px;
}

.modal h3 {
    font-size: 20px;
    font-weight: 600;
}

.modal p.sub {
    color: var(--ash-500);
    font-size: 13.5px;
    margin-top: 6px;
}

.field {
    margin-top: 16px;
}

.field label {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--ash-700);
    display: block;
    margin-bottom: 6px;
}

.field input {
    width: 100%;
    padding: 12px 13px;
    border-radius: 9px;
    border: 1px solid var(--smoke-300);
    font-family: inherit;
    font-size: 14px;
    background: var(--smoke-100);
    color: var(--ash-700);
}

.field input:focus {
    outline: 2px solid var(--gold-400);
    outline-offset: 1px;
    background: #fff;
}

.modal-submit {
    width: 100%;
    margin-top: 22px;
    background: var(--maroon-900);
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 13px;
    font-weight: 700;
    font-size: 14.5px;
    transition: background .2s;
}

.modal-submit:hover {
    background: var(--maroon-700);
}

.modal-note {
    font-size: 11.5px;
    color: var(--ash-500);
    text-align: center;
    margin-top: 14px;
    line-height: 1.5;
}

.modal-error {
    display: none;
    align-items: center;
    gap: 9px;
    margin-top: 16px;
    background: rgba(149,21,37,0.09);
    border: 1px solid rgba(149,21,37,0.35);
    color: var(--maroon-700);
    font-size: 12.8px;
    font-weight: 600;
    padding: 10px 12px;
    border-radius: 10px;
}

.modal-error.show {
    display: flex;
}

.modal-error svg {
    flex: none;
}

/* =========================================================
   TOAST
========================================================= */

.toast {
    position: fixed;
    bottom: 26px;
    left: 50%;
    transform: translateX(-50%) translateY(16px);
    background: var(--maroon-950);
    color: var(--smoke-050);
    padding: 14px 20px;
    border-radius: 11px;
    font-size: 13.5px;
    font-weight: 600;
    box-shadow: var(--shadow-lg);
    z-index: 2100;
    opacity: 0;
    visibility: hidden;
    transition: all .25s var(--ease);
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid rgba(220,192,127,0.3);
}

.toast[data-show="true"] {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}

/* =========================================================
   REVEAL
========================================================= */

.reveal {
    opacity: 0;
    transform: translateY(22px);
    transition:
        opacity .7s var(--ease),
        transform .7s var(--ease);
}

.reveal.in {
    opacity: 1;
    transform: translateY(0);
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 920px) {

    .nav-inner {
        min-height: 68px;
        padding: 9px 18px;
        gap: 12px;
    }

    .brand {
        flex: 1 1 auto;
        min-width: 0;
        gap: 10px;
    }

    .brand-logo {
        width: 42px;
        height: 42px;
    }

    .brand-text .b1 {
        font-size: 12px;
        line-height: 1.2;
        white-space: normal;
    }

    .brand-text .b2 {
        font-size: 9px;
        margin-top: 3px;
    }

    .nav-right {
        flex: 0 0 auto;
        margin-left: 0;
        gap: 8px;
    }

    .burger {
        display: block;
    }

    .portal-btn {
        padding: 9px 12px;
        font-size: 12.5px;
    }

    .main-nav {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: var(--maroon-950);
        border-bottom: 1px solid rgba(220,192,127,0.18);
        transform: translateY(-8px);
        opacity: 0;
        visibility: hidden;
        transition: all .25s var(--ease);
        z-index: 900;
    }

    .main-nav.menu-open {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
    }

    .nav-links {
        flex-direction: column;
        align-items: flex-start;
        gap: 0;
        width: 100%;
        padding: 12px 24px;
    }

    .nav-links li {
        width: 100%;
    }

    .nav-links a {
        width: 100%;
        padding: 13px 0;
        font-size: 14px;
    }

    .hero-inner {
        grid-template-columns: 1fr;
    }

    .ticket {
        margin: 40px auto 0;
        transform: rotate(0deg);
    }

    @keyframes floaty {
        0%, 100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-8px);
        }
    }

    .portals {
        grid-template-columns: 1fr;
    }

    .steps {
        grid-template-columns: 1fr 1fr;
        row-gap: 34px;
    }

    .steps::before {
        display: none;
    }

    .foot-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {

    .steps {
        grid-template-columns: 1fr;
    }

    .hero {
        padding: 76px 0 100px;
    }

    .section {
        padding: 76px 0;
    }
}

@media (max-width: 400px) {

    .nav-inner {
        padding: 8px 14px;
    }

    .brand {
        gap: 8px;
    }

    .brand-logo {
        width: 38px;
        height: 38px;
    }

    .brand-text .b1 {
        font-size: 11px;
    }

    .brand-text .b2 {
        font-size: 8px;
    }

    .portal-btn {
        padding: 8px 10px;
        font-size: 12px;
    }

    .portal-btn svg {
        display: none;
    }

    .ticket {
        padding: 22px 20px 18px;
    }

    .ticket-grid {
        gap: 12px 8px;
    }
}

</style>

</head>

<body>

<header class="nav">

```
<div class="nav-inner">

    <a href="#top" class="brand">

        <img
            src="assets/logo.png"
            alt="IBA College of Mindanao Logo"
            class="brand-logo"
        >

        <span class="brand-text">
            <span class="b1">Irene B. Antonio College of Mindanao</span>
            <span class="b2">Incorporated</span>
        </span>

    </a>

    <div class="nav-right">

        <nav class="main-nav" id="mainNav">

            <ul class="nav-links" id="navLinks">

                <li>
                    <a href="#top">Home</a>
                </li>

                <li>
                    <a href="#portals">Services</a>
                </li>

                <li>
                    <a href="#how">How it Works</a>
                </li>

                <li>
                    <a href="#contact">Contact</a>
                </li>

            </ul>

        </nav>

        <div class="portal-dd" id="portalDD">

            <button
                class="portal-btn"
                id="portalBtn"
                aria-haspopup="true"
                aria-expanded="false"
            >

                Portal

                <svg
                    width="12"
                    height="8"
                    viewBox="0 0 12 8"
                    fill="none"
                >
                    <path
                        d="M1 1L6 6L11 1"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>

            </button>

            <div class="portal-menu" role="menu">

                <button
                    class="pm-item"
                    data-role="student"
                    role="menuitem"
                >

                    <span class="pm-ic">🎓</span>

                    <span>
                        <span class="pm-t">For Students</span>
                        <span class="pm-s">
                            Book &amp; track your appointment
                        </span>
                    </span>

                </button>

                <button
                    class="pm-item"
                    data-role="registrar"
                    role="menuitem"
                >

                    <span class="pm-ic">📋</span>

                    <span>
                        <span class="pm-t">For Registrar</span>
                        <span class="pm-s">
                            Manage the daily queue
                        </span>
                    </span>

                </button>

                <button
                    class="pm-item"
                    data-role="cashier"
                    role="menuitem"
                >

                    <span class="pm-ic">🧾</span>

                    <span>
                        <span class="pm-t">For Cashier</span>
                        <span class="pm-s">
                            Manage payment slots
                        </span>
                    </span>

                </button>

                <div class="pm-divider"></div>

                <a
                    class="pm-item"
                    href="#portals"
                    role="menuitem"
                >

                    <span class="pm-ic">↗</span>

                    <span>
                        <span class="pm-t">See all services</span>
                        <span class="pm-s">
                            Compare the three offices
                        </span>
                    </span>

                </a>

            </div>

        </div>

        <button
            class="burger"
            id="burgerBtn"
            aria-label="Toggle menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>

</div>
```

</header>

<section class="hero" id="top">

```
<div class="smoke-layer">

    <div class="smoke-blob b1"></div>
    <div class="smoke-blob b2"></div>
    <div class="smoke-blob b3"></div>

</div>

<div class="wrap hero-inner">

    <div>

        <span class="eyebrow">
            <span class="dot"></span>
            Student Appointment System
        </span>

        <h1>
            Ayaw na pag LINYA.
            <br>
            <em>pag pa Appointment</em>
            na Lang.
        </h1>

        <p class="lead">
            One system, three offices —
            Registrar, Cashier, and you.
            Pick a date, pick a time, and just
            show up on your turn. Short line,
            real difference for every IBA
            Karaang and freshman alike.
        </p>

        <div class="hero-ctas">

            <button
                class="btn btn-primary"
                data-open-modal="student"
            >

                Book an Appointment

                <svg
                    width="14"
                    height="14"
                    viewBox="0 0 14 14"
                    fill="none"
                >
                    <path
                        d="M3 7H11M11 7L7 3M11 7L7 11"
                        stroke="currentColor"
                        stroke-width="1.6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

            </button>

            <a href="#how" class="btn btn-ghost">
                Unsaon Paggamit
            </a>

        </div>

    </div>

    <div class="ticket">

        <div class="ticket-top">

            <div>

                <div class="tt-label">
                    Appointment Slip
                </div>

                <div class="tt-office">
                    Registrar Office
                </div>

            </div>

            <span class="status-pill">
                Confirmed
            </span>

        </div>

        <div class="perforation"></div>

        <div class="ticket-grid">

            <div class="tg-item">
                <div class="tg-l">Student</div>
                <div class="tg-v">J. Delacruz</div>
            </div>

            <div class="tg-item">
                <div class="tg-l">Purpose</div>
                <div class="tg-v">TOR Request</div>
            </div>

            <div class="tg-item">
                <div class="tg-l">Date</div>
                <div class="tg-v">Aug 6, 2026</div>
            </div>

            <div class="tg-item">
                <div class="tg-l">Time</div>
                <div class="tg-v">9:40 AM</div>
            </div>

        </div>

        <div class="ticket-code">

            <span class="code mono">
                IBA-0417
            </span>

            <div class="qr"></div>

        </div>

    </div>

</div>
```

</section>

<section class="section" id="portals">

```
<div class="wrap">

    <div class="section-head reveal">

        <span class="eyebrow">

            <span class="dot"></span>

            Three Offices, One Line

        </span>

        <h2>
            Built for everyone who queues.
        </h2>

        <p>
            Whether you're claiming your slot,
            running the counter, or closing the
            day's collection — the system meets
            you where you work.
        </p>

    </div>

    <div class="portals">

        <article class="portal-card reveal">

            <div class="pc-glow"></div>

            <div class="pc-icon">🎓</div>

            <h3>For Students</h3>

            <p class="desc">
                Book a slot for enrollment,
                clearance, TOR, or ID concerns —
                walay pila, may turno ka nga sigurado.
            </p>

            <ul class="pc-list">

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Book anytime, even at midnight
                </li>

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Get a queue number in advance
                </li>

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Live status: pending, called, done
                </li>

            </ul>

            <button
                class="pc-cta"
                data-open-modal="student"
            >
                Student Login →
            </button>

        </article>

        <article class="portal-card reveal">

            <div class="pc-glow"></div>

            <span class="pc-badge">
                Staff Only
            </span>

            <div class="pc-icon">📋</div>

            <h3>For Registrar</h3>

            <p class="desc">
                See the day's appointments at a glance,
                set slot limits per purpose, and mark
                students served in one tap.
            </p>

            <ul class="pc-list">

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Set daily capacity per service
                </li>

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Real-time queue dashboard
                </li>

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Mark, reschedule, or no-show
                </li>

            </ul>

            <button
                class="pc-cta"
                data-open-modal="registrar"
            >
                Registrar Login →
            </button>

        </article>

        <article class="portal-card reveal">

            <div class="pc-glow"></div>

            <span class="pc-badge">
                Staff Only
            </span>

            <div class="pc-icon">🧾</div>

            <h3>For Cashier</h3>

            <p class="desc">
                Track payment appointments by teller
                window, avoid enrollment-day rush,
                and reconcile per slot at day's end.
            </p>

            <ul class="pc-list">

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Manage teller-window slots
                </li>

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Confirm payment appointments
                </li>

                <li>
                    <svg
                        width="15"
                        height="15"
                        viewBox="0 0 15 15"
                        fill="none"
                    >
                        <path
                            d="M3 8L6 11L12 4"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    Export the day's collection log
                </li>

            </ul>

            <button
                class="pc-cta"
                data-open-modal="cashier"
            >
                Cashier Login →
            </button>

        </article>

    </div>

</div>
```

</section>

<section class="section how" id="how">

```
<div class="wrap">

    <div class="section-head reveal">

        <span class="eyebrow">

            <span class="dot"></span>

            Giunsa Pag-gamit

        </span>

        <h2>
            Four steps, walay linya.
        </h2>

        <p>
            The same flow for every office —
            only the counter changes.
        </p>

    </div>

    <div class="steps">

        <div class="step reveal">

            <div class="num">01</div>

            <h4>Pili sa Office</h4>

            <p>
                Choose Registrar or Cashier,
                then the specific service you need.
            </p>

        </div>

        <div class="step reveal">

            <div class="num">02</div>

            <h4>Pili ug Petsa &amp; Oras</h4>

            <p>
                Pick an open slot from the live calendar —
                no more guessing games.
            </p>

        </div>

        <div class="step reveal">

            <div class="num">03</div>

            <h4>Kuhaa ang Slip</h4>

            <p>
                Get your digital appointment slip
                with a queue code, instantly.
            </p>

        </div>

        <div class="step reveal">

            <div class="num">04</div>

            <h4>Adto sa Imong Turno</h4>

            <p>
                Show up at your appointed time.
                Walk in, get served, walk out.
            </p>

        </div>

    </div>

</div>
```

</section>

<footer id="contact">

```
<div class="wrap">

    <div class="foot-grid">

        <div class="foot-brand">

            <a href="#top" class="brand">

                <img
                    src="assets/logo.png"
                    alt="IBA College of Mindanao Logo"
                    class="brand-logo"
                >

                <span class="brand-text">

                    <span class="b1">
                        Irene B. Antonio College of Mindanao
                    </span>

                    <span class="b2">
                        Incorporated
                    </span>

                </span>

            </a>

            <p>
                The official appointment system
                for students, the Registrar's Office,
                and the Cashier's Office.
                Dili na pila — appointment na lang.
            </p>

        </div>

        <div class="foot-col">

            <h5>Offices</h5>

            <ul>

                <li>
                    <a href="#portals">
                        Student Portal
                    </a>
                </li>

                <li>
                    <a href="#portals">
                        Registrar Office
                    </a>
                </li>

                <li>
                    <a href="#portals">
                        Cashier Office
                    </a>
                </li>

            </ul>

        </div>

        <div class="foot-col">

            <h5>Support</h5>

            <ul>

                <li>
                    <a href="#how">
                        How it Works
                    </a>
                </li>

                <li>
                    <a href="#top">
                        System Status
                    </a>
                </li>

                <li>
                    <a href="#contact">
                        Contact the Help Desk
                    </a>
                </li>

            </ul>

        </div>

    </div>

    <div class="foot-bottom">

        <span>
            ©
            <?php echo htmlspecialchars($year); ?>
            IBA College of Mindanao, Incorporated.
            All rights reserved.
        </span>

        <span class="mono">
            STUDENT APPOINTMENT SYSTEM
        </span>

    </div>

</div>
```

</footer>

<div class="modal-backdrop" id="modalBackdrop">

```
<div class="modal">

    <div class="modal-head">

        <div>

            <div class="mh-icon" id="modalIcon">
                🎓
            </div>

            <h3 id="modalTitle">
                Student Login
            </h3>

            <p class="sub" id="modalSub">
                Book and track your appointment.
            </p>

        </div>

        <button
            class="modal-close"
            id="modalClose"
            aria-label="Close"
        >
            ✕
        </button>

    </div>

    <div
        class="modal-error<?php echo $login_error ? ' show' : ''; ?>"
        id="modalErrorBox"
    >

        <svg
            width="15"
            height="15"
            viewBox="0 0 16 16"
            fill="none"
        >
            <path
                d="M8 5V9M8 11.2H8.008M14.5 8A6.5 6.5 0 1 1 1.5 8A6.5 6.5 0 0 1 14.5 8Z"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
            />
        </svg>

        <span id="modalErrorText">
            <?php echo htmlspecialchars($login_error); ?>
        </span>

    </div>

    <form
        id="modalForm"
        method="post"
        action="index.php"
    >

        <input
            type="hidden"
            name="action"
            value="login"
        >

        <input
            type="hidden"
            name="role"
            id="roleInput"
            value="<?php echo htmlspecialchars($last_role); ?>"
        >

        <div class="field">

            <label
                id="idLabel"
                for="idInput"
            >
                Email
            </label>

            <input
                id="idInput"
                name="email"
                type="email"
                placeholder="you@example.com"
                required
                value="<?php
                    echo isset($_POST['email'])
                        ? htmlspecialchars($_POST['email'])
                        : '';
                ?>"
            >

        </div>

        <div class="field">

            <label for="pwInput">
                Password
            </label>

            <input
                id="pwInput"
                name="password"
                type="password"
                placeholder="••••••••"
                required
            >

        </div>

        <button
            type="submit"
            class="modal-submit"
            id="modalSubmit"
        >
            Continue
        </button>

    </form>

    <p
        class="modal-note"
        id="signupNote"
        style="display:none;"
    >

        Wala pa kay account?

        <a
            href="register.php"
            style="color:var(--maroon-700);font-weight:700;text-decoration:none;"
        >
            Sign up as a student
        </a>

    </p>

    <p class="modal-note">

        Demo accounts:

        <span class="mono">
            student@iba.edu.ph
        </span>
        /
        student123

        ·

        <span class="mono">
            registrar@iba.edu.ph
        </span>
        /
        registrar123

        ·

        <span class="mono">
            cashier@iba.edu.ph
        </span>
        /
        cashier123

    </p>

</div>
```

</div>

<script>

/* =========================================================
   PORTAL DROPDOWN
========================================================= */

const portalDD  = document.getElementById('portalDD');
const portalBtn = document.getElementById('portalBtn');

portalBtn.addEventListener('click', function (e) {

    e.stopPropagation();

    const open =
        portalDD.getAttribute('data-open') === 'true';

    portalDD.setAttribute(
        'data-open',
        String(!open)
    );

    portalBtn.setAttribute(
        'aria-expanded',
        String(!open)
    );

});

document.addEventListener('click', function (e) {

    if (!portalDD.contains(e.target)) {

        portalDD.setAttribute(
            'data-open',
            'false'
        );

        portalBtn.setAttribute(
            'aria-expanded',
            'false'
        );

    }

});

/* =========================================================
   MOBILE MENU
========================================================= */

const burgerBtn = document.getElementById('burgerBtn');
const navLinks  = document.getElementById('navLinks');
const mainNav   = document.getElementById('mainNav');

burgerBtn.addEventListener('click', function (e) {

    e.stopPropagation();

    const open =
        mainNav.classList.toggle('menu-open');

    burgerBtn.setAttribute(
        'aria-expanded',
        String(open)
    );

    portalDD.setAttribute(
        'data-open',
        'false'
    );

    portalBtn.setAttribute(
        'aria-expanded',
        'false'
    );

});

navLinks.querySelectorAll('a').forEach(function (a) {

    a.addEventListener('click', function () {

        mainNav.classList.remove(
            'menu-open'
        );

    });

});

document.addEventListener('click', function (e) {

    if (
        !mainNav.contains(e.target) &&
        !burgerBtn.contains(e.target)
    ) {

        mainNav.classList.remove(
            'menu-open'
        );

    }

});

window.addEventListener('resize', function () {

    if (window.innerWidth > 920) {

        mainNav.classList.remove(
            'menu-open'
        );

    }

});

/* =========================================================
   LOGIN MODAL
========================================================= */

const modalBackdrop =
    document.getElementById('modalBackdrop');

const modalIcon =
    document.getElementById('modalIcon');

const modalTitle =
    document.getElementById('modalTitle');

const modalSub =
    document.getElementById('modalSub');

const idInput =
    document.getElementById('idInput');

const roleInput =
    document.getElementById('roleInput');

const modalSubmit =
    document.getElementById('modalSubmit');

const modalErrorBox =
    document.getElementById('modalErrorBox');

const roleConfig = {

    student: {
        icon: '🎓',
        title: 'Student Login',
        sub: 'Book and track your appointment.',
        btn: 'Continue'
    },

    registrar: {
        icon: '📋',
        title: 'Registrar Login',
        sub: "Manage the day's appointment queue.",
        btn: 'Sign In'
    },

    cashier: {
        icon: '🧾',
        title: 'Cashier Login',
        sub: 'Manage payment slots and collections.',
        btn: 'Sign In'
    }

};

/* OPEN MODAL */

function openModal(role, keepValues = false) {

    const cfg =
        roleConfig[role] ||
        roleConfig.student;

    modalIcon.textContent =
        cfg.icon;

    modalTitle.textContent =
        cfg.title;

    modalSub.textContent =
        cfg.sub;

    modalSubmit.textContent =
        cfg.btn;

    roleInput.value =
        role;

    document.getElementById(
        'signupNote'
    ).style.display =
        role === 'student'
            ? 'block'
            : 'none';

    modalBackdrop.setAttribute(
        'data-open',
        'true'
    );

    portalDD.setAttribute(
        'data-open',
        'false'
    );

    mainNav.classList.remove(
        'menu-open'
    );

    if (!keepValues) {

        document.getElementById(
            'modalForm'
        ).reset();

        roleInput.value =
            role;

        modalErrorBox.classList.remove(
            'show'
        );

    }

    setTimeout(function () {

        idInput.focus();

    }, 200);

}

/* OPEN MODAL BUTTONS */

document.querySelectorAll(
    '[data-open-modal]'
).forEach(function (el) {

    el.addEventListener(
        'click',
        function () {

            openModal(
                el.getAttribute(
                    'data-open-modal'
                )
            );

        }
    );

});

/* PORTAL MENU LOGIN BUTTONS */

document.querySelectorAll(
    '.pm-item[data-role]'
).forEach(function (el) {

    el.addEventListener(
        'click',
        function () {

            openModal(
                el.getAttribute(
                    'data-role'
                )
            );

        }
    );

});

/* CLOSE MODAL */

document.getElementById(
    'modalClose'
).addEventListener(
    'click',
    function () {

        modalBackdrop.setAttribute(
            'data-open',
            'false'
        );

    }
);

modalBackdrop.addEventListener(
    'click',
    function (e) {

        if (e.target === modalBackdrop) {

            modalBackdrop.setAttribute(
                'data-open',
                'false'
            );

        }

    }
);

/* ESCAPE KEY */

document.addEventListener(
    'keydown',
    function (e) {

        if (e.key === 'Escape') {

            modalBackdrop.setAttribute(
                'data-open',
                'false'
            );

            mainNav.classList.remove(
                'menu-open'
            );

        }

    }
);

/* REOPEN MODAL AFTER LOGIN ERROR */

<?php if ($login_error): ?>

openModal(
    '<?php echo htmlspecialchars(
        $last_role,
        ENT_QUOTES
    ); ?>',
    true
);

<?php endif; ?>

/* =========================================================
   SCROLL REVEAL
========================================================= */

const io =
    new IntersectionObserver(
        function (entries) {

            entries.forEach(
                function (entry) {

                    if (entry.isIntersecting) {

                        entry.target.classList.add(
                            'in'
                        );

                        io.unobserve(
                            entry.target
                        );

                    }

                }
            );

        },
        {
            threshold: 0.15
        }
    );

document.querySelectorAll(
    '.reveal'
).forEach(function (el) {

    io.observe(el);

});

</script>

</body>
</html>
