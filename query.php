<?php
$tables = DB::connection('legacy')->select("SHOW TABLES LIKE '%kelurahan%'");
print_r($tables);
$tables2 = DB::connection('legacy')->select("SHOW TABLES LIKE '%desa%'");
print_r($tables2);
