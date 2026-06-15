<?php
require_once '../src/db.php';

header('Content-Type: application/json');

// --- 1. Input Validation ---
$name   = trim($_POST['name'] ?? '');
$phone  = trim($_POST['phone'] ?? '');
$amount = intval($_POST['amount'] ?? 0);

if (empty($name) || empty($phone) || $amount < 1) {
    echo json_encode(['success' => false, 'message' => 'Please provide your name, a valid phone number, and an amount.']);
    exit;
}

// --- 2. Normalize Phone to 254XXXXXXXXX ---
// Strip spaces, dashes, and leading +
$phone = preg_replace('/[\s\-+]/', '', $phone);
if (str_starts_with($phone, '0')) {
    $phone = '254' . substr($phone, 1);
}
if (!preg_match('/^2547\d{8}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Kenyan phone number. Use format 07XX XXX XXX.']);
    exit;
}

// --- 3. Fetch Config from Database ---
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$config = [];
while ($row = $stmt->fetch()) { $config[$row['setting_key']] = $row['setting_value']; }

$isLive         = ($config['env_mode'] ?? 'sandbox') === 'live';
$consumerKey    = $isLive ? ($config['mpesa_live_key'] ?? '') : ($config['mpesa_sandbox_key'] ?? '');
$consumerSecret = $isLive ? ($config['mpesa_live_secret'] ?? '') : ($config['mpesa_sandbox_secret'] ?? '');
$passkey        = $config['mpesa_passkey'] ?? '';
$shortcode      = $config['mpesa_shortcode'] ?? '';

if (empty($consumerKey) || empty($consumerSecret) || empty($passkey) || empty($shortcode)) {
    echo json_encode(['success' => false, 'message' => 'M-Pesa is not fully configured. Please contact the administrator.']);
    exit;
}

// --- 4. Get Access Token ---
$authUrl = $isLive
    ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
    : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$curl = curl_init($authUrl);
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER     => ['Authorization: Basic ' . base64_encode($consumerKey . ':' . $consumerSecret)],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$authResponse = curl_exec($curl);
$authError    = curl_error($curl);
curl_close($curl);

if ($authError) {
    echo json_encode(['success' => false, 'message' => 'Could not connect to M-Pesa. Please try again.']);
    exit;
}

$authData    = json_decode($authResponse);
$accessToken = $authData->access_token ?? null;

if (!$accessToken) {
    echo json_encode(['success' => false, 'message' => 'M-Pesa authentication failed. Check your API credentials.']);
    exit;
}

// --- 5. Build STK Push Payload ---
$timestamp = date('YmdHis');
$password  = base64_encode($shortcode . $passkey . $timestamp);

// Dynamic callback URL based on current server host
$scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host        = $_SERVER['HTTP_HOST'] ?? 'localhost';
$callbackUrl = $scheme . '://' . $host . '/munangwe-proforma/payments/callback.php';

$stkUrl = $isLive
    ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
    : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$payload = json_encode([
    'BusinessShortCode' => $shortcode,
    'Password'          => $password,
    'Timestamp'         => $timestamp,
    'TransactionType'   => 'CustomerPayBillOnline',
    'Amount'            => $amount,
    'PartyA'            => $phone,
    'PartyB'            => $shortcode,
    'PhoneNumber'       => $phone,
    'CallBackURL'       => $callbackUrl,
    'AccountReference'  => 'MunangweRetirement',
    'TransactionDesc'   => 'Contribution – John & Rose Munangwe Retirement',
]);

$curl = curl_init($stkUrl);
curl_setopt_array($curl, [
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken],
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
]);
$stkResponse = curl_exec($curl);
$stkError    = curl_error($curl);
curl_close($curl);

if ($stkError) {
    echo json_encode(['success' => false, 'message' => 'STK push request failed. Please try again.']);
    exit;
}

$res = json_decode($stkResponse);

// --- 6. Log & Respond ---
if (isset($res->CheckoutRequestID)) {
    // Store the checkout_request_id so the callback can match precisely
    $stmt = $pdo->prepare(
        "INSERT INTO contributions (name, phone, amount, payment_method, status, checkout_request_id)
         VALUES (?, ?, ?, 'MPESA', 'PENDING', ?)"
    );
    $stmt->execute([$name, $phone, $amount, $res->CheckoutRequestID]);

    echo json_encode([
        'success' => true,
        'message' => 'An M-Pesa prompt has been sent to ' . $phone . '. Enter your PIN to complete the payment.',
    ]);
} else {
    $errorMessage = $res->errorMessage ?? ($res->ResponseDescription ?? 'Unknown error from M-Pesa.');
    echo json_encode(['success' => false, 'message' => 'M-Pesa error: ' . $errorMessage]);
}
?>