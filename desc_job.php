<?php
require 'includes/db.php';
$res = db_query('DESCRIBE job_orders');
file_put_contents('job_cols.json', json_encode($res));
