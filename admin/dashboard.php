<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require '../src/db.php';

$flashMessage = '';
$flashType = 'success';

// ── Handle offline contributor actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_contributor') {
        $name = trim($_POST['name'] ?? '');
        $amountPaid = max(0, (float)($_POST['amount_paid'] ?? 0));
        $amountPledged = max(0, (float)($_POST['amount_pledged'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);

        if ($name === '') {
            $flashMessage = 'Contributor name is required.';
            $flashType = 'error';
        } elseif ($amountPaid <= 0 && $amountPledged <= 0) {
            $flashMessage = 'Enter at least a paid or pledged amount.';
            $flashType = 'error';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO public_contributors (name, amount_paid, amount_pledged, notes, display_order)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $amountPaid, $amountPledged, $notes ?: null, $displayOrder]);
            $flashMessage = 'Contributor added successfully.';
        }
    }

    if ($action === 'update_contributor') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $amountPaid = max(0, (float)($_POST['amount_paid'] ?? 0));
        $amountPledged = max(0, (float)($_POST['amount_pledged'] ?? 0));
        $notes = trim($_POST['notes'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
        $isVisible = isset($_POST['is_visible']) ? 1 : 0;

        if ($id <= 0 || $name === '') {
            $flashMessage = 'Invalid contributor data.';
            $flashType = 'error';
        } elseif ($amountPaid <= 0 && $amountPledged <= 0) {
            $flashMessage = 'Enter at least a paid or pledged amount.';
            $flashType = 'error';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE public_contributors
                 SET name = ?, amount_paid = ?, amount_pledged = ?, notes = ?, display_order = ?, is_visible = ?
                 WHERE id = ?"
            );
            $stmt->execute([$name, $amountPaid, $amountPledged, $notes ?: null, $displayOrder, $isVisible, $id]);
            $flashMessage = 'Contributor updated successfully.';
        }
    }

    if ($action === 'delete_contributor') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM public_contributors WHERE id = ?");
            $stmt->execute([$id]);
            $flashMessage = 'Contributor removed.';
        }
    }
}

// ── Analytics ────────────────────────────────────────────────────────────────

// 1. Total Successful Digital Contributions
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM contributions WHERE status = 'SUCCESS'");
$totalDigital = $stmt->fetch()['total'];

// 2. Total Pending Offline Pledges
$stmt = $pdo->query("SELECT COALESCE(SUM(pledge_amount), 0) as total FROM collection_sheets WHERE is_remitted = 0");
$totalPendingPledges = $stmt->fetch()['total'];

// 3. Count of offline sheets distributed
$stmt = $pdo->query("SELECT COUNT(*) as count FROM collection_sheets");
$sheetsDownloaded = $stmt->fetch()['count'];

// 4. Recent Contributions (last 15)
$stmt = $pdo->query(
    "SELECT name, phone, amount, payment_method, status, transaction_id, created_at
     FROM contributions
     ORDER BY created_at DESC
     LIMIT 15"
);
$recentContributions = $stmt->fetchAll();

