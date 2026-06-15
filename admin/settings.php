<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require '../src/db.php';

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Whitelist the keys we accept to prevent arbitrary DB writes
    $allowedKeys = [
        'env_mode',
        'mpesa_sandbox_key', 'mpesa_sandbox_secret',
        'mpesa_live_key',    'mpesa_live_secret',
        'mpesa_passkey',     'mpesa_shortcode',
        'paystack_sandbox_key', 'paystack_live_key',
    ];

    foreach ($allowedKeys as $key) {
        if (isset($_POST[$key])) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = :key");
            $stmt->execute(['val' => trim($_POST[$key]), 'key' => $key]);
        }
    }

    $message     = '✅ Configuration saved successfully!';
    $messageType = 'success';
}

$settings = [];
$query = $pdo->query("SELECT setting_key, setting_value FROM settings");
while ($row = $query->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Helper to safely output a setting value
function sv(array $settings, string $key): string {
    return htmlspecialchars($settings[$key] ?? '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Settings | My Proforma</title>
    <link href="../public/css/output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">

    <nav class="bg-[#002056] text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="font-bold text-xl tracking-wide flex items-center gap-2">
                    <span class="bg-[#DA251C] text-white px-2 py-0.5 rounded text-xs uppercase">Admin</span>
                    My Proforma
                </div>
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm font-bold transition">← Dashboard</a>
                    <a href="logout.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm font-bold transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 py-10">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">API &amp; Environment Configuration</h1>
            <p class="text-gray-500 mt-1">Manage your M-Pesa credentials and environment mode.</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-xl mb-6 font-bold flex items-center gap-2">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-8">

            <!-- Environment Toggle -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-[#002056] mb-4">🌐 Environment</h2>
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer transition <?php echo ($settings['env_mode'] ?? '') === 'sandbox' ? 'border-[#002056] bg-blue-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                        <input type="radio" name="env_mode" value="sandbox" <?php echo ($settings['env_mode'] ?? 'sandbox') === 'sandbox' ? 'checked' : ''; ?> class="accent-[#002056]">
                        <div>
                            <p class="font-bold text-gray-900">Sandbox</p>
                            <p class="text-xs text-gray-500">Testing &amp; development</p>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 border-2 rounded-xl p-4 cursor-pointer transition <?php echo ($settings['env_mode'] ?? '') === 'live' ? 'border-[#DA251C] bg-red-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                        <input type="radio" name="env_mode" value="live" <?php echo ($settings['env_mode'] ?? '') === 'live' ? 'checked' : ''; ?> class="accent-[#DA251C]">
                        <div>
                            <p class="font-bold text-gray-900">Live</p>
                            <p class="text-xs text-gray-500">Real payments in production</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- M-Pesa Shared Config -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-[#002056] mb-1">📱 M-Pesa — Shared Config</h2>
                <p class="text-sm text-gray-500 mb-5">These apply regardless of environment.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Business Shortcode</label>
                        <input type="text" name="mpesa_shortcode" value="<?php echo sv($settings, 'mpesa_shortcode'); ?>"
                               placeholder="e.g. 174379"
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Lipa Na M-Pesa Passkey</label>
                        <input type="password" name="mpesa_passkey" value="<?php echo sv($settings, 'mpesa_passkey'); ?>"
                               placeholder="Sandbox or Live Passkey"
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                </div>
            </div>

            <!-- M-Pesa Sandbox -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-[#002056] mb-1">🧪 M-Pesa — Sandbox Credentials</h2>
                <p class="text-sm text-gray-500 mb-5">Used when environment is set to <strong>Sandbox</strong>.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Consumer Key</label>
                        <input type="text" name="mpesa_sandbox_key" value="<?php echo sv($settings, 'mpesa_sandbox_key'); ?>"
                               placeholder="Sandbox Consumer Key"
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Consumer Secret</label>
                        <input type="password" name="mpesa_sandbox_secret" value="<?php echo sv($settings, 'mpesa_sandbox_secret'); ?>"
                               placeholder="Sandbox Consumer Secret"
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                </div>
            </div>

            <!-- M-Pesa Live -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-[#002056] mb-1">🚀 M-Pesa — Live Credentials</h2>
                <p class="text-sm text-gray-500 mb-5">Used when environment is set to <strong>Live</strong>.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Consumer Key</label>
                        <input type="text" name="mpesa_live_key" value="<?php echo sv($settings, 'mpesa_live_key'); ?>"
                               placeholder="Live Consumer Key"
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Consumer Secret</label>
                        <input type="password" name="mpesa_live_secret" value="<?php echo sv($settings, 'mpesa_live_secret'); ?>"
                               placeholder="Live Consumer Secret"
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                </div>
            </div>

            <!-- Paystack -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold text-[#002056] mb-1">💳 Paystack</h2>
                <p class="text-sm text-gray-500 mb-5">Public keys for Paystack integration.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Sandbox Public Key</label>
                        <input type="text" name="paystack_sandbox_key" value="<?php echo sv($settings, 'paystack_sandbox_key'); ?>"
                               placeholder="pk_test_..."
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wide">Live Public Key</label>
                        <input type="text" name="paystack_live_key" value="<?php echo sv($settings, 'paystack_live_key'); ?>"
                               placeholder="pk_live_..."
                               class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#DA251C] hover:bg-[#B31D16] text-white font-bold py-4 rounded-xl transition text-lg shadow-md">
                Save Configuration
            </button>

        </form>
    </div>

</body>
</html>