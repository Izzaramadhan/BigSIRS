<?php

namespace Tests\Feature\Models\MasterData;

use Tests\TestCase;

class WarehouseTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
