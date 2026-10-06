<?php

$db = DB::connection('legacy');
print_r($db->select('SELECT * FROM ref_pendidikan LIMIT 1'));
print_r($db->select('SELECT * FROM ref_pekerjaan LIMIT 1'));
print_r($db->select('SELECT * FROM ref_provinsi LIMIT 1'));
print_r($db->select('SELECT * FROM ref_kabupaten LIMIT 1'));
print_r($db->select('SELECT * FROM ref_kecamatan LIMIT 1'));
print_r($db->select('SELECT * FROM ref_kelurahan LIMIT 1'));
