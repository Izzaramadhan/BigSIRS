<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_captcha()
    {
        $response = $this->getJson('/api/v1/auth/captcha');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'captcha_id',
                         'captcha_image',
                         'expires_in'
                     ]
                 ]);
        
        $captchaId = $response->json('data.captcha_id');
        $this->assertTrue(Cache::has('captcha_' . $captchaId));
    }

    public function test_can_login_with_valid_username_and_captcha()
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'password' => bcrypt('password123'),
        ]);

        $captchaId = 'test-uuid';
        Cache::put('captcha_' . $captchaId, Hash::make('ABCDE'), 300);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'johndoe',
            'password' => 'password123',
            'captcha_id' => $captchaId,
            'captcha_answer' => 'abcde', // test lowercase is normalized
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_cannot_login_with_invalid_captcha()
    {
        $user = User::factory()->create([
            'username' => 'johndoe',
            'password' => bcrypt('password123'),
        ]);

        $captchaId = 'test-uuid';
        Cache::put('captcha_' . $captchaId, Hash::make('ABCDE'), 300);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'johndoe',
            'password' => 'password123',
            'captcha_id' => $captchaId,
            'captcha_answer' => 'WRONG',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Username, password, atau kode keamanan tidak valid.']);
    }

    public function test_cannot_login_with_invalid_username()
    {
        $captchaId = 'test-uuid';
        Cache::put('captcha_' . $captchaId, Hash::make('ABCDE'), 300);

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'wronguser',
            'password' => 'password123',
            'captcha_id' => $captchaId,
            'captcha_answer' => 'abcde',
        ]);

        $response->assertStatus(401);
    }

    public function test_can_access_me_with_token()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
    }

    public function test_cannot_access_me_without_token()
    {
        $response = $this->getJson('/api/v1/auth/me');
        $response->assertStatus(401);
    }

    public function test_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }
}
