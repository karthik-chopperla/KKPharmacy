<?php
if (!defined('ROOT')) define('ROOT', dirname(dirname(dirname(__FILE__))));
require_once ROOT . '/config/app.php';
startSession();

if (!empty($_SESSION['supplier_id'])) {
    header('Location: index.php?p=sup_dash');
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name     = post('name');
    $contact  = post('contact');
    $phone    = post('phone');
    $email    = post('email');
    $address  = post('address');
    $gst_no   = post('gst_no');
    $dl_no    = post('dl_no');
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', post('username'));
    $pw       = post('password');
    $conf     = post('confirm');

    if (!$name) $errors[] = 'Company name is required.';
    if (!$phone) $errors[] = 'Phone number is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email address is required.';
    if (!$username || strlen($username) < 3) $errors[] = 'Username must be at least 3 characters (letters, numbers, underscores).';
    if (strlen($pw) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($pw !== $conf) $errors[] = 'Passwords do not match.';

    // Check unique username
    if ($username && $db->findOne('supplier_users', fn($u) => strtolower($u['username'] ?? '') === strtolower($username))) {
        $errors[] = 'Username is already taken.';
    }

    if (empty($errors)) {
        $now = date('Y-m-d H:i:s');
        $sid = $db->insert('suppliers', [
            'name'       => $name,
            'contact'    => $contact,
            'phone'      => $phone,
            'email'      => $email,
            'address'    => $address,
            'gst_no'     => $gst_no,
            'dl_no'      => $dl_no,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $db->insert('supplier_users', [
            'supplier_id' => $sid,
            'name'        => $contact ?: $name,
            'username'    => $username,
            'email'       => $email,
            'phone'       => $phone,
            'password'    => password_hash($pw, PASSWORD_BCRYPT),
            'active'      => 1,
            'verified'    => 1,
            'created_at'  => $now,
        ]);

        setFlash('success', 'Supplier account created successfully! You can now log in.');
        header('Location: index.php?p=sup_login');
        exit;
    }
}

$cfg = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register as Supplier — <?= e($cfg['store_name'] ?? APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<link rel="stylesheet" href="<?= assetUrl('assets/css/app.css') ?>">
</head>
<body>
<div class="auth-page">
<div class="auth-card" style="max-width:500px">
  <div class="auth-logo">
    <div class="auth-logo-icon" style="background:#166534">
      <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="18" height="18" rx="2"/>
        <path d="M9 9h1"/><path d="M14 9h1"/><path d="M9 14h1"/><path d="M14 14h1"/>
      </svg>
    </div>
    <div class="auth-logo-text">
      <h1><?= e($cfg['store_name'] ?? APP_NAME) ?></h1>
      <p>Supplier Portal Registration</p>
    </div>
  </div>

  <h2 class="auth-h2">Register as a Supplier</h2>
  <p class="auth-sub">Create your distributor account to view and fulfill purchase orders.</p>

  <?php foreach($errors as $er): ?>
    <div class="alert alert-danger"><span class="alert-body"><?= e($er) ?></span></div>
  <?php endforeach; ?>

  <form method="POST" novalidate>
    <?= csrfField() ?>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Company Name <span class="req">*</span></label>
        <input class="form-control" type="text" name="name" value="<?= e(post('name')) ?>" placeholder="e.g. Apex Pharma" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Contact Person</label>
        <input class="form-control" type="text" name="contact" value="<?= e(post('contact')) ?>" placeholder="Name">
      </div>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Phone <span class="req">*</span></label>
        <input class="form-control" type="tel" name="phone" value="<?= e(post('phone')) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email Address <span class="req">*</span></label>
        <input class="form-control" type="email" name="email" value="<?= e(post('email')) ?>" required>
      </div>
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">GST Number</label>
        <input class="form-control" type="text" name="gst_no" value="<?= e(post('gst_no')) ?>" placeholder="15-digit GSTIN">
      </div>
      <div class="form-group">
        <label class="form-label">Drug License (DL) Number</label>
        <input class="form-control" type="text" name="dl_no" value="<?= e(post('dl_no')) ?>" placeholder="e.g. MH-XX-12345">
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Address</label>
      <input class="form-control" type="text" name="address" value="<?= e(post('address')) ?>" placeholder="City, State">
    </div>

    <div class="form-section">Account Credentials</div>

    <div class="form-group">
      <label class="form-label">Portal Username <span class="req">*</span></label>
      <input class="form-control" type="text" name="username" value="<?= e(post('username')) ?>" required placeholder="Letters, numbers, underscores">
    </div>

    <div class="form-row-2">
      <div class="form-group">
        <label class="form-label">Password <span class="req">*</span></label>
        <input class="form-control" type="password" name="password" required placeholder="Min 6 characters">
      </div>
      <div class="form-group" style="margin-bottom:18px">
        <label class="form-label">Confirm Password <span class="req">*</span></label>
        <input class="form-control" type="password" name="confirm" required>
      </div>
    </div>

    <button type="submit" class="btn btn-block btn-lg" style="background:#166534;color:#fff">Create Supplier Account</button>
  </form>

  <div class="auth-divider" style="margin-top:14px">already have an account?</div>
  <a href="index.php?p=sup_login" class="btn btn-ghost btn-block">Sign In to Supplier Portal</a>

  <div class="auth-footer mt-2">Developed by <strong>Karthik</strong></div>
</div>
</div>
<script src="<?= assetUrl('assets/js/app.js') ?>"></script>
</body>
</html>
