<?php

use App\Models\Category;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->category = Category::factory()->create(['name' => 'newsletter']);
});

describe('authorization', function () {
    test('guests cannot view templates', function () {
        $this->get(route('admin.templates.index'))
            ->assertRedirect(route('login'));
    });

    test('non-admin users cannot view templates', function () {
        $user = User::factory()->create();
        $user->assignRole('User');

        $this->actingAs($user)
            ->get(route('admin.templates.index'))
            ->assertStatus(403);
    });

    test('super admin can view templates', function () {
        Template::factory()->count(3)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.templates.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/templates/index')
                ->has('templates.data', 3)
                ->has('categories')
                ->has('filters')
            );
    });
});

describe('CRUD operations', function () {
    test('admin can create a template', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.templates.store'), [
                'name' => 'Test Template',
                'slug' => 'test-template',
                'subject' => 'Test Subject',
                'body_html' => '<html><body>Hello {{name}}</body></html>',
                'category_id' => $this->category->id,
                'is_active' => true,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('templates', [
            'slug' => 'test-template',
            'variables' => json_encode(['name']),
        ]);
    });

    test('template slug must be unique', function () {
        Template::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($this->admin)
            ->post(route('admin.templates.store'), [
                'name' => 'Test',
                'slug' => 'existing-slug',
                'subject' => 'Subject',
                'body_html' => '<html></html>',
            ])
            ->assertSessionHasErrors('slug');
    });

    test('admin can view create page', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.templates.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/templates/create'));
    });

    test('admin can view edit page', function () {
        $template = Template::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.templates.edit', $template))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/templates/edit')
                ->where('template.id', $template->id)
            );
    });

    test('admin can update a template', function () {
        $template = Template::factory()->create(['name' => 'Old Name']);

        $this->actingAs($this->admin)
            ->put(route('admin.templates.update', $template), [
                'name' => 'New Name',
                'slug' => $template->slug,
                'subject' => $template->subject,
                'body_html' => $template->body_html,
                'category_id' => $template->category_id,
                'is_active' => $template->is_active,
            ])
            ->assertSessionHas('success');

        expect($template->fresh()->name)->toBe('New Name');
    });

    test('admin can delete a template', function () {
        $template = Template::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.templates.destroy', $template))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    });

    test('variables are auto-extracted from body_html', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.templates.store'), [
                'name' => 'Var Template',
                'slug' => 'var-template',
                'subject' => 'Hi {{name}}',
                'body_html' => '<html>Hello {{name}}, your {{plan}} is active</html>',
                'category_id' => $this->category->id,
                'is_active' => true,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('templates', [
            'slug' => 'var-template',
            'variables' => json_encode(['name', 'plan']),
        ]);
    });
});

describe('search and filter', function () {
    test('can filter templates by category', function () {
        $cat1 = Category::factory()->create(['name' => 'A']);
        $cat2 = Category::factory()->create(['name' => 'B']);
        Template::factory()->create(['category_id' => $cat1->id]);
        Template::factory()->create(['category_id' => $cat2->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.templates.index', ['category' => $cat1->id]))
            ->assertInertia(fn ($page) => $page
                ->has('templates.data', 1)
            );
    });

    test('can filter by active status', function () {
        Template::factory()->count(2)->create(['is_active' => true]);
        Template::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin)
            ->get(route('admin.templates.index', ['is_active' => '1']))
            ->assertInertia(fn ($page) => $page
                ->has('templates.data', 2)
            );
    });

    test('can search templates by name', function () {
        Template::factory()->create(['name' => 'UniqueSearchTerm', 'slug' => 'unique-search-term']);
        Template::factory()->create(['name' => 'Other Template', 'slug' => 'other-template']);

        $this->actingAs($this->admin)
            ->get(route('admin.templates.index', ['search' => 'UniqueSearchTerm']))
            ->assertInertia(fn ($page) => $page
                ->has('templates.data', 1)
            );
    });
});
