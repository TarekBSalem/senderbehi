<?php

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('Template Index', function () {
    test('authenticated user can view templates', function () {
        $this->actingAs($this->user);

        Template::factory()->count(3)->create();

        $response = $this->get(route('templates.index'));
        $response->assertOk();
    });

    test('only active templates are shown', function () {
        $this->actingAs($this->user);

        Template::factory()->count(2)->create(['is_active' => true]);
        Template::factory()->inactive()->create();

        $response = $this->get(route('templates.index'));
        $response->assertOk();
    });

    test('templates can be filtered by category', function () {
        $this->actingAs($this->user);

        Template::factory()->create(['category' => 'newsletter', 'is_active' => true]);
        Template::factory()->create(['category' => 'promotional', 'is_active' => true]);

        $response = $this->get(route('templates.index', ['category' => 'newsletter']));
        $response->assertOk();
    });

    test('templates can be searched by name', function () {
        $this->actingAs($this->user);

        Template::factory()->create(['name' => 'Welcome Template', 'is_active' => true]);
        Template::factory()->create(['name' => 'Newsletter Template', 'is_active' => true]);

        $response = $this->get(route('templates.index', ['search' => 'Welcome']));
        $response->assertOk();
    });
});

describe('Template Show', function () {
    test('authenticated user can view template by slug', function () {
        $this->actingAs($this->user);

        $template = Template::factory()->create(['is_active' => true]);

        $response = $this->get(route('templates.show', $template->slug));
        $response->assertOk();
    });

    test('inactive templates cannot be viewed', function () {
        $this->actingAs($this->user);

        $template = Template::factory()->inactive()->create();

        $response = $this->get(route('templates.show', $template->slug));
        $response->assertStatus(404);
    });

    test('non-existent template returns 404', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('templates.show', 'non-existent-slug'));
        $response->assertStatus(404);
    });
});
