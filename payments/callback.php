<?php
// payments/callback.php — Safaricom M-Pesa STK Callback Handler
require_once '../src/db.php';

// Always respond with JSON so Safaricom knows we received it
header('Content-Type: application/json');

// --- 1. Read & Log the Raw Payload ---
$rawPayload = file_get_contents('php://input');

$logDir  = __DIR__ . '/../logs';
$logFile = $logDir . '/mpesa_callback.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
file_put_contents($logFile, date('Y-m-d H:i:s') . ' — ' . $rawPayload . PHP_EOL, FILE_APPEND);

// --- 2. Decode the Payload ---
$data = json_decode($rawPayload);

if (!isset($data->Body->stkCallback)) {
    // Malformed payload — still respond OK so Safaricom doesn't retry endlessly
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    exit;
}

$callback          = $data->Body->stkCallback;
$resultCode        = $callback->ResultCode;
$checkoutRequestID = $callback->CheckoutRequestID;

// --- 3. Match the Contribution Row Precisely by CheckoutRequestID ---
if ($resultCode == 0) {
    // Success — extract the MpesaReceiptNumber
    $receiptNumber = null;
    if (isset($callback->CallbackMetadata->Item)) {
        foreach ($callback->CallbackMetadata->Item as $item) {
            if ($item->Name === 'MpesaReceiptNumber') {
                $receiptNumber = $item->Value;
                break;
            }
        }
    }

    $stmt = $pdo->prepare(
        "UPDATE contributions
         SET status = 'SUCCESS', transaction_id = :tx_id
         WHERE checkout_request_id = :checkout_id"
    );
    $stmt->execute([
        'tx_id'       => $receiptNumber ?? $checkoutRequestID,
        'checkout_id' => $checkoutRequestID,
    ]);
} else {
    // Failed or cancelled by user
    $stmt = $pdo->prepare(
        "UPDATE contributions
         SET status = 'FAILED'
         WHERE checkout_request_id = :checkout_id AND status = 'PENDING'"
    );
    $stmt->execute(['checkout_id' => $checkoutRequestID]);
}

// --- 4. Acknowledge to Safaricom ---
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
?>