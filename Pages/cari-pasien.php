<?php
// Redirect old public search requests to the new unified jadwal page
$q = $_GET['q'] ?? '';
$redirectUrl = 'jadwal.php' . (!empty($q) ? '?q=' . urlencode($q) : '');
header('Location: ' . $redirectUrl);
exit;
?>
