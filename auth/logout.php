<?php
session_start();
require_once __DIR__ . '/../classes/User.php';

$user = new User();

if (isset($_SESSION['session_id'])) {
    $user->logout($_SESSION['session_id']);
}

session_destroy();
setcookie('session_id', '', time() - 3600, '/');

header('Location: ../index.php');
exit;
?>