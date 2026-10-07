<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$legacy = DB::connection('legacy');
$dups = $legacy->table('ref_pegawai')
    ->join('master_person_index', 'ref_pegawai.id_mpi', '=', 'master_person_index.id')
    ->select('no_ktp', DB::raw('count(*) as c'))
    ->groupBy('no_ktp')
    ->having('c', '>', 1)
    ->get();
echo "Duplicate NIK in Pegawai: " . count($dups) . PHP_EOL;
$dupsNip = $legacy->table('ref_pegawai')
    ->select('nip', DB::raw('count(*) as c'))
    ->groupBy('nip')
    ->having('c', '>', 1)
    ->get();
echo "Duplicate NIP: " . count($dupsNip) . " (" . json_encode($dupsNip) . ")" . PHP_EOL;

// Check Jabatan table
$hasJabatan = $legacy->getSchemaBuilder()->hasTable('ref_jabatan');
echo "ref_jabatan exists: " . ($hasJabatan ? 'Yes' : 'No') . PHP_EOL;
if ($hasJabatan) {
    echo "ref_jabatan total: " . $legacy->table('ref_jabatan')->count() . PHP_EOL;
}
