<?php
/**
 * DRXStore - Customer/Patient Login
 * Developed by Vineet
 */
if (!defined('ROOT')) define('ROOT', dirname(dirname(dirname(__FILE__))));
require_once ROOT.'/config/app.php';
startSession();
if (!empty($_SESSION['cust_id'])) { header('Location: index.php?p=cust_dash'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ctx = 'customer';
    clearAttempts($ctx);
    $phone = post('phone'); $pw = post('password');
    if (!$phone || !$pw) {
        $error = 'Please enter your phone number and password.';
    } else {
        $cust = $db->findOne('customers', fn($c) =>
            ($c['phone'] ?? '') === $phone &&
            ($c['active'] ?? true) &&
            !empty($c['password'])
        );
        $isValidPw = $cust && password_verify($pw, $cust['password']);
        if ($isValidPw) {
            clearAttempts($ctx);
            session_regenerate_id(true);
            $_SESSION['cust_id']      = $cust['id'];
            $_SESSION['customer_name']= $cust['name'];
            $_SESSION['cust_verified']= true;
            $db->update('customers', fn($c) => $c['id'] === $cust['id'], [
                'last_login' => date('Y-m-d H:i:s')
            ]);
            setFlash('success', 'Welcome, ' . $cust['name'] . '!');
            header('Location: index.php?p=cust_dash'); exit;
        } else {
            recordAttempt($ctx);
            $error = 'Invalid phone number or password.';
        }
    }
}
$cfg = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Patient Login &mdash; <?=e($cfg['store_name']??APP_NAME)?></title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?=assetUrl('assets/css/app.css')?>">
</head>
<body>
<div class="auth-page">
<div class="auth-card">
  <div class="auth-logo">
    <div class="auth-logo-icon" style="background:#581c87">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    </div>
    <div class="auth-logo-text">
      <h1><?=e($cfg['store_name']??APP_NAME)?></h1>
      <p>Patient Portal</p>
    </div>
  </div>
  <h2 class="auth-h2">Patient Sign In</h2>
  <p class="auth-sub">Enter your phone number and password to continue.</p>

  <?php if ($error): ?>
  <div class="alert alert-danger"><span class="alert-body"><?=e($error)?></span></div>
  <?php endif; ?>

  <form method="POST" novalidate><?=csrfField()?>
    <div class="form-group"><label class="form-label">Phone Number</label>
      <input class="form-control" type="tel" name="phone" value="<?=e(post('phone'))?>" required autofocus placeholder="Enter your phone number">
    </div>
    <div class="form-group" style="margin-bottom:18px"><label class="form-label">Password <span style="color:var(--g5);font-size:.78rem;font-weight:400">(default: your phone number)</span></label>
      <input class="form-control" type="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-block btn-lg" style="background:#581c87;color:#fff;border:none;border-radius:var(--r);padding:10px;font-size:.9rem;font-weight:600;cursor:pointer">Sign In</button>
  </form>

  <div class="auth-divider" style="margin-top:16px">new patient?</div>
  <p style="text-align:center;margin-top:8px;font-size:.8rem;color:var(--g5)">Contact your pharmacy to register.</p>
  <p style="text-align:center;margin-top:4px;font-size:.78rem;color:var(--g5)">
    Forgot password? Ask pharmacy staff to reset it.
  </p>

  <div class="auth-divider" style="margin-top:10px">other portals</div>
  <div class="flex gap-2 justify-center flex-wrap">
    <a href="index.php?p=login" class="btn btn-ghost btn-sm">Admin</a>
    <a href="index.php?p=sup_login" class="btn btn-ghost btn-sm">Supplier</a>
  </div>
  <div class="auth-footer">Developed by <strong>Karthik</strong></div>
</div>
</div>
<script src="<?=assetUrl('assets/js/app.js')?>"></script>
</body></html>
