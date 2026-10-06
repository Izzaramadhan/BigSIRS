<?php

$tables = DB::connection('legacy')->select("SHOW TABLES LIKE '%kelurahan%'");
print_r($tables);
$tables2 = DB::connection('legacy')->select("SHOW TABLES LIKE '%desa%'");
print_r($tables2);
$cols = DB::connection('legacy')->select('SHOW COLUMNS FROM ref_kelurahan');
print_r($cols);
$data = DB::connection('legacy')->select('SELECT * FROM ref_kelurahan LIMIT 5');
print_r($data);
