<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$_POST['message'] = 'Compare users by Gender';
$_POST['session_id'] = 'test1234';
$_POST['model'] = 'gemini-1.5-flash';
require 'admin/ai_query.php';
