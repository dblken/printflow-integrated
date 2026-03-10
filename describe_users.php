<?php
require_once 'includes/db.php';
$res = db_query('DESCRIBE users');
print_r($res);
