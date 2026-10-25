<?php
/**
 * DRXStore v1.0 - Front Controller
 * Works from root OR any subdirectory (drxstore, webroot, etc.)
 * Developed by Vineet
 */

// Buffer ALL output - prevents "headers already sent" from any warning/notice
ob_start();

// Define ROOT once here - config/app.php guards against re-definition
if (!defined('ROOT')) define('ROOT', __DIR__);

// Serve static assets if routed through PHP on Vercel/serverless
$reqUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($reqUri && preg_match('#/assets/(css|js|img|fonts)/(.+)$#', $reqUri, $m)) {
    $assetPath = ROOT . '/assets/' . $m[1] . '/' . $m[2];
    if (file_exists($assetPath)) {
        if (ob_get_level()) ob_end_clean();
        $ext = strtolower(pathinfo($assetPath, PATHINFO_EXTENSION));
        $mimes = [
            'css'   => 'text/css; charset=UTF-8',
            'js'    => 'application/javascript; charset=UTF-8',
            'svg'   => 'image/svg+xml',
            'png'   => 'image/png',
            'jpg'   => 'image/jpeg',
            'jpeg'  => 'image/jpeg',
            'woff'  => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf'   => 'font/ttf',
        ];
        header('Content-Type: ' . ($mimes[$ext] ?? 'text/plain'));
        header('Cache-Control: public, max-age=31536000');
        readfile($assetPath);
        exit;
    }
}

require_once ROOT . '/config/app.php';

// Start session BEFORE any output or redirect
startSession();

$p = preg_replace('/[^a-z0-9_]/', '', get('p', ''));

// ── Setup guard ──────────────────────────────────────────────────────────
// Check settings table exists AND has a row (not just table present but empty)
$setupDone = false;
try {
    $setupDone = $db->count('settings') > 0;
} catch (Exception $e) {
    // Table might not exist yet — treat as not set up
    $setupDone = false;
}

if (!$setupDone && $p !== 'setup' && $p !== 'login' && $p !== 'logout') {
    // If user has an active session but settings are gone (e.g. after DB restore),
    // destroy the stale session so they land on setup cleanly
    if (!empty($_SESSION['admin_id']) || !empty($_SESSION['supplier_id']) || !empty($_SESSION['cust_id'])) {
        session_destroy();
        session_start();
    }
    header('Location: index.php?p=setup');
    ob_end_flush();
    exit;
}

// Logout
if ($p === 'logout') {
    $pr = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $pr['path'], $pr['domain'], $pr['secure'], $pr['httponly']);
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?p=login');
    ob_end_flush();
    exit;
}

// Route map
$routes = [
    'setup'       => 'setup/wizard',
    'login'       => 'admin/login',
    'sup_login'   => 'supplier/login',
    'sup_reg'     => 'supplier/register',
    'cust_login'  => 'customer/login',
    'cust_reg'      => 'customer/register',
    'verify'        => 'customer/verify',
    'resend_verify' => 'customer/resend_verify',
    // Admin
    'dashboard'   => 'admin/dashboard',
    'medicines'   => 'admin/medicines',
    'batches'     => 'admin/batches',
    'adjust'      => 'admin/adjust_stock',
    'suppliers'   => 'admin/suppliers',
    'purchase'    => 'admin/purchase',
    'view_po'     => 'admin/view_po',
    'sales'       => 'admin/sales',
    'sales_hist'  => 'admin/sales_history',
    'invoice'     => 'admin/invoice',
    'view_inv'    => 'admin/view_invoice',
    'customers'   => 'admin/customers',
    'returns'     => 'admin/returns',
    'discounts'   => 'admin/discounts',
    'ledger'      => 'admin/ledger',
    'reports'     => 'admin/reports',
    'expiry'      => 'admin/expiry_report',
    'users'       => 'admin/users',
    'settings'    => 'admin/settings',
    'sw_update'   => 'admin/software_update',
    // Cart
    'cart_add'    => 'admin/cart_add',
    'cart_remove' => 'admin/cart_remove',
    'cart_clear'  => 'admin/cart_clear',
    'cart_setcust'=> 'admin/cart_setcust',
    'finalize'    => 'admin/finalize_sale',
    'get_batches' => 'admin/get_batches',
    // Supplier portal
    'sup_dash'    => 'supplier/dashboard',
    'sup_orders'  => 'supplier/orders',
    'sup_profile' => 'supplier/profile',
    'sup_contact' => 'supplier/contact',
    // Customer portal
    'cust_dash'   => 'customer/dashboard',
    'cust_orders' => 'customer/orders',
    'cust_return' => 'customer/return_request',
    'cust_profile'  => 'customer/profile',
    'cust_messages' => 'customer/messages',
    // API
    'smtp_test'         => 'admin/smtp_test',
    'serve_file'        => 'admin/serve_file',
    'patient_messages'  => 'admin/patient_messages',
];

// Default routing
if ($p === '') {
    if (!empty($_SESSION['admin_id']))     { header('Location: index.php?p=dashboard'); ob_end_flush(); exit; }
    if (!empty($_SESSION['supplier_id'])) { header('Location: index.php?p=sup_dash');  ob_end_flush(); exit; }
    if (!empty($_SESSION['cust_id']))     { header('Location: index.php?p=cust_dash'); ob_end_flush(); exit; }
    header('Location: index.php?p=login');
    ob_end_flush();
    exit;
}

$target = $routes[$p] ?? null;
if (!$target) {
    ob_end_clean();
    http_response_code(404);
    die('<div style="font-family:sans-serif;padding:30px"><h1>404</h1><p>Page not found.</p><a href="index.php">Home</a></div>');
}

$file = ROOT . '/public/' . $target . '.php';
if (!file_exists($file)) {
    ob_end_clean();
    http_response_code(500);
    die('<div style="font-family:sans-serif;padding:30px;color:#991b1b"><h2>Error</h2><p>Missing: ' . htmlspecialchars($target) . '.php</p></div>');
}

// Pages handle their own output - discard the buffer (pages output directly)
ob_end_clean();

require $file;
