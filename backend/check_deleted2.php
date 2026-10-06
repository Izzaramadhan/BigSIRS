<?php
$res = DB::connection('legacy')->table('ref_kelurahan')->selectRaw("deleted_at, count(*) as count")->groupBy('deleted_at')->get();
print_r($res->toArray());
