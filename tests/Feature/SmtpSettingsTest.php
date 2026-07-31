<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('authorization', function () {
    test('guests cannot view smtp settings', function () {
        $this->get(route('smtp.edit'))
            ->assertRedirect(route('login'));
    });

    test('authenticated user can view smtp settings', function () {
        $this->actingAs($this->user)
            ->get(route('smtp.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/smtp')
                ->has('settings')
            );
    });
});

describe('CRUD operations', function () {
    test('user can save custom smtp settings', function () {
        $this->actingAs($this->user)
            ->put(route('smtp.update'), [
                'is_custom' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'user@example.com',
                'password' => 'secret',
                'encryption' => 'tls',
                'from_email' => 'newsletter@example.com',
                'from_name' => 'My Newsletter',
                'sends_per_minute' => 30,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('smtp.edit'));

        $this->assertDatabaseHas('smtp_settings', [
            'user_id' => $this->user->id,
            'is_custom' => true,
            'host' => 'smtp.example.com',
            'port' => 587,
            'username' => 'user@example.com',
            'encryption' => 'tls',
            'from_email' => 'newsletter@example.com',
            'from_name' => 'My Newsletter',
            'sends_per_minute' => 30,
        ]);
    });

    test('password is encrypted in database', function () {
        $this->actingAs($this->user)
            ->put(route('smtp.update'), [
                'is_custom' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'user@example.com',
                'password' => 'my-secret-password',
                'encryption' => 'tls',
                'from_email' => 'noreply@example.com',
            ]);

        $record = DB::table('smtp_settings')->first();

        expect($record->password)->not->toBe('my-secret-password');
        expect($record->password)->toStartWith('eyJ'); // Laravel encrypted prefix
    });

    test('user can disable custom smtp', function () {
        $this->actingAs($this->user)
            ->put(route('smtp.update'), [
                'is_custom' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('smtp_settings', [
            'user_id' => $this->user->id,
            'is_custom' => false,
            'host' => null,
            'port' => null,
            'username' => null,
            'from_email' => null,
        ]);
    });

    test('password is optional on update when already set', function () {
        $this->actingAs($this->user)
            ->put(route('smtp.update'), [
                'is_custom' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'user@example.com',
                'password' => 'original-password',
                'encryption' => 'tls',
                'from_email' => 'noreply@example.com',
            ]);

        $this->actingAs($this->user)
            ->put(route('smtp.update'), [
                'is_custom' => true,
                'host' => 'smtp.example.com',
                'port' => 587,
                'username' => 'user@example.com',
                'encryption' => 'tls',
                'from_email' => 'new@example.com',
            ])
            ->assertSessionHasNoErrors();
    });

    test('validation requires fields when custom is enabled', function () {
        $this->actingAs($this->user)
            ->put(route('smtp.update'), [
                'is_custom' => true,
                'host' => '',
                'port' => '',
                'username' => '',
            ])
            ->assertSessionHasErrors(['host', 'port', 'username', 'from_email']);
    });
});

describe('smtp test endpoint', function () {
    test('returns error for unreachable host', function () {
        $this->actingAs($this->user)
            ->postJson(route('smtp.test'), [
                'host' => '192.0.2.1',
                'port' => 12345,
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['success', 'message']);
    });

    test('validates test connection request', function () {
        $this->actingAs($this->user)
            ->postJson(route('smtp.test'), [
                'host' => '',
                'port' => 'not-a-port',
            ])
            ->assertStatus(422);
    });
});
