<?php
require_once('../src/db.php');

// Fetch settings from database to determine visibility of payment methods
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$config = [];
while ($row = $stmt->fetch()) { $config[$row['setting_key']] = $row['setting_value']; }

$hasMpesa = !empty($config['mpesa_shortcode']);
$hasPaystack = !empty($config['paystack_live_key']) || !empty($config['paystack_sandbox_key']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capts. John & Rose Munangwe | Retirement Thanksgiving</title>
    <link href="./css/output.css" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        @keyframes marquee { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }
        .animate-marquee { display: inline-block; animation: marquee 40s linear infinite; }
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
            <h1 class="text-4xl font-bold mb-2">Capts. John & Rose Munangwe</h1>
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
                <div class="flex justify-between text-sm mb-2 text-gray-300"><span>Raised: Kshs. 30,000</span><span>Target: Kshs. 550,000</span></div>
                <div class="w-full bg-white/20 rounded-full h-2"><div class="bg-[#DA251C] h-2 rounded-full" style="width: 5%"></div></div>
            </div>
        </div>

        <!-- RIGHT PANE -->
        <div class="lg:col-span-7 p-6 lg:p-12 flex flex-col">
            <h2 id="contribution-section" class="text-2xl font-bold mb-6 text-[#002056] pt-4">Choose a Contribution Tier</h2>
            
            <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mb-8">
                <div class="grid grid-cols-4 gap-2 mb-6">
                    <button onclick="updateAmount(500)" class="border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">500</button>
                    <button onclick="updateAmount(1000)" class="border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">1k</button>
                    <button onclick="updateAmount(5000)" class="border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">5k</button>
                    <button onclick="updateAmount(10000)" class="border border-gray-300 hover:border-[#002056] py-3 rounded-lg font-bold text-sm transition">10k</button>
                </div>
                <div class="flex border border-gray-300 rounded-lg overflow-hidden mb-6">
                    <div class="bg-gray-100 p-4 font-bold text-gray-500 border-r border-gray-300">Kshs</div>
                    <input type="number" id="contributionAmount" value="500" min="1" class="w-full p-4 font-bold text-2xl outline-none text-[#002056]">
                    <div class="bg-gray-50 p-4 font-bold text-gray-400 border-l border-gray-300">KES</div>
                </div>
                <button onclick="submitDonation()" class="w-full bg-[#DA251C] hover:bg-[#B31D16] text-white font-bold py-4 rounded-xl text-lg transition shadow-md">Continue to Pay</button>
            </div>

            <div class="mb-8 text-center">
                <div class="flex justify-center gap-4">
                    <?php if ($hasMpesa): ?><span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold border border-green-200">M-Pesa Ready</span><?php endif; ?>
                    <?php if ($hasPaystack): ?><span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold border border-blue-200">Paystack Ready</span><?php endif; ?>
                </div>
            </div>

            <button type="button" onclick="toggleModal('downloadModal')" class="block w-full text-center bg-[#002056] text-white py-4 rounded-xl font-bold hover:bg-[#001333] transition mb-10">Download Offline Collection Sheet (PDF)</button>
            
            <div class="flex justify-center opacity-90 mb-8 mt-auto"><img src="assets/logo.png" alt="TSA" class="h-16 mix-blend-multiply"></div>
            
            <div class="bg-white rounded-lg shadow-sm p-4 mb-4 border border-gray-200 overflow-hidden whitespace-nowrap">
                <div class="inline-block animate-marquee text-[#DA251C] font-bold">✅ Capt. Rose & John: 25k &nbsp;⭐&nbsp; ✅ Ian Aluda: 5k (🅿️ 5k) &nbsp;⭐&nbsp; 🅿️ Vinic Nyabuti: 10k ...</div>
            </div>
        </div>
    </div>

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
                        <li>Manyuria</li>
                        <li>Kemondo</li>
                        <li>Number One</li>
                        <li>Kisawayi</li>
                        <li>Yuya</li>
                    </ul>
                </div>

                <p>Two years ago, their journey brought them to <strong>Viyalo Corps</strong> in the Mbale Division. Since arriving, they have poured their hearts into the congregation, leading with the same fire and dedication that defined their early years in the ministry. Their leadership here stands as a beautiful testament to a lifetime of gathered wisdom and enduring love for God’s people.</p>
                
                <p>From guiding young minds in Sunday school to fostering talent in church bands and choirs, the Munangwes have planted seeds of faith that will continue to grow for decades to come.</p>
            </div>
        </div>
    </div>

    <!-- PAYMENT MODAL -->
    <div id="paymentModal" class="fixed inset-0 bg-[#000000]/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 relative shadow-2xl border-t-8 border-[#DA251C]">
            <button onclick="toggleModal('paymentModal')" class="absolute top-4 right-4 text-[#989799] hover:text-[#DA251C] font-bold text-2xl">&times;</button>
            <h2 class="text-2xl font-bold mb-2 text-[#002056]">Complete Contribution</h2>
            <p id="selectedTier" class="text-sm font-bold mb-6 text-gray-500 uppercase tracking-wider"></p>
            
            <form action="../payments/mpesa-stk.php" method="POST" class="space-y-4">
                <input type="hidden" name="tier" id="formTier">
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Amount to Pay (Kshs)</label>
                    <input type="number" id="modalAmount" name="amount" readonly class="w-full border p-3 rounded-lg bg-gray-100 text-[#002056] font-bold outline-none cursor-not-allowed">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Your Name</label>
                    <input type="text" name="name" placeholder="E.g. John Doe" required class="w-full border p-3 rounded-lg outline-none focus:border-[#002056]">
                </div>
                
                <!-- Replace the existing Phone input block in the payment modal -->
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">M-Pesa Phone Number</label>
                    <!-- Now accepts 07XXXXXXXX or 01XXXXXXXX -->
                    <input type="tel" name="phone" placeholder="07XX XXX XXX" required pattern="^(07|01)\d{8}$" title="Must start with 07 or 01 followed by 8 digits" class="w-full border p-3 rounded-lg outline-none focus:border-[#DA251C]">
                    <p class="text-xs text-gray-400 mt-1">Format: 07... or 01...</p>
                </div>

                <button type="submit" class="w-full bg-[#DA251C] text-white font-bold py-4 rounded-lg hover:bg-[#B31D16] shadow-md transition mt-2">Initiate M-Pesa Payment</button>
            </form>
        </div>
    </div>

    <!-- DOWNLOAD MODAL -->
    <div id="downloadModal" class="fixed inset-0 bg-[#000000]/80 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-8 relative shadow-2xl border-t-8 border-[#002056]">
            <button onclick="toggleModal('downloadModal')" class="absolute top-4 right-4 text-[#989799] hover:text-[#DA251C] font-bold text-2xl">&times;</button>
            <h2 class="text-2xl font-bold mb-6 text-[#002056]">Collection Sheet</h2>
            <form action="download-pdf.php" method="POST" target="_blank" onsubmit="setTimeout(() => toggleModal('downloadModal'), 1000)" class="space-y-4">
                <input type="text" name="organizer_name" required placeholder="Organizer Full Name" class="w-full border p-3 rounded-lg">
                <input type="tel" name="organizer_phone" required placeholder="Phone Number" class="w-full border p-3 rounded-lg">
                <input type="number" name="organizer_pledge" required placeholder="Your Initial Pledge (Kshs)" class="w-full border p-3 rounded-lg">
                <button type="submit" class="w-full bg-[#002056] text-white font-bold py-4 rounded-lg hover:bg-[#001333] transition">Generate PDF Sheet</button>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(id) { 
            document.getElementById(id).classList.toggle('hidden'); 
        }
        
        function updateAmount(val) { 
            document.getElementById('contributionAmount').value = val; 
        }
        
        function submitDonation() { 
            const amount = parseInt(document.getElementById('contributionAmount').value);
            if (!amount || amount <= 0) {
                alert("Please enter a valid contribution amount.");
                return;
            }
            openPayment('Thanksgiving Contribution', amount); 
        }
        
        function openPayment(tierName, amount) {
            document.getElementById('selectedTier').innerText = "Tier: " + tierName;
            document.getElementById('formTier').value = tierName;
            
            // Set the read-only amount field in the payment modal
            document.getElementById('modalAmount').value = amount;
            
            toggleModal('paymentModal');
        }
    </script>
</body>
</html>