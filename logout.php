<?php
require_once __DIR__ . '/app/functions.php';
$_SESSION = [];
session_destroy();
redirect('login.php');
