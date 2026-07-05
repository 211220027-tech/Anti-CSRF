<?php
session_start();

// Inisialisasi session default
if (!isset($_SESSION['balance'])) {
    $_SESSION['balance'] = 50000000;
}
if (!isset($_SESSION['mode'])) {
    $_SESSION['mode'] = 'vulnerable';
}

// Generate CSRF Token jika mode aman
if ($_SESSION['mode'] === 'secure' && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notification = null;
$error_type = ''; 

// Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'reset') {
            $_SESSION['balance'] = 50000000;
            $notification = ['type' => 'success', 'message' => 'Saldo berhasil direset.'];
        } 
        elseif ($action === 'set_mode') {
            $_SESSION['mode'] = $_POST['mode'];
            if ($_SESSION['mode'] === 'secure') {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            // Redirect to remove POST state
            header("Location: bank.php");
            exit;
        }
        elseif ($action === 'transfer') {
            $amount = isset($_POST['amount']) ? (int) preg_replace('/\D/', '', $_POST['amount']) : 0;
            $destination = isset($_POST['destination']) ? $_POST['destination'] : '';
            
            // Validasi CSRF Token di Mode Aman
            if ($_SESSION['mode'] === 'secure') {
                if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                    $notification = ['type' => 'error', 'message' => '⛔ ERROR: SERANGAN CSRF TERDETEKSI!'];
                    $error_type = 'csrf';
                }
            }

            if (!$error_type) {
                if ($amount > $_SESSION['balance']) {
                    $notification = ['type' => 'error', 'message' => 'Saldo tidak mencukupi.'];
                    $error_type = 'general';
                } elseif ($amount > 0) {
                    $_SESSION['balance'] -= $amount;
                    $notification = ['type' => 'success', 'message' => 'Transfer Rp ' . number_format($amount, 0, ',', '.') . ' berhasil!'];
                }
            }
        }
    }
}

$balance = $_SESSION['balance'];
$isVuln = $_SESSION['mode'] === 'vulnerable';
$csrf_token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '';

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'cara_kerja';

