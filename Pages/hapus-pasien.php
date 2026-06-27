<?php
require_once '../config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}

$patientId = $_GET['id'] ?? '';
if (!empty($patientId)) {
    $result = callAPI('DELETE', '/pasien/' . urlencode($patientId));
    if ($result['status_code'] === 200) {
        header("Location: pasien.php?success=deleted");
        exit;
    } else {
        header("Location: pasien.php?error=delete_failed");
        exit;
    }
} else {
    header("Location: pasien.php");
    exit;
}
?>
