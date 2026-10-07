<?php

namespace Tests\Feature;

use App\Models\MasterData\LaboratoryItem;
use App\Services\MasterData\LaboratoryItemImporter;
use App\Services\MasterData\LegacyLaboratoryItemSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ImportLegacyLaboratoryItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_idempotent_and_preserves_reference_symbols(): void
    {
        $rows = collect([
            (object) ['id' => 2, 'nama' => ' HBSAG ', 'standar_normal' => 'Negatif', 'satuan' => '', 'status' => '0'],
            (object) ['id' => 3, 'nama' => 'KOLESTEROL HDL', 'standar_normal' => '30-70', 'satuan' => 'mg/dL', 'status' => '1'],
            (object) ['id' => 5, 'nama' => 'KOLESTEROL TOTAL', 'standar_normal' => '<200', 'satuan' => 'mg/dL', 'status' => '1'],
        ]);

        $source = Mockery::mock(LegacyLaboratoryItemSource::class);
        $source->shouldReceive('chunk')->twice()->andReturnUsing(function ($callback) use ($rows): void {
            $callback($rows);
        });

        $importer = new LaboratoryItemImporter($source);

        $first = $importer->import();
        $second = $importer->import();

        $this->assertSame(3, $first['inserted']);
        $this->assertSame(3, $second['unchanged']);
        $this->assertSame(3, LaboratoryItem::count());
        $this->assertDatabaseHas('laboratory_items', ['legacy_id' => 2, 'name' => 'HBSAG', 'unit' => null]);
        $this->assertDatabaseHas('laboratory_items', ['legacy_id' => 5, 'reference_value' => '<200']);
    }

    public function test_import_keeps_duplicate_names_as_distinct_legacy_items(): void
    {
        $rows = collect([
            (object) ['id' => 4, 'nama' => 'KOLESTEROL TOTAL', 'standar_normal' => 'L: 45, P: 50', 'satuan' => 'ml', 'status' => '0'],
            (object) ['id' => 5, 'nama' => 'KOLESTEROL TOTAL', 'standar_normal' => '<200', 'satuan' => 'mg/dL', 'status' => '1'],
        ]);

        $source = Mockery::mock(LegacyLaboratoryItemSource::class);
        $source->shouldReceive('chunk')->once()->andReturnUsing(function ($callback) use ($rows): void {
            $callback($rows);
        });

        (new LaboratoryItemImporter($source))->import();

        $this->assertSame(2, LaboratoryItem::where('name', 'KOLESTEROL TOTAL')->count());
    }
}
