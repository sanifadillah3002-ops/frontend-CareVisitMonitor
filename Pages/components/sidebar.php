
<?php
// Auto-detect active page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$user = $_SESSION['user'] ?? [];
$userName = htmlspecialchars($user['name'] ?? 'Petugas');
$userRole = htmlspecialchars($user['role'] ?? 'Petugas Kesehatan');
$userInitial = strtoupper(substr($user['name'] ?? 'P', 0, 1));

// Nav items: [href, icon, label, page(s)]
$navItems = [
    ['dashboard.php',         '🏠', 'Dashboard',          ['dashboard.php']],
    ['pasien.php',            '👥', 'Daftar Pasien',       ['pasien.php']],
    ['tambah-pasien.php',     '➕', 'Tambah Pasien',       ['tambah-pasien.php']],
    ['monitoring.php',        '📋', 'Data Monitoring',     ['monitoring.php']],
    ['tambah-monitoring.php', '🩺', 'Catat Monitoring',    ['tambah-monitoring.php']],
    ['cari-pasien.php',       '🔍', 'Cari Pasien',         ['cari-pasien.php']],
];
?>
<!-- SIDEBAR -->
<div class="sv-sidebar" id="svSidebar">
    <!-- Brand -->
    <div class="sv-sidebar-brand">
        <div class="sv-sidebar-logo">SV</div>
        <div class="sv-sidebar-brand-name">
            <strong>SIVISIT</strong>
            <span>CareVisit Monitor</span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sv-sidebar-nav">
        <span class="sv-nav-section-label">Menu Utama</span>
        <?php foreach ($navItems as [$href, $icon, $label, $pages]): ?>
            <?php $isActive = in_array($currentPage, $pages); ?>
            <a href="<?= $href ?>" class="sv-nav-link <?= $isActive ? 'active' : '' ?>">
                <span class="nav-icon"><?= $icon ?></span>
                <?= $label ?>
            </a>
        <?php endforeach; ?>

        <span class="sv-nav-section-label" style="margin-top:12px;">Akses Publik</span>
        <a href="../index.php" class="sv-nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <span class="nav-icon">🌐</span>
            Halaman Utama
        </a>
    </nav>

    <!-- Footer / Logout -->
    <div class="sv-sidebar-footer">
        <div class="d-flex align-items-center gap-2 px-2 mb-3">
            <div class="sv-avatar" style="width:32px;height:32px;font-size:12px;"><?= $userInitial ?></div>
            <div style="overflow:hidden;">
                <div style="font-size:12px;font-weight:600;color:white;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= $userName ?></div>
                <div style="font-size:10px;color:var(--sv-sidebar-txt);opacity:.7;"><?= $userRole ?></div>
            </div>
        </div>
        <a href="logout.php" class="sv-logout-btn">
            <span class="nav-icon">🚪</span>
            Keluar
        </a>
    </div>
</div>
