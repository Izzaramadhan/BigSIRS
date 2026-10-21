<?php

namespace Tests\Feature\Api\V1\MasterData;

use App\Models\Employee;
use App\Models\MasterData\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_employees(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/employees');

        $response->assertOk()
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => ['id', 'name', 'code', 'has_signature', 'signature_url'],
                ],
            ]);
    }

    public function test_can_create_employee_without_signature(): void
    {
        $position = Position::create(['name' => 'Perawat', 'is_active' => true]);

        $data = [
            'name' => 'Budi Santoso',
            'code' => 'PEG-001',
            'national_id' => '3201012345678901',
            'gender' => 'L',
            'position_id' => $position->id,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/employees', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Budi Santoso')
            ->assertJsonPath('data.has_signature', false)
            ->assertJsonPath('data.signature_url', null);

        $this->assertDatabaseHas('employees', [
            'name' => 'Budi Santoso',
            'code' => 'PEG-001',
            'signature_path' => null,
        ]);
    }

    public function test_can_create_employee_with_valid_signature(): void
    {
        Storage::fake('public');

        $position = Position::create(['name' => 'Perawat', 'is_active' => true]);
        $file = UploadedFile::fake()->createWithContent('ttd.png', 'fake-signature-content')->mimeType('image/png');

        $data = [
            'name' => 'Siti Rahma',
            'code' => 'PEG-002',
            'national_id' => '3201012345678902',
            'gender' => 'P',
            'position_id' => $position->id,
            'is_active' => true,
            'signature' => $file,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/employees', $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Siti Rahma')
            ->assertJsonPath('data.has_signature', true);

        $employee = Employee::where('name', 'Siti Rahma')->first();
        $this->assertNotNull($employee->signature_path);
        Storage::disk('public')->assertExists($employee->signature_path);
    }

    public function test_rejects_signature_with_invalid_format(): void
    {
        Storage::fake('public');

        $position = Position::create(['name' => 'Perawat', 'is_active' => true]);
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $data = [
            'name' => 'Ahmad',
            'code' => 'PEG-003',
            'national_id' => '3201012345678903',
            'position_id' => $position->id,
            'signature' => $pdfFile,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/employees', $data);

        $response->assertJsonValidationErrors(['signature']);
        $this->assertEquals('File tanda tangan harus berupa gambar.', $response->json('errors.signature.0'));

        // Test non-allowed image format like gif or svg
        $gifFile = UploadedFile::fake()->create('animation.gif', 100, 'image/gif');
        $data['signature'] = $gifFile;
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/employees', $data);
        $response->assertJsonValidationErrors(['signature']);
        $this->assertStringContainsString('Format tanda tangan harus berupa jpeg, png, atau jpg', $response->json('errors.signature.0'));
    }

    public function test_rejects_signature_exceeding_max_size(): void
    {
        Storage::fake('public');

        $position = Position::create(['name' => 'Perawat', 'is_active' => true]);
        $oversizedFile = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg');

        $data = [
            'name' => 'Joko',
            'code' => 'PEG-004',
            'national_id' => '3201012345678904',
            'position_id' => $position->id,
            'signature' => $oversizedFile,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/employees', $data);

        $response->assertJsonValidationErrors(['signature']);
        $this->assertStringContainsString('Ukuran tanda tangan maksimal 2MB', $response->json('errors.signature.0'));
    }

    public function test_can_show_employee_with_signature(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->create('ttd.jpg', 100, 'image/jpeg')->store('signatures', 'public');
        $employee = Employee::factory()->create([
            'signature_path' => $path,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/master-data/employees/'.$employee->id);

        $response->assertOk()
            ->assertJsonPath('id', $employee->id)
            ->assertJsonPath('has_signature', true)
            ->assertJsonPath('signature_url', Storage::disk('public')->url($path));
    }

    public function test_can_update_employee_without_changing_signature(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->create('old_ttd.png', 50, 'image/png')->store('signatures', 'public');
        $employee = Employee::factory()->create([
            'name' => 'Old Name',
            'signature_path' => $path,
        ]);

        $data = [
            'name' => 'Updated Name',
            'code' => $employee->code,
            'national_id' => $employee->national_id,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/employees/'.$employee->id, $data);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.has_signature', true);

        $employee->refresh();
        $this->assertEquals($path, $employee->signature_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_can_update_employee_with_new_signature_and_cleans_old_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->create('old_ttd.png', 50, 'image/png')->store('signatures', 'public');
        $employee = Employee::factory()->create([
            'name' => 'Employee 1',
            'signature_path' => $oldPath,
        ]);

        $newFile = UploadedFile::fake()->create('new_ttd.jpg', 80, 'image/jpeg');

        $data = [
            'name' => 'Employee 1 Updated',
            'signature' => $newFile,
        ];

        // Sending with POST and _method=PUT to simulate multipart/form-data
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/master-data/employees/'.$employee->id, array_merge($data, ['_method' => 'PUT']));

        $response->assertOk()
            ->assertJsonPath('data.name', 'Employee 1 Updated')
            ->assertJsonPath('data.has_signature', true);

        $employee->refresh();
        $this->assertNotEquals($oldPath, $employee->signature_path);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($employee->signature_path);
    }

    public function test_can_remove_signature_on_update_and_cleans_file(): void
    {
        Storage::fake('public');

        $oldPath = UploadedFile::fake()->create('old_ttd.png', 50, 'image/png')->store('signatures', 'public');
        $employee = Employee::factory()->create([
            'name' => 'Employee 2',
            'signature_path' => $oldPath,
        ]);

        $data = [
            'name' => 'Employee 2 Updated',
            'remove_signature' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/master-data/employees/'.$employee->id, $data);

        $response->assertOk()
            ->assertJsonPath('data.has_signature', false)
            ->assertJsonPath('data.signature_url', null);

        $employee->refresh();
        $this->assertNull($employee->signature_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_can_delete_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/master-data/employees/'.$employee->id);

        $response->assertOk()
            ->assertJsonPath('message', 'Data Pegawai berhasil dihapus.');

        $this->assertSoftDeleted('employees', [
            'id' => $employee->id,
        ]);
    }
}
