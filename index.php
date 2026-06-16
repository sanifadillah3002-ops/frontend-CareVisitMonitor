<?php

require 'config.php';

if (!isset($_SESSION['api_token'])) {
    header("Location: Pages/login.php");
    exit;
} else {
    header("Location: Pages/dashboard.php");
    exit;
}
?>
