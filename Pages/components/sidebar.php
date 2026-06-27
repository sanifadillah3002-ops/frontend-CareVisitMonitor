<?php
require_once __DIR__ . '/sf-icons.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'] ?? [];
$userName = htmlspecialchars($user['name'] ?? 'Petugas');
$userRole = htmlspecialchars($user['role'] ?? 'Petugas Kesehatan');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));

$navItems = [
    ['dashboard.php',         'square-grid',   'Dashboard',           ['dashboard.php']],
    ['pasien.php',            'person-2',      'Daftar Pasien',        ['pasien.php']],
    ['tambah-pasien.php',     'plus-circle',   'Tambah Pasien',        ['tambah-pasien.php']],
    ['monitoring.php',        'clipboard',     'Data Monitoring',      ['monitoring.php']],
    ['tambah-monitoring.php', 'cross-case',    'Catat Monitoring',     ['tambah-monitoring.php']],
    ['cari-pasien.php',       'magnifyingglass','Cari Pasien',          ['cari-pasien.php']],
    ['lokasi-petugas.php',    'mappin',        'Monitoring Lokasi',    ['lokasi-petugas.php']],
];
?>
<div class="sv-sidebar" id="sidebar">
    <div class="sv-sidebar-brand">
        <button class="sv-sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <span></span><span></span><span></span>
        </button>
        <div class="sv-sidebar-brand-name">
            <strong>SIVISIT</strong>
            <span>SIVISIT-SIVISIT-CareVisitMonitor</span>
        </div>
    </div>

    <nav class="sv-sidebar-nav">
        <div class="sv-nav-section-label">Menu Utama</div>
        <?php foreach ($navItems as [$href, $icon, $label, $pages]): ?>
            <?php $isActive = in_array($currentPage, $pages); ?>
            <a href="<?= $href ?>" class="sv-nav-link <?= $isActive ? 'active' : '' ?>">
                <span class="nav-icon"><?= sf_icon($icon, 18) ?></span>
                <?= $label ?>
            </a>
        <?php endforeach; ?>

        <div class="sv-nav-section-label">Akses Publik</div>
        <a href="../index.php" class="sv-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <span class="nav-icon"><?= sf_icon('globe', 18) ?></span>
            Halaman Utama
        </a>
    </nav>

    <div class="sv-sidebar-footer">
        <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;margin-bottom:8px;">
            <div class="sv-avatar" style="width:36px;height:36px;font-size:13px;"><?= $userInitial ?></div>
            <div style="overflow:hidden;">
                <div style="font-size:13px;font-weight:600;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $userName ?></div>
                <div style="font-size:11px;color:var(--sv-sidebar-txt);"><?= $userRole ?></div>
            </div>
        </div>
        <a href="logout.php" class="sv-logout-btn" style="text-decoration:none;">
             Keluar
        </a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
            this.classList.toggle('active');
        });
    }
});
</script>
