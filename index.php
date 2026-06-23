<?php
// index.php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/index.php');
    } else {
        redirect('/user/index.php');
    }
} else {
    redirect('/auth/login.php');
}
