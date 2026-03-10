<?php
require_once 'includes/db.php';
$res = db_query("SHOW COLUMNS FROM users LIKE 'role'");
print_r($res);
