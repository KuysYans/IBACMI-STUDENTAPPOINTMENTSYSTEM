<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
header('Location: index.php');
exit;
