<?php

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->template = Template::factory()->create();
});

describe('Campaign Index', function () {
    test('guests cannot view campaigns', function () {
        $response = $this->get(route('campaigns.index'));
        $response->assertRedirect(route('login'));
    });

    test('authenticated user can view their campaigns', function () {
        $this->actingAs($this->user);

        Campaign::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->get(route('campaigns.index'));
        $response->assertOk();
    });

    test('user cannot view other users campaigns', function () {
        $this->actingAs($this->user);

        Campaign::factory()->count(5)->create();

        $response = $this->get(route('campaigns.index'));
        $response->assertOk();
    });
});

describe('Campaign Create', function () {
    test('authenticated user can view create form', function () {
        $this->actingAs($this->user);

        $response = $this->get(route('campaigns.create'));
        $response->assertOk();
    });

    test('create form includes active templates', function () {
        $this->actingAs($this->user);

        $activeTemplate = Template::factory()->create(['is_active' => true]);
        $inactiveTemplate = Template::factory()->inactive()->create();

        $response = $this->get(route('campaigns.create'));
        $response->assertOk();
    });
});

describe('Campaign Store', function () {
    test('authenticated user can create a campaign', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Test Campaign',
            'template_id' => $this->template->id,
            'subject' => 'Test Subject',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
    });

    test('campaign creation requires name', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'template_id' => $this->template->id,
            'subject' => 'Test Subject',
        ]);

        $response->assertSessionHasErrors('name');
    });

    test('campaign creation requires template_id', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Test Campaign',
            'subject' => 'Test Subject',
        ]);

        $response->assertSessionHasErrors('template_id');
    });

    test('campaign creation requires valid template_id', function () {
        $this->actingAs($this->user);

        $response = $this->post(route('campaigns.store'), [
            'name' => 'Test Campaign',
            'template_id' => 9999,
            'subject' => 'Test Subject',
        ]);

        $response->assertSessionHasErrors('template_id');
    });
});

describe('Campaign Show', function () {
    test('authenticated user can view their campaign', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->get(route('campaigns.show', $campaign));
        $response->assertOk();
    });

    test('user cannot view other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->get(route('campaigns.show', $campaign));
        $response->assertStatus(403);
    });
});

describe('Campaign Update', function () {
    test('user can update their draft campaign', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);

        $response = $this->put(route('campaigns.update', $campaign), [
            'name' => 'Updated Campaign',
            'subject' => 'Updated Subject',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Updated Campaign',
        ]);
    });

    test('user cannot update other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->put(route('campaigns.update', $campaign), [
            'name' => 'Hacked Campaign',
        ]);

        $response->assertStatus(403);
    });
});

describe('Campaign Delete', function () {
    test('user can delete their draft campaign', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);

        $response = $this->delete(route('campaigns.destroy', $campaign));
        $response->assertRedirect(route('campaigns.index'));
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    });

    test('user cannot delete other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->delete(route('campaigns.destroy', $campaign));
        $response->assertStatus(403);
    });
});

describe('Campaign Send', function () {
    test('user can send draft campaign with recipients', function () {
        $this->actingAs($this->user);

        Queue::fake();

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'status' => 'queued',
        ]);
    });

    test('cannot send campaign without recipients', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->draft()->create(['user_id' => $this->user->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('error');
    });

    test('cannot send already sent campaign', function () {
        $this->actingAs($this->user);

        $campaign = Campaign::factory()->sent()->create(['user_id' => $this->user->id]);
        CampaignRecipient::factory()->count(3)->sent()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertSessionHas('error');
    });

    test('user cannot send other users campaign', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->draft()->create(['user_id' => $otherUser->id]);
        CampaignRecipient::factory()->count(3)->pending()->create(['campaign_id' => $campaign->id]);

        $response = $this->post(route('campaigns.send', $campaign));
        $response->assertStatus(403);
    });
});
