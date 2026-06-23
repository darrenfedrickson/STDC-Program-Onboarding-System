<?php
// auth/logout.php
session_start();

$was_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'developer']);

session_destroy();

if ($was_admin) {
    header("Location: /iDaftar@STDC/admin/login.php");
} else {
    header("Location: /iDaftar@STDC/auth/login.php");
}
exit();
?>
