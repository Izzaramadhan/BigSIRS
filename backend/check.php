<?php
$all = DB::connection('legacy')->table('ref_kategori_lab')->get();
$groups = $all->groupBy('status')->map->count();
dump($groups->toArray());
exit;
