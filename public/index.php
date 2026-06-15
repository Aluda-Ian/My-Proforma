<?php
require_once('../src/db.php');

// Fetch settings from database to determine visibility of payment methods
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$config = [];
while ($row = $stmt->fetch()) { $config[$row['setting_key']] = $row['setting_value']; }

$hasMpesa    = !empty($config['mpesa_sandbox_key']) || !empty($config['mpesa_live_key']);
$hasPaystack = !empty($config['paystack_sandbox_key']) || !empty($config['paystack_live_key']);

$campaignTarget = (int)($config['campaign_target'] ?? 550000);

$stmt = $pdo->query(
    "SELECT name, amount_paid, amount_pledged, notes
     FROM public_contributors
     WHERE is_visible = 1
     ORDER BY display_order ASC, id ASC"
);
$contributors = $stmt->fetchAll();

$totalRaised = 0;
foreach ($contributors as $c) {
    $totalRaised += (float)$c['amount_paid'];
}

$progressPct = $campaignTarget > 0 ? min(100, round(($totalRaised / $campaignTarget) * 100, 1)) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capts. John &amp; Rose Munangwe | Retirement Thanksgiving</title>
    <link href="./css/output.css" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        /* Spinner */
        @keyframes spin { to { transform: rotate(360deg); } }
        .spinner {
            display: inline-block;
            width: 18px; height: 18px;
            border: 3px solid rgba(255,255,255,0.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
            vertical-align: middle;
            margin-right: 8px;
        }
    </style>
</head>
<body class="bg-gray-50 text-[#000000] font-sans antialiased">

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-12">
        <!-- LEFT PANE -->
        <div class="lg:col-span-5 bg-[#002056] text-white p-10 lg:sticky lg:top-0 lg:h-screen flex flex-col justify-center items-center text-center border-r-8 border-[#DA251C] relative">
            <div class="absolute top-6 left-6 lg:top-8 lg:left-8">
                <span class="bg-[#DA251C] text-white px-4 py-1.5 rounded-full text-xs font-bold tracking-widest uppercase shadow-md">My Proforma</span>
            </div>
            <div class="w-48 h-48 bg-white/10 rounded-[2rem] mb-6 overflow-hidden border-4 border-white mt-8 lg:mt-0 shadow-lg">
                <img src="assets/john and rose.png" alt="Capts. John and Rose" class="w-full h-full object-cover">
            </div>
            <h1 class="text-4xl font-bold mb-2">Capts. John &amp; Rose Munangwe</h1>
            <p class="text-white text-lg mb-6 tracking-widest uppercase font-bold opacity-90">Retirement Thanksgiving</p>
            <p class="text-gray-300 mb-6 max-w-sm">Join us in celebrating 26 years of faithful service to the Salvation Army. Event: Nov 8, 2026 at Viyalo Corps.</p>
            <div class="flex flex-col w-full max-w-sm gap-4 mb-8">
                <a href="#contribution-section" class="lg:hidden block bg-white text-[#002056] py-3 px-8 rounded-full font-bold shadow-lg border-2 border-white">Support the Cause 👇</a>
                <button onclick="toggleModal('historyModal')" class="bg-[#DA251C] hover:bg-[#B31D16] text-white py-3 px-8 rounded-full font-bold transition">Read Their History</button>
            </div>
            <div class="bg-white/10 p-4 rounded-xl border border-white/20 w-full max-w-sm mb-8 backdrop-blur-sm shadow-inner">
                <h3 class="text-white font-bold mb-1 text-lg">🙏 Thank You!</h3>
                <div class="flex justify-center gap-6 text-sm font-bold text-white bg-[#001333] py-2 rounded-lg">
                    <span>✅ = Paid</span> <span>🅿️ = Pledged</span>
                </div>
            </div>
            <div class="w-full max-w-sm">
                <div class="flex justify-between text-sm mb-2 text-gray-300">
                    <span>Raised: Kshs. <?php echo number_format($totalRaised); ?></span>
                    <span>Target: Kshs. <?php echo number_format($campaignTarget); ?></span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-2">
                    <div class="bg-[#DA251C] h-2 rounded-full" style="width: <?php echo $progressPct; ?>%"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANE -->
        <div class="lg:col-span-7 p-6 lg:p-12 flex flex-col">

            <button type="button" onclick="toggleModal('downloadModal')" class="block w-full text-center bg-[#002056] text-white py-4 rounded-xl font-bold hover:bg-[#001333] transition mb-10">Download Offline Collection Sheet (PDF)</button>
            
            <h2 id="contribution-section" class="text-2xl font-bold mb-6 text-[#002056] pt-4">Choose a Contribution Tier or enter your own amount</h2>
            
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mb-8">
                <div class="grid grid-cols-4 gap-2 mb-6">
                    <button onclick="updateAmount(500)"   class="tier-btn border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">500</button>
                    <button onclick="updateAmount(1000)"  class="tier-btn border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">1k</button>
                    <button onclick="updateAmount(5000)"  class="tier-btn border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">5k</button>
                    <button onclick="updateAmount(10000)" class="tier-btn border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">10k</button>
                </div>
                <div class="flex border border-gray-300 rounded-lg overflow-hidden mb-6">
                    <div class="bg-gray-100 p-4 font-bold text-gray-500 border-r border-gray-300">Kshs</div>
                    <input type="number" id="contributionAmount" value="500" min="1" class="w-full p-4 font-bold text-2xl outline-none text-[#002056]">
                    <div class="bg-gray-50 p-4 font-bold text-gray-400 border-l border-gray-300">KES</div>
                </div>
                <button onclick="submitDonation()" class="w-full bg-[#DA251C] hover:bg-[#B31D16] text-white font-bold py-4 rounded-xl text-lg transition shadow-md">Continue to Pay</button>
            </div>

            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 mb-8 overflow-hidden">
                <div class="bg-[#002056] text-white pl-10 pr-6 py-4 px-8">
                    <h2 class="text-xl font-bold">People Who Have Contributed</h2>
                    <p class="text-sm text-gray-300 mt-1">Thank you to everyone supporting this cause.</p>
                </div>

                <?php if (empty($contributors)): ?>
                    <div class="text-center py-10 text-gray-400 px-6">
                        <p class="font-bold text-gray-500">No contributions recorded yet.</p>
                        <p class="text-sm mt-1">Be the first to support the Munangwes!</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-gray-100 bg-gray-50">
                                    <th class="text-left py-3 px-4 text-gray-500 font-bold uppercase tracking-wide text-xs">Name</th>
                                    <th class="text-right py-3 px-4 text-gray-500 font-bold uppercase tracking-wide text-xs">Paid</th>
                                    <th class="text-right py-3 px-4 text-gray-500 font-bold uppercase tracking-wide text-xs">Pledged</th>
                                    <th class="text-center py-3 px-4 text-gray-500 font-bold uppercase tracking-wide text-xs">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($contributors as $row):
                                    $paid    = (float)$row['amount_paid'];
                                    $pledged = (float)$row['amount_pledged'];
                                    if ($paid > 0 && $pledged > 0) {
                                        $status = '✅ Paid + 🅿️ Pledged';
                                        $statusCls = 'bg-blue-100 text-blue-800';
                                    } elseif ($paid > 0) {
                                        $status = '✅ Paid';
                                        $statusCls = 'bg-green-100 text-green-800';
                                    } else {
                                        $status = '🅿️ Pledged';
                                        $statusCls = 'bg-yellow-100 text-yellow-800';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-3 px-4 font-semibold text-[#002056]">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                        <?php if (!empty($row['notes'])): ?>
                                            <span class="block text-xs text-gray-400 font-normal mt-0.5"><?php echo htmlspecialchars($row['notes']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right font-bold text-gray-900">
                                        <?php echo $paid > 0 ? 'Kshs ' . number_format($paid) : '—'; ?>
                                    </td>
                                    <td class="py-3 px-4 text-right text-gray-600">
                                        <?php echo $pledged > 0 ? 'Kshs ' . number_format($pledged) : '—'; ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="<?php echo $statusCls; ?> text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap"><?php echo $status; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex justify-center opacity-90 mb-8 mt-auto"><img src="assets/logo.png" alt="TSA" class="h-16 mix-blend-multiply"></div>
        </div>
    </div>

    <!-- ===================== MODALS ===================== -->

    <!-- HISTORY MODAL -->
    <div id="historyModal" class="fixed inset-0 bg-[#000000]/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto p-12 relative shadow-2xl">
            <button onclick="toggleModal('historyModal')" class="absolute top-6 right-6 text-[#989799] hover:text-[#DA251C] font-bold text-3xl">&times;</button>
            <h2 class="text-4xl font-black mb-8 text-[#002056]">The Journey of Service</h2>
            <div class="space-y-8 text-lg text-gray-700 leading-relaxed">
                <p>Capts. John and Rose Munangwe have dedicated over 26 years to the ministry of the Salvation Army, touching countless lives as pillars of strength and faith. Their journey has taken them across many communities, where they didn't just lead churches—they built families.</p>
                <div class="bg-gray-50 p-6 rounded-xl border-l-4 border-[#002056]">
                    <h3 class="text-xl font-bold text-[#002056] mb-4">A Legacy Across Divisions</h3>
                    <p>Their ministry has been marked by resilience and unwavering commitment, serving faithfully at:</p>
                    <ul class="list-disc ml-6 mt-2 space-y-1">
                        <li>Manyuria</li><li>Kemondo</li><li>Number One</li><li>Kisawayi</li><li>Yuya</li>
                    </ul>
                </div>
                <p>Two years ago, their journey brought them to <strong>Viyalo Corps</strong> in the Mbale Division. Since arriving, they have poured their hearts into the congregation, leading with the same fire and dedication that defined their early years in the ministry.</p>
                <p>From guiding young minds in Sunday school to fostering talent in church bands and choirs, the Munangwes have planted seeds of faith that will continue to grow for decades to come.</p>
            </div>
        </div>
    </div>

    <!-- PAYMENT MODAL -->
    <div id="paymentModal" class="fixed inset-0 bg-[#000000]/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 relative shadow-2xl border-t-8 border-[#DA251C]">
            <button onclick="closePaymentModal()" class="absolute top-4 right-4 text-[#989799] hover:text-[#DA251C] font-bold text-2xl">&times;</button>
            <h2 class="text-2xl font-bold mb-1 text-[#002056]">Complete Your Contribution</h2>
            <p id="paymentAmountLabel" class="font-bold mb-5 text-gray-500"></p>

            <div id="paymentError" class="hidden bg-red-50 border border-red-200 text-red-700 text-sm font-bold p-3 rounded-lg mb-4"></div>

            <div class="space-y-4">
                <input type="text"  id="payName"  placeholder="Your Full Name"    class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                <input type="tel"   id="payPhone" placeholder="07XX XXX XXX"      class="w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                <button id="paySubmitBtn" onclick="initiatePayment()" class="w-full bg-[#DA251C] hover:bg-[#B31D16] text-white font-bold py-4 rounded-lg transition text-lg">
                    Pay via M-Pesa
                </button>
            </div>
        </div>
    </div>

    <!-- SUCCESS MODAL -->
    <div id="successModal" class="fixed inset-0 bg-[#000000]/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-sm w-full p-10 relative shadow-2xl text-center border-t-8 border-green-500">
            <div class="text-6xl mb-4">📲</div>
            <h2 class="text-2xl font-bold mb-3 text-green-700">Check Your Phone!</h2>
            <p id="successMessage" class="text-gray-600 mb-6"></p>
            <button onclick="toggleModal('successModal')" class="w-full bg-[#002056] text-white font-bold py-3 rounded-lg">Done</button>
        </div>
    </div>

    <!-- DOWNLOAD MODAL -->
    <div id="downloadModal" class="fixed inset-0 bg-[#000000]/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 relative shadow-2xl border-t-8 border-[#002056]">
            <button onclick="toggleModal('downloadModal')" class="absolute top-4 right-4 text-[#989799] hover:text-[#DA251C] font-bold text-2xl">&times;</button>
            <h2 class="text-xl font-bold mb-5 text-[#002056]">Download Collection Sheet</h2>
            <form action="download-pdf.php" method="POST" target="_blank" onsubmit="setTimeout(() => toggleModal('downloadModal'), 1000)" class="space-y-4">
                <input type="text"   name="organizer_name"   required placeholder="Full Name"                class="w-full border p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                <input type="tel"    name="organizer_phone"  required placeholder="Phone Number"             class="w-full border p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                <input type="number" name="organizer_pledge" required placeholder="Initial Pledge (Kshs)"   class="w-full border p-3 rounded-lg focus:outline-none focus:border-[#002056]">
                <button type="submit" class="w-full bg-[#002056] text-white font-bold py-4 rounded-lg hover:bg-[#001333] transition">Generate PDF Sheet</button>
            </form>
        </div>
    </div>

    <script>
        // ── Helpers ──────────────────────────────────────────────────────────────
        function toggleModal(id) {
            document.getElementById(id).classList.toggle('hidden');
        }

        function updateAmount(val) {
            document.getElementById('contributionAmount').value = val;
        }

        function closePaymentModal() {
            toggleModal('paymentModal');
            // Reset form state
            document.getElementById('payName').value = '';
            document.getElementById('payPhone').value = '';
            document.getElementById('paymentError').classList.add('hidden');
            resetPayBtn();
        }

        function resetPayBtn() {
            const btn = document.getElementById('paySubmitBtn');
            btn.disabled = false;
            btn.innerHTML = 'Pay via M-Pesa';
        }

        // ── Open payment modal ────────────────────────────────────────────────────
        function submitDonation() {
            const amount = parseInt(document.getElementById('contributionAmount').value);
            if (!amount || amount < 1) {
                alert('Please enter a valid amount.');
                return;
            }
            document.getElementById('paymentAmountLabel').innerText =
                'Amount: Kshs ' + amount.toLocaleString();
            document.getElementById('paymentModal').dataset.amount = amount;
            document.getElementById('paymentError').classList.add('hidden');
            toggleModal('paymentModal');
        }

        // ── Submit payment via AJAX ───────────────────────────────────────────────
        async function initiatePayment() {
            const name   = document.getElementById('payName').value.trim();
            const phone  = document.getElementById('payPhone').value.trim();
            const amount = document.getElementById('paymentModal').dataset.amount;
            const errBox = document.getElementById('paymentError');
            const btn    = document.getElementById('paySubmitBtn');

            // Client-side validation
            if (!name) { showPayError('Please enter your full name.'); return; }
            if (!phone) { showPayError('Please enter your phone number.'); return; }

            // Loading state
            errBox.classList.add('hidden');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Processing...';

            try {
                const formData = new FormData();
                formData.append('name',   name);
                formData.append('phone',  phone);
                formData.append('amount', amount);

                const res  = await fetch('../payments/mpesa-stk.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    // Close payment modal, show success modal
                    toggleModal('paymentModal');
                    document.getElementById('successMessage').innerText = data.message;
                    toggleModal('successModal');
                    resetPayBtn();
                } else {
                    showPayError(data.message);
                    resetPayBtn();
                }
            } catch (err) {
                showPayError('A network error occurred. Please check your connection and try again.');
                resetPayBtn();
            }
        }

        function showPayError(msg) {
            const errBox = document.getElementById('paymentError');
            errBox.innerText = msg;
            errBox.classList.remove('hidden');
        }
    </script>
</body>
</html>