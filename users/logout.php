<?php
require_once '../db.php';
session_destroy();
header('Location: /users/login.php');
exit;