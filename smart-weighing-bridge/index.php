<?php
/**
 * Halaman Awal - Redirect ke login atau dashboard
 * Smart Weighing Bridge
 */

declare(strict_types=1);

session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

header('Location: login.php');
exit;