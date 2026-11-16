<?php
// logout.php
require_once __DIR__ . '/includes/auth.php';

logoutUser();

$baseUrl = getAppBaseUrl();
if (!headers_sent()) {
    header('Location: ' . $baseUrl . '/index.php');
} else {
    echo '<script>window.location.href=' . json_encode($baseUrl . '/index.php') . ';</script>';
}
exit;
