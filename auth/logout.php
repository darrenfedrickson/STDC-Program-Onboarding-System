<?php
// auth/logout.php
session_start();
require_once __DIR__ . '/../includes/functions.php';

$was_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'developer']);

session_destroy();

if ($was_admin) {
    redirect('/admin/login.php');
} else {
    redirect('/auth/login.php');
}
?>
