<?php

$countNull = DB::connection('legacy')->table('ref_kelurahan')->whereNull('deleted_at')->orWhere('deleted_at', '')->orWhere('deleted_at', '0000-00-00 00:00:00')->count();
$countNotNull = DB::connection('legacy')->table('ref_kelurahan')->whereNotNull('deleted_at')->where('deleted_at', '!=', '')->where('deleted_at', '!=', '0000-00-00 00:00:00')->count();
print_r('Null/Empty deleted_at: '.$countNull."\n");
print_r('Not null deleted_at: '.$countNotNull."\n");

$sample = DB::connection('legacy')->table('ref_kelurahan')->whereNotNull('deleted_at')->limit(1)->first();
print_r($sample);
