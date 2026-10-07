<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$legacy = DB::connection('legacy');
echo 'Total Pegawai: ' . $legacy->table('ref_pegawai')->count() . PHP_EOL;
echo 'Total Person: ' . $legacy->table('master_person_index')->count() . PHP_EOL;
echo 'Empty/0 NIP: ' . $legacy->table('ref_pegawai')->where('nip', '0')->orWhere('nip', '')->orWhereNull('nip')->count() . PHP_EOL;
echo 'Pegawai without MPI: ' . $legacy->table('ref_pegawai')->whereNull('id_mpi')->count() . PHP_EOL;
echo 'Pegawai without Jabatan: ' . $legacy->table('ref_pegawai')->whereNull('id_jabatan')->count() . PHP_EOL;
echo 'Empty/duplicate NIK: ' . $legacy->table('master_person_index')->where('no_ktp', '0')->orWhere('no_ktp', '')->orWhereNull('no_ktp')->count() . PHP_EOL;
