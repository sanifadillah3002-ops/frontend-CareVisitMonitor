<?php

require 'config.php';


if (!isset($_SESSION['api_token'])) {
    header("Location: login.php");
    exit;
}


$apiCall = callAPI('GET', '/pasien');


$daftarPasien = [];
if ($apiCall['status_code'] == 200 && isset($apiCall['response']['data'])) {
    $daftarPasien = $apiCall['response']['data'];
}
