<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientsRes = callAPI('GET', '/patients');
$patients = ($patientsRes['status_code'] === 200 && isset($patientsRes['response']['data'])) ? $patientsRes['response']['data'] : [];

$monitoringsRes = callAPI('GET', '/monitorings');
$monitorings = ($monitoringsRes['status_code'] === 200 && isset($monitoringsRes['response']['data'])) ? $monitoringsRes['response']['data'] : [];

// Statistics
$totalPatients = count($patients);

$todayDate = date('Y-m-d');
$todayVisits = 0;
$todayFinished = 0;
$todayAgenda = [];

foreach ($monitorings as $m) {
    if ($m['monitoring_date'] === $todayDate) {
        $todayVisits++;
        if (($m['status'] ?? '') === 'Stable') {
            $todayFinished++;
        }
        $todayAgenda[] = $m;
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta charset="utf-8" />
    <link rel="stylesheet" href="globals.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {},
            },
            plugins: [],
        }
    </script>
</head>

<body>
    <div class="flex flex-col items-start relative [background:linear-gradient(0deg,rgba(248,249,250,1)_0%,rgba(248,249,250,1)_100%),linear-gradient(0deg,rgba(255,255,255,1)_0%,rgba(255,255,255,1)_100%)] min-h-screen w-full">
        
        <!-- Main Panel (Shifted by 260px to clear the sidebar) -->
        <div class="flex flex-col min-h-screen items-start pl-[260px] pr-0 py-0 relative self-stretch w-full flex-1">
            
            <!-- Top Navbar Header -->
            <div class="flex h-16 items-center px-6 py-0 relative self-stretch w-full bg-[#f8f9fa] border-b border-[#c2c6d6] justify-between">
                <div class="flex flex-col max-w-2xl items-start relative flex-1 grow">
                    <div class="flex items-start justify-center pl-12 pr-6 pt-[9px] pb-2.5 relative self-stretch w-full flex-[0_0_auto] bg-white rounded-full overflow-hidden border border-solid border-[#c2c6d6]">
                        <div class="flex flex-col items-start relative flex-1 grow">
                            <p class="relative flex items-center self-stretch mt-[-1.00px] [font-family:'Inter-Regular',Helvetica] font-normal text-gray-500 text-sm tracking-[0] leading-[normal]">
                                Cari data pasien, rekam medis, atau log...</p>
                        </div>
                    </div>
                </div>
                
                <div class="inline-flex items-center gap-6 relative flex-[0_0_auto]">
                    <div class="inline-flex items-center gap-3 relative flex-[0_0_auto]">
                        <div class="inline-flex flex-col items-start relative flex-[0_0_auto]">
                            <div class="flex flex-col items-end relative self-stretch w-full flex-[0_0_auto]">
                                <div class="justify-end [font-family:'Inter-SemiBold',Helvetica] font-semibold text-[#191c1d] text-xs text-right tracking-[0.60px] leading-4 relative flex items-center w-fit mt-[-1.00px] whitespace-nowrap">
                                    <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Perawat'); ?></div>
                            </div>
                            <div class="flex flex-col items-end relative self-stretch w-full flex-[0_0_auto]">
                                <div class="justify-end [font-family:'Inter-Medium',Helvetica] font-medium text-[#424754] text-[10px] text-right tracking-[0] leading-[15px] relative flex items-center w-fit mt-[-1.00px] whitespace-nowrap">
                                    <?php echo htmlspecialchars($_SESSION['user']['email'] ?? 'perawat@carevisit.com'); ?></div>
                            </div>
                        </div>
                        <div class="flex flex-col w-10 h-10 items-start justify-center relative rounded-full overflow-hidden border-2 border-solid border-[#adc6ff] bg-gray-200">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Content Area -->
            <div class="relative self-stretch w-full flex-1 px-8 py-12">
                <div class="flex flex-col max-w-[1440px] items-center gap-[22.9px] relative w-full mx-auto">
                    
                    <div class="inline-flex items-center gap-[7.99px] px-3 py-1 relative flex-[0_0_auto] bg-[#d7e0f4] rounded-full">
                        <div class="inline-flex flex-col items-center relative flex-[0_0_auto]">
                            <div class="flex items-center justify-center mt-[-1.00px] [font-family:'Inter-Bold',Helvetica] font-bold text-[#5a6374] text-[11px] text-center tracking-[0.55px] leading-[16.5px] whitespace-nowrap relative w-fit">
                                SISTEM MONITORING TERPADU</div>
                        </div>
                    </div>
                    
                    <div class="max-w-4xl w-[896px] items-center pt-0 pb-[0.69px] px-0 flex flex-col relative flex-[0_0_auto]">
                        <p class="mt-[-1.00px] [font-family:'Inter-Regular',Helvetica] font-normal text-[#191c1d] text-4xl text-center tracking-[0] leading-[52.8px] relative w-fit">
                            Selamat Datang kembali, <span class="text-[#1e56e3]"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Perawat'); ?></span>
                        </p>
                    </div>

                    <!-- Statistics Cards Section -->
                    <div class="grid grid-cols-3 gap-6 w-[896px] mt-6">
                        <div class="flex flex-col bg-white p-6 rounded-xl border border-solid border-[#c2c6d6] shadow-sm">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Pasien Homecare</span>
                            <span class="text-3xl font-bold text-[#1e56e3] mt-2"><?php echo $totalPatients; ?> Orang</span>
                        </div>
                        <div class="flex flex-col bg-white p-6 rounded-xl border border-solid border-[#c2c6d6] shadow-sm">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kunjungan Hari Ini</span>
                            <span class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $todayVisits; ?> Pasien</span>
                        </div>
                        <div class="flex flex-col bg-white p-6 rounded-xl border border-solid border-[#c2c6d6] shadow-sm">
                            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tugas Selesai</span>
                            <span class="text-3xl font-bold text-green-600 mt-2"><?php echo $todayFinished; ?> Selesai</span>
                        </div>
                    </div>

                    <!-- Agenda Table Section -->
                    <div class="flex flex-col w-[896px] mt-8 bg-white p-6 rounded-xl border border-solid border-[#c2c6d6] shadow-sm">
                        <h5 class="text-lg font-bold text-gray-800 mb-4">Agenda Kunjungan Rumah Hari Ini</h5>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jam</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Pasien</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alamat Rumah</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (empty($todayAgenda)): ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada agenda kunjungan hari ini.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($todayAgenda as $agenda): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                                    <?php echo isset($agenda['monitoring_time']) ? date('H:i', strtotime($agenda['monitoring_time'])) : '--:--'; ?> WIB
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                                    <?php echo htmlspecialchars($agenda['patient']['patient_name'] ?? '-'); ?>
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($agenda['patient']['address'] ?? '-'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if (($agenda['status'] ?? '') === 'Stable'): ?>
                                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Selesai</span>
                                                    <?php else: ?>
                                                        <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 font-medium">Tertunda / Belum</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <a href="pasien.php" class="text-[#1e56e3] hover:text-[#0058be]">Detail</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Quick Lookup Search Mockup -->
                    <div class="flex w-[896px] items-center gap-4 p-4 mt-8 bg-white rounded-xl border border-solid border-[#c2c6d6] shadow-sm">
                        <div class="flex flex-col items-start relative flex-1 grow">
                            <div class="flex items-start justify-center pl-4 pr-4 pt-[17px] pb-[18px] relative self-stretch w-full bg-[#f3f4f5] rounded-lg overflow-hidden">
                                <input type="text" class="w-full bg-transparent border-0 outline-none text-sm text-gray-700" placeholder="Masukkan NIK Dummy Anda untuk pencarian cepat...">
                            </div>
                        </div>
                        <button class="box-border inline-flex items-center justify-center gap-2 px-8 py-4 relative flex-[0_0_auto] bg-[#1e56e3] rounded-lg text-white font-bold hover:bg-[#0058be] transition-colors">
                            Cari Data Monitoring
                        </button>
                    </div>

                </div>
            </div>

            <!-- Redesigned Footer Section -->
            <footer class="flex flex-col items-start px-8 py-12 relative self-stretch w-full bg-[#e7e8e9] border-t border-[#c2c6d6]">
                <div class="flex flex-col max-w-[1440px] items-start gap-8 relative w-full mx-auto">
                    <div class="justify-between pt-0 pb-8 px-0 border-b border-solid flex items-center relative self-stretch w-full border-[#c2c6d6]">
                        <div class="inline-flex items-center gap-3 relative">
                            <div class="flex w-8 h-8 items-center justify-center relative bg-[#1e56e3] rounded">
                                <span class="text-white font-bold text-sm">CM</span>
                            </div>
                            <span class="font-semibold text-[#191c1d] text-xl tracking-[0] leading-7 whitespace-nowrap relative">
                                CareVisit Monitor
                            </span>
                        </div>
                        <div class="inline-flex items-start gap-6 relative">
                            <span class="font-semibold text-[#424754] text-xs tracking-[0.60px] leading-4 whitespace-nowrap">Privacy Policy</span>
                            <span class="font-semibold text-[#424754] text-xs tracking-[0.60px] leading-4 whitespace-nowrap">Terms of Service</span>
                            <span class="font-semibold text-[#424754] text-xs tracking-[0.60px] leading-4 whitespace-nowrap">Help Center</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-start gap-[14.94px] relative self-stretch w-full">
                        <span class="font-semibold text-[#424754] text-xs tracking-[0.60px] leading-4">
                            © 2026 MediAdmin CareVisit Monitor. Seluruh hak cipta dilindungi.
                        </span>
                        <p class="font-medium italic text-[#424754] text-[11px] tracking-[0] leading-[17.9px]">
                            Disclaimer: Seluruh data yang ditampilkan pada sistem ini bersifat simulasi/dummy untuk keperluan monitoring administratif. Sistem ini tidak memberikan diagnosis medis mandiri; konsultasikan hasil monitoring kepada dokter profesional untuk tindakan medis lebih lanjut.
                        </p>
                    </div>
                </div>
            </footer>
        </div>
        
        <!-- Sidebar Drawer (Absolute Left Panel) -->
        <div class="flex flex-col items-start justify-between px-0 py-6 bg-[#001a42] w-[260px] absolute top-0 left-0 bottom-0 h-full border-r border-[#002866] z-50">
            <div class="items-start pt-0 pb-10 px-0 self-stretch w-full flex flex-col relative">
                <div class="flex items-center gap-3 px-6 py-0 relative self-stretch w-full">
                    <div class="flex w-10 h-10 items-center justify-center relative bg-[#0058be] rounded-lg">
                        <span class="text-white font-bold text-lg">CVM</span>
                    </div>
                    <div class="inline-flex flex-col items-start relative">
                        <span class="font-bold text-white text-base tracking-[0] leading-tight">
                            MediAdmin
                        </span>
                        <span class="font-normal text-[#adc6ff] text-[10px] tracking-[1.00px] leading-[15px] opacity-70">
                            CAREVISIT
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-start gap-1 px-4 py-0 relative flex-1 self-stretch w-full">
                <!-- Navigation Link: Dashboard -->
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 relative self-stretch w-full bg-[#2170e4] rounded-lg text-white hover:bg-[#1a5cbd] transition-colors text-decoration-none">
                    <span class="text-sm">🏠 Dashboard</span>
                </a>
                
                <!-- Navigation Link: Patients -->
                <a href="pasien.php" class="flex items-center gap-3 px-4 py-3 relative self-stretch w-full rounded-lg text-[#bec7db] hover:bg-[#082859] hover:text-white transition-colors text-decoration-none">
                    <span class="text-sm">👥 Daftar Pasien</span>
                </a>
                
                <!-- Navigation Link: Add Patient -->
                <a href="tambah-pasien.php" class="flex items-center gap-3 px-4 py-3 relative self-stretch w-full rounded-lg text-[#bec7db] hover:bg-[#082859] hover:text-white transition-colors text-decoration-none">
                    <span class="text-sm">➕ Tambah Pasien</span>
                </a>
            </div>
            
            <!-- Bottom Sidebar Tools -->
            <div class="flex flex-col items-start gap-2 px-4 py-0 relative self-stretch w-full">
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 relative self-stretch w-full rounded-lg text-red-400 hover:bg-[#2d0010] hover:text-red-200 transition-colors text-decoration-none">
                    <span class="text-sm">🚪 Keluar</span>
                </a>
            </div>
        </div>

    </div>
</body>

</html>