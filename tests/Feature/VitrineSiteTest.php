<?php

use App\Models\BlogPost;
use App\Models\ChangelogEntry;
use App\Models\Doc;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(SubscriptionPlanSeeder::class);
});

describe('landing page', function () {
    test('home page loads successfully', function () {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('welcome'));
    });
});

describe('pricing page', function () {
    test('pricing page shows plans from database', function () {
        $response = $this->get(route('pricing'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('public/pricing')
            ->has('plans', 4)
        );
    });

    test('pricing page shows correct plan data', function () {
        $response = $this->get(route('pricing'));

        $response->assertInertia(fn ($page) => $page
            ->where('plans.0.name', 'Free')
            ->where('plans.0.price_cents', 0)
            ->where('plans.1.name', 'Starter')
            ->where('plans.1.price_cents', 900)
        );
    });
});

describe('features page', function () {
    test('features page loads successfully', function () {
        $response = $this->get(route('features'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('public/features')
            ->has('features', 6)
        );
    });
});

describe('contact page', function () {
    test('contact page loads successfully', function () {
        $response = $this->get(route('contact'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('public/contact'));
    });

    test('contact form validates required fields', function () {
        $response = $this->post(route('contact.send'), []);

        $response->assertSessionHasErrors(['name', 'email', 'message']);
    });

    test('contact form submits successfully', function () {
        $response = $this->post(route('contact.send'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'This is a test message.',
        ]);

        $response->assertSessionHas('success');
    });
});

describe('legal pages', function () {
    test('privacy page loads successfully', function () {
        $response = $this->get(route('privacy'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('public/privacy'));
    });

    test('terms page loads successfully', function () {
        $response = $this->get(route('terms'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('public/terms'));
    });
});

describe('docs pages', function () {
    test('docs index loads successfully', function () {
        Doc::factory()->count(3)->create();

        $response = $this->get(route('docs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('public/docs/index'));
    });

    test('doc show page loads successfully', function () {
        $doc = Doc::factory()->create();

        $response = $this->get(route('docs.show', $doc));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('public/docs/show')
            ->where('doc.id', $doc->id)
        );
    });
});

describe('blog pages', function () {
    test('blog index loads successfully', function () {
        BlogPost::factory()->count(3)->create();

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('public/blog/index'));
    });

    test('blog show page loads successfully', function () {
        $post = BlogPost::factory()->create();

        $response = $this->get(route('blog.show', $post));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('public/blog/show')
            ->where('post.id', $post->id)
        );
    });
});

describe('changelog page', function () {
    test('changelog page loads successfully', function () {
        ChangelogEntry::factory()->count(2)->create();

        $response = $this->get(route('changelog'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('public/changelog'));
    });
});

describe('sitemap', function () {
    test('sitemap.xml returns valid XML', function () {
        Doc::factory()->count(2)->create();
        BlogPost::factory()->count(2)->create();

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
        $response->assertSee('</urlset>', false);
    });

    test('sitemap includes all public URLs', function () {
        $response = $this->get(route('sitemap'));

        $response->assertSee(url('/'));
        $response->assertSee(url('/pricing'));
        $response->assertSee(url('/features'));
        $response->assertSee(url('/contact'));
        $response->assertSee(url('/docs'));
        $response->assertSee(url('/blog'));
        $response->assertSee(url('/changelog'));
    });
});
