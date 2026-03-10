<?php
require 'includes/db.php';
$res = db_query('DESCRIBE job_order_materials');
file_put_contents('columns.json', json_encode($res));
