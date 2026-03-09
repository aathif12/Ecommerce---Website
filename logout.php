<?php
require_once 'config/helpers.php';
session_destroy();
header('Location: login.php');
exit;
?>