// 5. Public contributors (offline payments shown on homepage)
$stmt = $pdo->query(
    "SELECT id, name, amount_paid, amount_pledged, notes, display_order, is_visible, updated_at
     FROM public_contributors
     ORDER BY display_order ASC, id ASC"
);
$publicContributors = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM public_contributors WHERE is_visible = 1");
$totalOfflineRaised = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(amount_pledged), 0) as total FROM public_contributors WHERE is_visible = 1");
$totalOfflinePledged = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | My Proforma</title>
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
                    <a href="add_user.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm font-bold transition">+ Add User</a>
                    <span class="text-sm text-gray-300">Hello, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                    <a href="logout.php" class="bg-white/10 hover:bg-white/20 px-4 py-2 rounded-lg text-sm font-bold transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header -->
        <div class="mb-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Campaign Overview</h1>
                <p class="text-gray-500 mt-1">Real-time statistics for Capts. John &amp; Rose's retirement.</p>
            </div>
            <a href="settings.php" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm">
                ⚙️ API Settings
            </a>
        </div>

        <?php if ($flashMessage): ?>
            <div class="<?php echo $flashType === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-green-50 border-green-200 text-green-800'; ?> border p-4 rounded-xl mb-6 font-bold">
                <?php echo htmlspecialchars($flashMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Analytics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 border-l-8 border-l-[#002056]">
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Digital Funds Collected</h3>
                <p class="text-3xl font-black text-gray-900">Kshs <?php echo number_format($totalDigital); ?></p>
                <p class="text-xs text-gray-400 mt-1">Confirmed M-Pesa payments</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 border-l-8 border-l-[#DA251C]">
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Pending Offline Pledges</h3>
                <p class="text-3xl font-black text-gray-900">Kshs <?php echo number_format($totalPendingPledges); ?></p>
                <p class="text-xs text-gray-400 mt-1">From unremitted collection sheets</p>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 border-l-8 border-l-green-600">
                <h3 class="text-gray-500 text-sm font-bold uppercase tracking-wider mb-2">Collection Sheets Issued</h3>
                <p class="text-3xl font-black text-gray-900"><?php echo $sheetsDownloaded; ?> <span class="text-lg font-bold text-gray-400">Organizers</span></p>
                <p class="text-xs text-gray-400 mt-1">PDFs downloaded by organizers</p>
            </div>
        </div>

        <!-- Offline Contributors Management -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold text-[#002056]">Offline Contributors</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage names shown on the public contributors table for offline payments and pledges.</p>
                </div>
                <div class="text-sm text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2">
                    <span class="font-bold text-gray-900">Kshs <?php echo number_format($totalOfflineRaised); ?></span> paid
                    &middot;
                    <span class="font-bold text-gray-900">Kshs <?php echo number_format($totalOfflinePledged); ?></span> pledged
                </div>
            </div>

            <!-- Add new contributor -->
            <form method="POST" class="bg-gray-50 border border-gray-200 rounded-xl p-5 mb-6">
                <input type="hidden" name="action" value="add_contributor">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4">Add Offline Contributor</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">
                    <input type="text" name="name" required placeholder="Full name"
                           class="lg:col-span-2 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#002056]">
                    <input type="number" name="amount_paid" min="0" step="1" placeholder="Amount paid"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#002056]">
                    <input type="number" name="amount_pledged" min="0" step="1" placeholder="Amount pledged"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#002056]">
                    <input type="text" name="notes" placeholder="Notes (optional)"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#002056]">
                    <input type="number" name="display_order" min="0" step="1" placeholder="Order"
                           class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#002056]">
                </div>
                <button type="submit" class="mt-4 bg-[#002056] hover:bg-[#001333] text-white font-bold px-5 py-2 rounded-lg text-sm transition">
                    + Add Contributor
                </button>
            </form>

            <?php if (empty($publicContributors)): ?>
                <div class="text-center py-10 text-gray-400">
                    <p class="font-bold text-gray-500">No offline contributors yet</p>
                    <p class="text-sm mt-1">Add contributors above when offline payments are received.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <div class="min-w-[900px]">
                        <div class="grid grid-cols-7 gap-2 border-b-2 border-gray-100 pb-2 mb-2">
                            <div class="text-left px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Name</div>
                            <div class="text-right px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Paid</div>
                            <div class="text-right px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Pledged</div>
                            <div class="text-left px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Notes</div>
                            <div class="text-center px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Order</div>
                            <div class="text-center px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Visible</div>
                            <div class="text-right px-2 text-gray-500 font-bold uppercase tracking-wide text-xs">Actions</div>
                        </div>
                        <?php foreach ($publicContributors as $row): ?>
                        <form method="POST" class="grid grid-cols-7 gap-2 items-center py-2 border-b border-gray-50 hover:bg-gray-50 rounded-lg">
                            <input type="hidden" name="action" value="update_contributor">
                            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>" required
                                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-[#002056]">
                            <input type="number" name="amount_paid" min="0" step="1" value="<?php echo (float)$row['amount_paid']; ?>"
                                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:border-[#002056]">
                            <input type="number" name="amount_pledged" min="0" step="1" value="<?php echo (float)$row['amount_pledged']; ?>"
                                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-right focus:outline-none focus:border-[#002056]">
                            <input type="text" name="notes" value="<?php echo htmlspecialchars($row['notes'] ?? ''); ?>"
                                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:border-[#002056]">
                            <input type="number" name="display_order" min="0" step="1" value="<?php echo (int)$row['display_order']; ?>"
                                   class="border border-gray-300 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:border-[#002056]">
                            <div class="text-center">
                                <input type="checkbox" name="is_visible" value="1" <?php echo $row['is_visible'] ? 'checked' : ''; ?>
                                       class="w-4 h-4 rounded border-gray-300 text-[#002056]">
                            </div>
                            <div class="flex justify-end gap-2">
                                <button type="submit" class="bg-[#DA251C] hover:bg-[#B31D16] text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                    Save
                                </button>
                                <button type="submit" formaction="dashboard.php" formmethod="POST" name="action" value="delete_contributor"
                                        onclick="return confirm('Remove this contributor from the public list?');"
                                        class="bg-gray-200 hover:bg-red-100 hover:text-red-700 text-gray-700 font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                    Delete
                                </button>
                            </div>
                        </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold mb-5 text-[#002056]">Recent Contributions</h2>

            <?php if (empty($recentContributions)): ?>
                <div class="text-center py-12 text-gray-400">
                    <div class="text-5xl mb-3">💳</div>
                    <p class="font-bold text-gray-500">No contributions yet</p>
                    <p class="text-sm mt-1">Payments will appear here once users begin contributing.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-gray-100">
                                <th class="text-left py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Name</th>
                                <th class="text-left py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Phone</th>
                                <th class="text-right py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Amount</th>
                                <th class="text-center py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Method</th>
                                <th class="text-center py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Status</th>
                                <th class="text-left py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Receipt</th>
                                <th class="text-left py-3 px-3 text-gray-500 font-bold uppercase tracking-wide text-xs">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach ($recentContributions as $row): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-3 px-3 font-semibold"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="py-3 px-3 text-gray-600 font-mono text-xs"><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td class="py-3 px-3 text-right font-bold text-gray-900">Kshs <?php echo number_format($row['amount']); ?></td>
                                <td class="py-3 px-3 text-center">
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($row['payment_method']); ?></span>
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <?php
                                        $statusColors = [
                                            'SUCCESS' => 'bg-green-100 text-green-800',
                                            'PENDING' => 'bg-yellow-100 text-yellow-800',
                                            'FAILED'  => 'bg-red-100 text-red-800',
                                        ];
                                        $cls = $statusColors[$row['status']] ?? 'bg-gray-100 text-gray-600';
                                    ?>
                                    <span class="<?php echo $cls; ?> text-xs font-bold px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($row['status']); ?></span>
                                </td>
                                <td class="py-3 px-3 font-mono text-xs text-gray-500"><?php echo htmlspecialchars($row['transaction_id'] ?? '—'); ?></td>
                                <td class="py-3 px-3 text-gray-500 text-xs whitespace-nowrap"><?php echo date('d M Y, H:i', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>