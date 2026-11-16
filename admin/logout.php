<?php
require_once 'functions.php';
session_destroy();
header('Location: /admin/login.php');
exit;