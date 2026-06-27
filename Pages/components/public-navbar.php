<?php
/**
 * Shared public navbar.
 * Set $navActive: beranda|jadwal|about
 * Set $navFromPages: true when included from Pages/*.php
 * Optional $showAdminBtn: true to show Masuk Admin link
 */
require_once __DIR__ . '/sf-icons.php';

$navActive = $navActive ?? '';
$navFromPages = $navFromPages ?? false;
$showAdminBtn = $showAdminBtn ?? false;

$home = $navFromPages ? '../index.php' : 'index.php';
$jadwal = $navFromPages ? 'jadwal.php' : 'Pages/jadwal.php';
$about = $navFromPages ? 'about.php' : 'Pages/about.php';
$login = $navFromPages ? 'login.php' : 'Pages/login.php';
$brandHref = $home;

$items = [
    'beranda' => ['href' => $home,   'icon' => 'house',       'label' => 'Beranda'],
    'jadwal'  => ['href' => $jadwal, 'icon' => 'calendar',    'label' => 'Cek Jadwal'],
    'about'   => ['href' => $about,  'icon' => 'info-circle', 'label' => 'Tentang Kami'],
];

$navClass = $navFromPages ? 'sv-navbar' : 'navbar';
$brandClass = $navFromPages ? 'sv-brand' : 'nav-brand';
$linksClass = $navFromPages ? 'sv-navbar-links' : 'nav-links';
?>
<nav class="<?= $navClass ?>">
    <a href="<?= htmlspecialchars($brandHref) ?>" class="<?= $brandClass ?>">SIVI<span>SIT</span></a>
    <button class="burger-btn" id="burgerBtn" type="button" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <div class="<?= $linksClass ?>">
        <?php foreach ($items as $key => $item): ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $navActive === $key ? 'active' : '' ?>">
                <?= sf_icon($item['icon'], 17, 'nav-sf-icon') ?>
                <?= htmlspecialchars($item['label']) ?>
            </a>
        <?php endforeach; ?>
        <?php if ($showAdminBtn): ?>
            <a href="<?= htmlspecialchars($login) ?>" class="btn-sv-primary ms-2">
                <?= sf_icon('person', 16, 'nav-sf-icon') ?>
                Masuk Admin
            </a>
        <?php endif; ?>
    </div>
</nav>
<div class="mobile-menu" id="mobileMenu">
    <?php foreach ($items as $key => $item): ?>
        <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $navActive === $key ? 'active' : '' ?>">
            <?= sf_icon($item['icon'], 18, 'nav-sf-icon') ?>
            <?= htmlspecialchars($item['label']) ?>
        </a>
    <?php endforeach; ?>
    <?php if ($showAdminBtn): ?>
        <a href="<?= htmlspecialchars($login) ?>">
            <?= sf_icon('person', 18, 'nav-sf-icon') ?>
            Masuk Admin
        </a>
    <?php endif; ?>
</div>
<script>
document.getElementById('burgerBtn')?.addEventListener('click', function () {
    this.classList.toggle('active');
    document.getElementById('mobileMenu')?.classList.toggle('open');
});
</script>
