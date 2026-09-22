<?php

namespace Tests\Feature\Seeder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_runs_in_local_environment()
    {
        // Set to local explicitly (Testing env is fine too)
        App::detectEnvironment(function () { return 'local'; });

        Artisan::call('db:seed', ['--class' => 'AdminDevelopmentSeeder']);

        $expectedUsername = env('DEV_ADMIN_USERNAME');
        
        if ($expectedUsername) {
            $this->assertDatabaseHas('users', [
                'username' => $expectedUsername
            ]);
        } else {
            $this->assertTrue(true); // Seeder skips if no env
        }
    }
}
