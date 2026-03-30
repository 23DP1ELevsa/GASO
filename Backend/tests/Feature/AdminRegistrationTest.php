<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_admin_can_be_registered_publicly(): void
    {
        $response = $this->postJson('/api/register/admin', [
            'name' => 'Anna',
            'surname' => 'Admina',
            'email' => 'admin@gaso.lv',
            'phone' => '+37120000001',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.actorType', 'employee')
            ->assertJsonPath('data.user.email', 'admin@gaso.lv')
            ->assertJsonPath('data.user.role', 'administrators');

        $this->assertDatabaseHas('employees', [
            'email' => 'admin@gaso.lv',
            'role' => 'administrators',
        ]);

        $employee = Employee::query()->where('email', 'admin@gaso.lv')->firstOrFail();

        $this->assertTrue(Hash::check('password', $employee->password));
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_public_admin_registration_is_blocked_after_first_admin_exists(): void
    {
        Employee::create([
            'name' => 'Esošs',
            'surname' => 'Administrators',
            'email' => 'existing-admin@gaso.lv',
            'phone' => '+37120000001',
            'password' => 'password',
            'role' => 'administrators',
        ]);

        $response = $this->postJson('/api/register/admin', [
            'name' => 'Anna',
            'surname' => 'Admina',
            'email' => 'admin@gaso.lv',
            'phone' => '+37120000002',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Publiska administratora reģistrācija vairs nav pieejama.');

        $this->assertDatabaseMissing('employees', [
            'email' => 'admin@gaso.lv',
        ]);
    }

    public function test_registration_status_reports_whether_public_admin_signup_is_available(): void
    {
        $this->getJson('/api/register/admin')
            ->assertOk()
            ->assertJsonPath('data.available', true);

        Employee::create([
            'name' => 'Esošs',
            'surname' => 'Administrators',
            'email' => 'existing-admin@gaso.lv',
            'phone' => '+37120000001',
            'password' => 'password',
            'role' => 'administrators',
        ]);

        $this->getJson('/api/register/admin')
            ->assertOk()
            ->assertJsonPath('data.available', false);
    }
}