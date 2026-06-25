<?php
// config/google.php
define('GOOGLE_CLIENT_ID', '1069719200035-od6igdu59et9qp3k4qrnc98t1mn4mjhk.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
define('GOOGLE_REDIRECT_URI', $protocol . '://' . $host . '/STDC-Program-Onboarding-System/auth/google_callback.php');
?>