// Jika terjadi error CSRF, tampilkan halaman putih (Simulasi die())
if ($error_type === 'csrf') {
    echo '<!DOCTYPE html><html><head><title>CSRF Error</title><script src="https://cdn.tailwindcss.com"></script></head>';
    echo '<body class="bg-white flex flex-col p-8 items-start justify-start">';
    echo '<div class="font-mono text-lg font-bold text-slate-900">' . $notification['message'] . '</div>';
    echo '<a href="bank.php" class="mt-8 text-sm font-sans font-medium text-blue-600 hover:underline">← Kembali ke Halaman Demo</a>';
    echo '</body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSRF Exploit & Patch Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen bg-[#fcf9f9] text-slate-900 font-sans p-4 md:p-8 selection:bg-red-200">

    <?php if ($notification && $notification['type'] === 'success'): ?>
    <div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-6 py-4 rounded-xl shadow-2xl font-bold flex items-center gap-3 bg-white border border-emerald-200 text-emerald-600">
        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
        <span class="text-lg"><?= htmlspecialchars($notification['message']) ?></span>
        <button onclick="this.parentElement.style.display='none'" class="ml-4 text-gray-400 hover:text-gray-600">&times;</button>
    </div>
    <?php endif; ?>
    
    <?php if ($notification && $notification['type'] === 'error'): ?>
    <div class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-6 py-4 rounded-xl shadow-2xl font-bold flex items-center gap-3 bg-white border border-red-200 text-red-600">
        <i data-lucide="alert-triangle" class="w-6 h-6"></i>
        <span class="text-lg"><?= htmlspecialchars($notification['message']) ?></span>
        <button onclick="this.parentElement.style.display='none'" class="ml-4 text-gray-400 hover:text-gray-600">&times;</button>
    </div>
    <?php endif; ?>

    <div class="max-w-5xl mx-auto pb-12">
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 text-red-600 text-xs font-bold border border-red-100 mb-4 tracking-wider">
                <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> PERTEMUAN 11 — PJBL OBE
            </div>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4 tracking-tight">
                CSRF <span class="text-red-500">Exploit</span> & <span class="text-teal-600">Patch</span> Demo
            </h1>
            <p class="text-slate-500 text-sm md:text-base max-w-2xl mx-auto mb-6">
                Platform demonstrasi interaktif simulasi serangan <strong>Cross-Site Request Forgery</strong> pada<br/>
                <strong>Web Bank Rentan</strong> vs <strong>Web Bank Aman</strong> dengan <code class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded text-sm font-mono border border-slate-200">bin2hex(random_bytes(32))</code>.
            </p>
            
            <div class="flex flex-wrap justify-center gap-4 text-sm font-medium text-slate-500">
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="reset">
                    <button type="submit" class="flex items-center gap-1.5 hover:text-slate-800 transition-colors">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> Reset Saldo
                    </button>
                </form>
            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-4 mb-8">
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="set_mode">
                <input type="hidden" name="mode" value="vulnerable">
                <button type="submit" class="px-6 py-2.5 rounded-full font-bold flex items-center gap-2 transition-all <?= $isVuln ? 'bg-red-500 text-white shadow-lg' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
                    <i data-lucide="shield-alert" class="w-[18px] h-[18px]"></i> Mode Rentan (Hack)
                </button>
            </form>
            
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="set_mode">
                <input type="hidden" name="mode" value="secure">
                <button type="submit" class="px-6 py-2.5 rounded-full font-bold flex items-center gap-2 transition-all <?= !$isVuln ? 'bg-teal-600 text-white shadow-lg' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' ?>">
                    <i data-lucide="shield-check" class="w-[18px] h-[18px]"></i> Mode Aman (Patch)
                </button>
            </form>
        </div>

        <?php if ($isVuln): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-xl mb-8 flex items-start gap-3 shadow-sm mx-auto">
                <i data-lucide="alert-triangle" class="shrink-0 mt-0.5"></i>
                <div>
                    <h3 class="font-bold">⚠️ MODE RENTAN AKTIF — Simulasi Bank Tanpa Perlindungan CSRF</h3>
                    <p class="text-sm mt-1 text-red-600">Form transfer ini <strong>tidak memverifikasi asal request</strong>. Gunakan panel <em>Simulasi Hacker</em> di bawah untuk melihat bagaimana saldo bisa dicuri dari web lain.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl mb-8 flex items-start gap-3 shadow-sm mx-auto">
                <i data-lucide="check-circle-2" class="shrink-0 mt-0.5"></i>
                <div>
                    <h3 class="font-bold">✅ MODE AMAN AKTIF — Mitigasi Anti-CSRF Token Aktif</h3>
                    <p class="text-sm mt-1 text-emerald-700">Setiap transfer memerlukan Token kriptografis rahasia yang ditanam di form. Coba gunakan panel <em>Simulasi Hacker</em> — request akan ditolak!</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
            
            <!-- KIRI: BANK DAN HACKER -->
            <div class="space-y-6">
                
                <!-- KARTU BANK -->
                <div class="flex flex-col rounded-2xl shadow-xl overflow-hidden border border-gray-100 bg-white">
                    <div class="<?= $isVuln ? 'bg-red-600' : 'bg-teal-700' ?> p-6 relative overflow-hidden transition-colors duration-300">
                        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 rounded-full bg-white opacity-5"></div>
                        <div class="absolute bottom-0 right-0 -mb-16 -mr-16 w-48 h-48 rounded-full bg-white opacity-5"></div>
                        
                        <div class="flex justify-between items-start relative z-10">
                            <div>
                                <p class="text-white/80 text-xs font-bold tracking-wider mb-1">NASABAH</p>
                                <h2 class="text-2xl font-bold text-white mb-6">Bapak Budi</h2>
                                
                                <p class="text-white/80 text-xs font-bold tracking-wider mb-1">SALDO REKENING</p>
                                <h1 class="text-4xl font-black text-white tracking-tight">Rp <?= number_format($balance, 0, ',', '.') ?></h1>
                            </div>
                            <div class="<?= $isVuln ? 'bg-red-700' : 'bg-teal-800' ?> p-3 rounded-xl transition-colors duration-300">
                                <i data-lucide="landmark" class="text-white w-[28px] h-[28px]"></i>
                            </div>
                        </div>
                        
                        <div class="mt-8 flex items-center gap-2 text-white/90 text-sm font-medium">
                            <span class="w-2.5 h-2.5 rounded-full <?= $isVuln ? 'bg-red-300' : 'bg-teal-300' ?> animate-pulse"></span>
                            Sesi Aktif — <?= $isVuln ? 'Tidak Terproteksi' : 'Dilindungi Anti-CSRF Token' ?>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold flex items-center gap-2 text-gray-800">
                                <i data-lucide="send" class="w-[18px] h-[18px] text-gray-400"></i> Form Transfer Dana
                            </h3>
                            <?php if (!$isVuln): ?>
                                <span class="text-xs font-bold bg-emerald-100 text-emerald-700 px-2 py-1 rounded flex items-center gap-1">
                                    <i data-lucide="check" class="w-[14px] h-[14px]"></i> Token Aktif
                                </span>
                            <?php else: ?>
                                <span class="text-xs font-bold bg-red-100 text-red-700 px-2 py-1 rounded flex items-center gap-1">
                                    <i data-lucide="alert-triangle" class="w-[14px] h-[14px]"></i> Tanpa Token
                                </span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="bank.php" class="space-y-4">
                            <input type="hidden" name="action" value="transfer">
                            
                            <!-- HIDDEN CSRF TOKEN FOR DOM INSPECTION -->
                            <?php if (!$isVuln): ?>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <?php endif; ?>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">NO. REKENING TUJUAN</label>
                                <input 
                                    type="text" 
                                    name="destination"
                                    placeholder="cth: 1234-5678-9012" 
                                    class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-gray-50 text-gray-800"
                                    required
                                >
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">JUMLAH TRANSFER (RP)</label>
                                <input 
                                    type="text" 
                                    name="amount"
                                    placeholder="cth: 1000000" 
                                    class="w-full px-4 py-2.5 text-sm rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-gray-50 text-gray-800"
                                    required
                                >
                            </div>
                            
                            <?php if (!$isVuln): ?>
                                <div class="mt-4 bg-slate-50 border border-slate-200 p-3 rounded-lg">
                                    <div class="flex items-center gap-2 text-xs font-bold text-slate-600 mb-2">
                                        <i data-lucide="lock" class="w-[14px] h-[14px]"></i> CSRF Token Aktif (tersembunyi di form):
                                    </div>
                                    <div class="bg-slate-900 text-emerald-400 font-mono text-[10px] sm:text-xs p-2 rounded break-all">
                                        <?= htmlspecialchars($csrf_token) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="w-full py-3 rounded-xl font-bold flex items-center justify-center gap-2 transition-all mt-6 text-white shadow-md <?= $isVuln ? 'bg-red-500 hover:bg-red-600' : 'bg-teal-600 hover:bg-teal-700' ?>">
                                <i data-lucide="send" class="w-[18px] h-[18px]"></i> Kirim Transfer (<?= $isVuln ? 'Rentan' : 'Aman' ?>)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- HACKER SIMULATION CARD -->
                <div class="rounded-2xl shadow-xl overflow-hidden bg-[#1a1c23] border border-[#2d313a]">
                    <div class="bg-[#21242b] px-4 py-3 flex items-center gap-3 border-b border-[#2d313a]">
                        <div class="flex gap-2 shrink-0">
                            <div class="w-3 h-3 rounded-full bg-[#ff5f56]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#ffbd2e]"></div>
                            <div class="w-3 h-3 rounded-full bg-[#27c93f]"></div>
                        </div>
                        <div class="flex-1 flex justify-center">
                            <div class="flex items-center gap-2 text-[10px] md:text-xs font-medium text-slate-400 bg-[#16181d] px-3 py-1.5 rounded border border-[#2d313a] w-full max-w-sm overflow-hidden">
                                <i data-lucide="lock" class="w-3 h-3 text-slate-500 shrink-0"></i>
                                <span class="truncate">web-hacker-jahat.com <span class="text-slate-600 mx-1 hidden sm:inline">—</span> <span class="text-red-400 font-bold hidden sm:inline">Halaman Undian Palsu</span></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 md:p-8 text-center relative overflow-hidden">
                        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full bg-gradient-to-b from-blue-500/10 to-transparent pointer-events-none"></div>
                        
                        <h2 class="text-xl md:text-2xl font-black text-white mb-2 relative z-10 flex items-center justify-center gap-2">
                            🎉 SELAMAT! ANDA MEMENANGKAN MOBIL SPORT! 🎉
                        </h2>
                        <p class="text-slate-300 text-sm mb-6 relative z-10">
                            Klaim hadiah Anda sekarang sebelum kedaluwarsa! Hanya untuk 10 orang pertama.
                        </p>
                        
                        <div class="text-left text-xs font-mono text-slate-500 mb-3 ml-2 opacity-50">
                            &lt;!-- form tersembunyi mengincar Rp 10.000.000 --&gt;
                        </div>
                        
                        <!-- DITAMBAHKAN ONSUBMIT JAVASCRIPT ALERT DI SINI -->
                        <form method="POST" action="bank.php" class="relative z-10" onsubmit="alert('Memproses klaim hadiah Anda...\n\n(Simulasi Hacker: Mengirim request transfer Rp 10.000.000 secara sembunyi-sembunyi ke server bank!)');">
                            <input type="hidden" name="action" value="transfer">
                            <input type="hidden" name="destination" value="9999-HACKER-0001">
                            <input type="hidden" name="amount" value="10000000">
                            <!-- TIDAK ADA CSRF TOKEN DI SINI (SIMULASI ATTACK) -->
                            
                            <button type="submit" class="w-full py-4 rounded-xl font-black text-slate-900 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 shadow-[0_0_20px_rgba(251,191,36,0.2)] hover:shadow-[0_0_30px_rgba(251,191,36,0.4)] transition-all flex items-center justify-center gap-2 text-lg transform hover:-translate-y-1">
                                <i data-lucide="gift" class="w-6 h-6"></i> KLAIM MOBIL SEKARANG!
                            </button>
                        </form>
                        
                        <div class="mt-4 text-[11px] md:text-xs font-medium">
                            <?php if ($isVuln): ?>
                                <span class="text-red-400 flex items-center justify-center gap-1 bg-red-900/20 py-2 px-3 rounded-lg border border-red-900/50">
                                    <i data-lucide="alert-triangle" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span>⚠️ Tombol ini akan <strong>mencuri Rp 10.000.000</strong> dari saldo bank Anda! (Mode Rentan)</span>
                                </span>
                            <?php else: ?>
                                <span class="text-emerald-400 flex items-center justify-center gap-1 bg-emerald-900/20 py-2 px-3 rounded-lg border border-emerald-900/50">
                                    <i data-lucide="check-circle-2" class="w-3.5 h-3.5 shrink-0"></i>
                                    <span>✅ Tombol ini akan <strong>DITOLAK</strong> server karena tidak memiliki CSRF Token valid. (Mode Aman)</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- KANAN: TAB INFORMASI -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden sticky top-8">
                <div class="flex border-b border-gray-100 p-2 gap-2 bg-gray-50/50">
                    <a href="?tab=cara_kerja" class="flex-1 py-2 text-sm font-bold text-center rounded-lg transition-all <?= $activeTab === 'cara_kerja' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' ?>">Cara Kerja</a>
                    <a href="?tab=kode_php" class="flex-1 py-2 text-sm font-bold text-center rounded-lg transition-all <?= $activeTab === 'kode_php' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' ?>">Kode PHP</a>
                    <a href="?tab=tugas" class="flex-1 py-2 text-sm font-bold text-center rounded-lg transition-all <?= $activeTab === 'tugas' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100' ?>">Tugas</a>
                </div>
                
                <div class="p-6">
                    <?php if ($activeTab === 'cara_kerja'): ?>
                        <div class="space-y-6">
                            <?php if ($isVuln): ?>
                                <div class="bg-[#4a1515] text-white p-5 rounded-xl border border-red-900/50 shadow-inner">
                                    <h3 class="font-bold flex items-center gap-2 mb-4 text-red-200">
                                        <i data-lucide="alert-triangle" class="w-[18px] h-[18px]"></i> Mengapa Ini Berbahaya?
                                    </h3>
                                    <div class="bg-black/20 p-4 rounded-lg border border-white/5">
                                        <p class="text-xs font-bold text-red-300 mb-3 tracking-wide">ALUR SERANGAN CSRF:</p>
                                        <ol class="text-sm text-red-100 space-y-3 list-decimal list-outside ml-4">
                                            <li>Bapak Budi membuka <code class="text-red-200 bg-red-950 px-1.5 py-0.5 rounded border border-red-900">bank.php</code> → Sesi Login aktif di browser.</li>
                                            <li>Bapak Budi membuka tab baru ke <code class="text-red-200 bg-red-950 px-1.5 py-0.5 rounded border border-red-900">web-hacker.com</code> (web undian palsu).</li>
                                            <li>Ia klik tombol <strong>"Klaim Mobil"</strong> yang ternyata adalah form POST ke <code class="text-red-200 bg-red-950 px-1.5 py-0.5 rounded border border-red-900">bank.php</code>.</li>
                                            <li>Browser mengirim request + Cookie Sesi otomatis karena domain bank sama.</li>
                                            <li class="text-white font-bold bg-red-500/20 px-3 py-2 rounded-lg border border-red-500/30">Server Bank menerima request, menganggapnya SAH, dan mentransfer Rp 10 Juta!</li>
                                        </ol>
                                    </div>
                                </div>
                                
                                <div class="border border-red-100 bg-red-50/30 p-5 rounded-xl">
                                    <h3 class="font-bold flex items-center gap-2 mb-4 text-red-800">
                                        <i data-lucide="terminal" class="w-[18px] h-[18px]"></i> Coba Serang: Isi Form Transfer Manual
                                    </h3>
                                    <div class="space-y-3">
                                        <form method="POST" action="bank.php">
                                            <input type="hidden" name="action" value="transfer">
                                            <input type="hidden" name="destination" value="9999-HACKER-0001">
                                            <input type="hidden" name="amount" value="5000000">
                                            <!-- Tanpa CSRF Token -->
                                            <button type="submit" class="w-full text-left p-3 rounded-lg border border-red-200 hover:border-red-400 hover:bg-red-50 transition-all text-sm font-medium text-red-900 flex items-center gap-3 bg-white">
                                                <i data-lucide="file-text" class="w-[18px] h-[18px] text-red-400"></i>
                                                <span>Curi Rp 5.000.000 ke rek. <span class="font-mono text-xs">9999-HACKER-0001</span></span>
                                            </button>
                                        </form>
                                        
                                        <form method="POST" action="bank.php">
                                            <input type="hidden" name="action" value="transfer">
                                            <input type="hidden" name="destination" value="HACKER-ANONIM">
                                            <input type="hidden" name="amount" value="25000000">
                                            <!-- Tanpa CSRF Token -->
                                            <button type="submit" class="w-full text-left p-3 rounded-lg border border-red-200 hover:border-red-400 hover:bg-red-50 transition-all text-sm font-medium text-red-900 flex items-center gap-3 bg-white">
                                                <i data-lucide="file-text" class="w-[18px] h-[18px] text-red-400"></i>
                                                <span>Curi Rp 25.000.000 ke rek. <span class="font-mono text-xs">HACKER-ANONIM</span></span>
                                            </button>
                                        </form>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-4 leading-relaxed">Gunakan panel Hacker di bawah untuk simulasi serangan cross-site sebenarnya.</p>
                                </div>
                            <?php else: ?>
                                <div class="bg-[#0f3f2b] text-white p-5 rounded-xl border border-teal-900/50 shadow-inner">
                                    <h3 class="font-bold flex items-center gap-2 mb-4 text-emerald-300">
                                        <i data-lucide="shield-check" class="w-[18px] h-[18px]"></i> Cara Token Melindungi Anda
                                    </h3>
                                    <div class="bg-black/20 p-4 rounded-lg border border-white/5 mb-4">
                                        <p class="text-xs font-bold text-emerald-400 mb-3 tracking-wide">MEKANISME PERLINDUNGAN:</p>
                                        <ol class="text-sm text-emerald-100 space-y-3 list-decimal list-outside ml-4">
                                            <li>Server generate token acak 64 karakter via <code class="text-emerald-200 bg-teal-950 px-1.5 py-0.5 rounded border border-teal-900 text-xs">bin2hex(random_bytes(32))</code>.</li>
                                            <li>Token disimpan di <code class="text-emerald-200 bg-teal-950 px-1.5 py-0.5 rounded border border-teal-900 text-xs">$_SESSION['csrf_token']</code> (Rahasia Server).</li>
                                            <li>Token juga dicetak di form asli sebagai <code class="text-emerald-200 bg-teal-950 px-1.5 py-0.5 rounded border border-teal-900 text-xs">input type="hidden"</code>.</li>
                                            <li>Saat submit, server bandingkan via <code class="text-emerald-200 bg-teal-950 px-1.5 py-0.5 rounded border border-teal-900 text-xs">hash_equals()</code>. Cocok = Aman.</li>
                                            <li class="text-white font-bold bg-emerald-500/20 px-3 py-2 rounded-lg border border-emerald-500/30">Form hacker tidak bisa membaca token rahasia ini! Request ditolak!</li>
                                        </ol>
                                    </div>
                                    
                                    <div class="bg-black/30 p-3.5 rounded-lg border border-white/5 font-mono text-xs leading-relaxed">
                                        <p class="text-slate-400 mb-1 font-bold text-[10px] tracking-widest">PERBANDINGAN REQUEST:</p>
                                        <p class="text-red-400">Form Hacker: csrf_token = <em>(kosong / tidak ada)</em></p>
                                        <p class="text-slate-500 my-1">→ hash_equals() gagal →</p>
                                        <p class="text-red-400 font-bold bg-red-950/50 px-2 py-1 rounded border border-red-900/50">Server: "⛔ TOKEN TIDAK VALID! Ditolak."</p>
                                    </div>
                                </div>
                                
                                <div class="border border-emerald-100 bg-emerald-50/30 p-5 rounded-xl">
                                    <h3 class="font-bold flex items-center gap-2 mb-3 text-emerald-800">
                                        <i data-lucide="refresh-cw" class="w-[18px] h-[18px]"></i> Coba Serang Ulang dengan Token Aktif
                                    </h3>
                                    <p class="text-sm text-gray-700 mb-4">
                                        Gunakan panel <strong>Simulasi Hacker</strong> di bawah — tekan tombol "Klaim Mobil". Kali ini server akan menampilkan pesan Error CSRF!
                                    </p>
                                    <div class="bg-red-50 border border-red-100 rounded-lg p-3 text-xs text-red-800">
                                        <strong>⛔ Yang diharapkan terjadi:</strong><br/>
                                        Banner merah "SERANGAN CSRF TERDETEKSI" muncul, saldo tidak berkurang.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($activeTab === 'kode_php'): ?>
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800">Implementasi Patch PHP (Contoh)</h3>
                            <p class="text-sm text-gray-600">Berikut adalah cara mengimplementasikan Anti-CSRF token di sisi server PHP untuk tugas Anda.</p>
                            
                            <div class="bg-slate-900 rounded-xl p-5 overflow-x-auto text-xs sm:text-sm font-mono text-slate-300 shadow-inner">
                                <pre><code>&lt;?php
session_start();

// 1. Generate Token (Hanya jika belum ada)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. Verifikasi Token (Saat form disubmit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        
        die("⛔ ERROR: SERANGAN CSRF TERDETEKSI!");
    }
    
    // Proses transfer aman di sini...
}
?&gt;</code></pre>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($activeTab === 'tugas'): ?>
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800">Instruksi Tugas Pengumpulan</h3>
                            <div class="bg-blue-50 border border-blue-100 p-5 rounded-xl text-sm text-blue-900 space-y-4">
                                <p class="font-medium">Buktikan pemahaman Anda dengan mendokumentasikan 3 bukti screenshot berikut:</p>
                                <ol class="list-decimal list-outside space-y-3 ml-4 font-normal">
                                    <li><strong>Bukti Kerentanan (Sebelum Patch):</strong> Screenshot halaman bank setelah Anda mengklik tombol hacker (saldo berkurang) di <span class="font-bold">Mode Rentan</span>.</li>
                                    <li><strong>Inspect Element Form Asli:</strong> Setelah beralih ke <span class="font-bold">Mode Aman</span>, screenshot kode HTML yang menunjukkan kehadiran <code class="bg-white border border-blue-200 px-1.5 py-0.5 rounded font-mono text-xs text-blue-600">&lt;input type="hidden" name="csrf_token" value="..."&gt;</code> beserta nilai acaknya.</li>
                                    <li><strong>Uji Serangan Ulang (Setelah Patch):</strong> Screenshot halaman putih bertuliskan "⛔ ERROR: SERANGAN CSRF TERDETEKSI!" saat mencoba serang di Mode Aman.</li>
                                </ol>
                                <div class="mt-4 pt-4 border-t border-blue-200 flex items-center gap-2">
                                    <i data-lucide="check-circle-2" class="w-4 h-4 text-blue-500"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Kumpulkan ke classroom beserta link project</span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>