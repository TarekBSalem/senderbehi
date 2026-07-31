<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

describe('authorization', function () {
    test('guests cannot view categories', function () {
        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('login'));
    });

    test('non-admin users cannot view categories', function () {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->actingAs($user)
            ->get(route('admin.categories.index'))
            ->assertStatus(403);
    });

    test('super admin can view categories', function () {
        Category::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/categories/index')
                ->has('categories.data', 3)
                ->has('filters')
            );
    });
});

describe('CRUD operations', function () {
    test('admin can create a category', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Test Category',
                'slug' => 'test-category',
                'is_active' => true,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'slug' => 'test-category',
            'is_active' => true,
        ]);
    });

    test('category slug must be unique', function () {
        Category::factory()->create(['slug' => 'existing']);

        $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Test',
                'slug' => 'existing',
            ])
            ->assertSessionHasErrors('slug');
    });

    test('admin can view create page', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/categories/create'));
    });

    test('admin can view edit page', function () {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/categories/edit')
                ->where('category.id', $category->id)
            );
    });

    test('admin can update a category', function () {
        $category = Category::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'New Name',
                'slug' => $category->slug,
                'is_active' => $category->is_active,
            ])
            ->assertSessionHas('success');

        expect($category->fresh()->name)->toBe('New Name');
    });

    test('admin can delete a category without templates', function () {
        $category = Category::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    test('admin cannot delete a category with templates', function () {
        $category = Category::factory()->hasTemplates(2)->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    });
});

describe('search and filter', function () {
    test('can search categories by name', function () {
        Category::factory()->create(['name' => 'Unique Name', 'slug' => 'unique-name']);
        Category::factory()->create(['name' => 'Other', 'slug' => 'other']);

        $this->actingAs($this->admin)
            ->get(route('admin.categories.index', ['search' => 'Unique']))
            ->assertInertia(fn ($page) => $page
                ->has('categories.data', 1)
            );
    });
});
