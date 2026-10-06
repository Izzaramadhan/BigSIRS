<?php

$active_layanan = DB::connection('legacy')->table('ref_jenis_layanan')
    ->whereNull('deleted_at')
    ->orWhere('deleted_at', '0000-00-00 00:00:00')
    ->get();

dump('Active ref_jenis_layanan count:', $active_layanan->count());
dump('Sample active:', $active_layanan->pluck('nama')->toArray());
exit;
