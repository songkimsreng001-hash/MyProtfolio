<?php
// includes/header.php
// Detect prefix ('' for root pages, '../' for dashboard subfolder)
$_prefix = (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false) ? '../' : '';

// Set page title dynamically based on current file
$_page = basename($_SERVER['PHP_SELF'], '.php');
$_titles = [
    'index'    => 'Kimsreng | Portfolio',
    'login'    => 'Login | Kimsreng Portfolio',
    'register' => 'Register | Kimsreng Portfolio',
    'admin'    => 'Admin Dashboard | Kimsreng Portfolio',
];
$_title = $_titles[$_page] ?? 'Kimsreng Portfolio';
?>

<!DOCTYPE html>
<html lang="en" data-theme="dark" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= $_prefix ?>assets/images/Kimsreng Song-logo.png" type="image/png">
    <title><?= htmlspecialchars($_title) ?></title>
    
    <!-- Inline Theme Script: Eliminates Flash of Wrong Theme (FOUC) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('portfolio_theme');
            const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme ? savedTheme : (systemPrefersDark ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300..800;1,9..40,300..800&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $_prefix ?>assets/css/style.css?v=<?= time() ?>" rel="stylesheet">

    <!-- Google Identity Services -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="<?= (function_exists('isAdmin') && isAdmin()) ? 'is-admin' : '' ?>">