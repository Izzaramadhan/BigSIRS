<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$legacy = DB::connection('legacy');

$paks = $legacy->table('ref_paket_tindakan')->count();
$maps = $legacy->table('map_paket_tindakan')->count();

echo "Total Packages: $paks\n";
echo "Total Details: $maps\n";

$emptyPaks = $legacy->select("SELECT COUNT(*) as c FROM ref_paket_tindakan p LEFT JOIN map_paket_tindakan m ON p.id = m.id_ref_paket_tindakan WHERE m.id IS NULL")[0]->c;
echo "Packages Without Details: $emptyPaks\n";

$dupes = $legacy->select("SELECT id_ref_paket_tindakan, id_ref_tarif_tindakan, COUNT(*) as c FROM map_paket_tindakan GROUP BY id_ref_paket_tindakan, id_ref_tarif_tindakan HAVING c > 1");
echo "Duplicate Details: " . count($dupes) . "\n";
if (count($dupes) > 0) {
    print_r($dupes[0]);
}

$orphans = $legacy->select("SELECT COUNT(*) as c FROM map_paket_tindakan m LEFT JOIN ref_tarif_tindakan t ON m.id_ref_tarif_tindakan = t.id WHERE t.id IS NULL")[0]->c;
echo "Details without valid Tariff: $orphans\n";

$deletedDetails = $legacy->table('map_paket_tindakan')->whereNotNull('deleted_at')->where('deleted_at', '!=', '0000-00-00 00:00:00')->count();
echo "Soft-deleted details: $deletedDetails\n";

$deletedHeaders = $legacy->table('ref_paket_tindakan')->whereNotNull('deleted_at')->where('deleted_at', '!=', '0000-00-00 00:00:00')->count();
echo "Soft-deleted headers: $deletedHeaders\n";

$duplicateNames = $legacy->select("SELECT nama, COUNT(*) as c FROM ref_paket_tindakan GROUP BY nama HAVING c > 1");
echo "Duplicate package names: " . count($duplicateNames) . "\n";
