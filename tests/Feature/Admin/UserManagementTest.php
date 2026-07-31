<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->admin()->create();
    $this->regularUser = User::factory()->create()->assignRole('User');
});

describe('Admin Users Index', function () {
    test('guests cannot access admin users', function () {
        $response = $this->get(route('admin.users.index'));
        $response->assertRedirect(route('login'));
    });

    test('non-admin users cannot access admin users', function () {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('admin.users.index'));
        $response->assertStatus(403);
    });

    test('super admin can view users index', function () {
        $this->actingAs($this->superAdmin);

        User::factory()->count(3)->create()->each->assignRole('User');

        $response = $this->get(route('admin.users.index'));
        $response->assertOk();
    });
});

describe('Admin Users Create', function () {
    test('super admin can view create form', function () {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.users.create'));
        $response->assertOk();
    });

    test('super admin can create a user', function () {
        $this->actingAs($this->superAdmin);

        $this->post(route('admin.users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'User',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertTrue($user->hasRole('User'));
    });

    test('create requires valid data', function () {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('admin.users.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'role' => 'InvalidRole',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    });

    test('create requires unique email', function () {
        $this->actingAs($this->superAdmin);

        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'role' => 'User',
        ]);

        $response->assertSessionHasErrors('email');
    });
});

describe('Admin Users Show', function () {
    test('super admin can view user detail', function () {
        $this->actingAs($this->superAdmin);

        $user = User::factory()->create()->assignRole('User');

        $response = $this->get(route('admin.users.show', $user));
        $response->assertOk();
    });
});

describe('Admin Users Update Role', function () {
    test('super admin can update user role', function () {
        $this->actingAs($this->superAdmin);

        $user = User::factory()->create()->assignRole('User');

        $response = $this->put(route('admin.users.role', $user), [
            'role' => 'Admin',
        ]);

        $response->assertRedirect();
        $this->assertTrue($user->fresh()->hasRole('Admin'));
    });

    test('role update requires valid role', function () {
        $this->actingAs($this->superAdmin);

        $user = User::factory()->create()->assignRole('User');

        $response = $this->put(route('admin.users.role', $user), [
            'role' => 'NonExistentRole',
        ]);

        $response->assertSessionHasErrors('role');
    });
});

describe('Admin Users Block', function () {
    test('super admin can block a user', function () {
        $this->actingAs($this->superAdmin);

        $user = User::factory()->create()->assignRole('User');

        $response = $this->post(route('admin.users.block', $user));
        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->blocked_at);
    });

    test('blocked user is redirected to login on subsequent requests', function () {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->superAdmin);
        $this->post(route('admin.users.block', $user));

        $this->actingAs($user->fresh());
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    });
});

describe('Admin Users Unblock', function () {
    test('super admin can unblock a user', function () {
        $this->actingAs($this->superAdmin);

        $user = User::factory()->create()->assignRole('User');

        $this->post(route('admin.users.block', $user));
        $this->assertNotNull($user->fresh()->blocked_at);

        $response = $this->post(route('admin.users.unblock', $user));
        $response->assertRedirect();
        $this->assertNull($user->fresh()->blocked_at);
    });

    test('unblocked user can access the app again', function () {
        $user = User::factory()->create()->assignRole('User');

        $this->actingAs($this->superAdmin);
        $this->post(route('admin.users.block', $user));
        $this->post(route('admin.users.unblock', $user));

        $this->actingAs($user);
        $response = $this->get(route('dashboard'));
        $response->assertOk();
    });
});
