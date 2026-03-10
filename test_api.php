<?php
$_SESSION = [];
$_SESSION['user_type'] = 'Staff';
$_SESSION['user_id'] = 1;
$_GET['action'] = 'list_orders';
require_once 'admin/job_orders_api.php';
