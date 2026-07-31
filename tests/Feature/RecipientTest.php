<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
});

describe('Recipient Index', function () {
    test('user can list recipients for their campaign', function () {
        $this->actingAs($this->user);

        CampaignRecipient::factory()->count(3)->create(['campaign_id' => $this->campaign->id]);

        $response = $this->getJson(route('recipients.index', $this->campaign));
        $response->assertOk();
        $response->assertJsonCount(3, 'recipients');
    });

    test('user cannot list recipients for other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherCampaign = Campaign::factory()->draft()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson(route('recipients.index', $otherCampaign));
        $response->assertStatus(403);
    });
});

describe('Recipient Store', function () {
    test('user can add recipients to their draft campaign', function () {
        $this->actingAs($this->user);

        $response = $this->postJson(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['email' => 'test1@example.com', 'name' => 'Test User 1'],
                ['email' => 'test2@example.com', 'name' => 'Test User 2'],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'added' => 2, 'duplicates' => 0]);

        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'test1@example.com',
        ]);
    });

    test('recipients are added as pending', function () {
        $this->actingAs($this->user);

        $response = $this->postJson(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['email' => 'test@example.com'],
            ],
        ]);

        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);
    });

    test('duplicate recipients are detected', function () {
        $this->actingAs($this->user);

        CampaignRecipient::factory()->create([
            'campaign_id' => $this->campaign->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['email' => 'existing@example.com'],
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'added' => 0, 'duplicates' => 1]);
    });

    test('cannot add recipients to sent campaign', function () {
        $this->actingAs($this->user);

        $this->campaign->update(['status' => 'sent']);

        $response = $this->postJson(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['email' => 'test@example.com'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    });

    test('email is required for recipients', function () {
        $this->actingAs($this->user);

        $response = $this->postJson(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['name' => 'Test User'],
            ],
        ]);

        $response->assertStatus(422);
    });

    test('user cannot add recipients to other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherCampaign = Campaign::factory()->draft()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson(route('recipients.store', $otherCampaign), [
            'recipients' => [
                ['email' => 'test@example.com'],
            ],
        ]);

        $response->assertStatus(403);
    });
});

describe('Recipient Import CSV', function () {
    test('user can import recipients from CSV', function () {
        $this->actingAs($this->user);

        $csvContent = "email,name\njohn@example.com,John Doe\njane@example.com,Jane Smith\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->postJson(route('recipients.import', $this->campaign), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'added' => 2]);

        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    });

    test('CSV without email column is rejected', function () {
        $this->actingAs($this->user);

        $csvContent = "name,phone\nJohn Doe,555-1234\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->postJson(route('recipients.import', $this->campaign), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    });

    test('invalid emails are skipped during import', function () {
        $this->actingAs($this->user);

        $csvContent = "email\nvalid@example.com\ninvalid-email\nalso.valid@test.com\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->postJson(route('recipients.import', $this->campaign), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'added' => 2]);

        $this->assertDatabaseMissing('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'invalid-email',
        ]);
    });

    test('duplicate emails are skipped during import', function () {
        $this->actingAs($this->user);

        CampaignRecipient::factory()->create([
            'campaign_id' => $this->campaign->id,
            'email' => 'existing@example.com',
        ]);

        $csvContent = "email\nexisting@example.com\nnew@example.com\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->postJson(route('recipients.import', $this->campaign), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'added' => 1, 'duplicates' => 1]);
    });

    test('cannot import to sent campaign', function () {
        $this->actingAs($this->user);

        $this->campaign->update(['status' => 'sent']);

        $csvContent = "email\ntest@example.com\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->postJson(route('recipients.import', $this->campaign), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    });
});

describe('Recipient Delete', function () {
    test('user can delete pending recipient', function () {
        $this->actingAs($this->user);

        $recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $response = $this->deleteJson(route('recipients.destroy', [$this->campaign, $recipient]));
        $response->assertOk();
        $this->assertDatabaseMissing('campaign_recipients', ['id' => $recipient->id]);
    });

    test('cannot delete sent recipient', function () {
        $this->actingAs($this->user);

        $recipient = CampaignRecipient::factory()->sent()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $response = $this->deleteJson(route('recipients.destroy', [$this->campaign, $recipient]));
        $response->assertStatus(422);
        $this->assertDatabaseHas('campaign_recipients', ['id' => $recipient->id]);
    });

    test('user cannot delete recipient from other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherCampaign = Campaign::factory()->draft()->create(['user_id' => $otherUser->id]);
        $recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $otherCampaign->id,
        ]);

        $response = $this->deleteJson(route('recipients.destroy', [$otherCampaign, $recipient]));
        $response->assertStatus(403);
    });
});

describe('Recipient Inertia Responses', function () {
    test('inertia request to add recipients redirects back with toast', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['email' => 'test1@example.com', 'name' => 'Test User 1'],
            ],
        ], ['X-Inertia' => true]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast');
        $response->assertInertiaFlash('importResult', ['added' => 1, 'duplicates' => 0, 'invalid' => 0]);

        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'test1@example.com',
        ]);
    });

    test('inertia request to import recipients redirects back with toast', function () {
        $this->actingAs($this->user);

        $csvContent = "email,name\njohn@example.com,John Doe\nnot-an-email,Bad Row\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->post(route('recipients.import', $this->campaign), [
            'file' => $file,
        ], ['X-Inertia' => true]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast');
        $response->assertInertiaFlash('importResult', ['added' => 1, 'duplicates' => 0, 'invalid' => 1]);

        $this->assertDatabaseHas('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'john@example.com',
        ]);
    });

    test('inertia request to delete recipient redirects back with toast', function () {
        $this->actingAs($this->user);

        $recipient = CampaignRecipient::factory()->pending()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $response = $this->delete(route('recipients.destroy', [$this->campaign, $recipient]), [], ['X-Inertia' => true]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast');

        $this->assertDatabaseMissing('campaign_recipients', ['id' => $recipient->id]);
    });

    test('inertia request to add recipients to sent campaign redirects with error toast', function () {
        $this->actingAs($this->user);

        $this->campaign->update(['status' => 'sent']);

        $response = $this->post(route('recipients.store', $this->campaign), [
            'recipients' => [
                ['email' => 'test@example.com'],
            ],
        ], ['X-Inertia' => true]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast');

        $this->assertDatabaseMissing('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
            'email' => 'test@example.com',
        ]);
    });

    test('inertia request to import csv without email column redirects with error toast', function () {
        $this->actingAs($this->user);

        $csvContent = "name,phone\nJohn Doe,555-1234\n";
        $file = UploadedFile::fake()->createWithContent('recipients.csv', $csvContent);

        $response = $this->post(route('recipients.import', $this->campaign), [
            'file' => $file,
        ], ['X-Inertia' => true]);

        $response->assertRedirect();
        $response->assertInertiaFlash('toast');

        $this->assertDatabaseMissing('campaign_recipients', [
            'campaign_id' => $this->campaign->id,
        ]);
    });
});
