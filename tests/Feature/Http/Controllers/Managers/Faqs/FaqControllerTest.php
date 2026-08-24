<?php

use App\Enums\FaqType;
use App\Models\Faq;
use App\Models\User;
use Database\Seeders\FaqSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

it(
  'shows only active knowledge base articles on the public knowledge base in display order',
  function () {
    $second = Faq::factory()
      ->knowledgeBase()
      ->create([
      'name' => 'Second Guide',
      'code' => 'second-guide',
      'sort_order' => 2,
      'is_active' => true,
      'video_url' => 'https://www.youtube.com/live/dQw4w9WgXcQ'
    ]);
    $first = Faq::factory()
      ->knowledgeBase()
      ->create([
      'name' => 'First Guide',
      'code' => 'first-guide',
      'sort_order' => 1,
      'is_active' => true
    ]);
    Faq::factory()->faq()->create([
      'name' => 'Public FAQ',
      'code' => 'public-faq',
      'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
      'is_active' => true
    ]);
    Faq::factory()->knowledgeBase()->create([
      'name' => 'Inactive Guide',
      'code' => 'inactive-guide',
      'is_active' => false
    ]);

    $response = $this->get(route('knowledge-base'));

    $response->assertOk()->assertInertia(
      fn($page) => $page
        ->component('knowledge-base')
        ->has('faqs', 2)
        ->where('faqs.0.id', $first->id)
        ->where('faqs.1.id', $second->id)
        ->where('faqs.0.type', FaqType::KnowledgeBase->value)
        ->where(
          'faqs.1.youtube_embed_url',
          'https://www.youtube.com/embed/dQw4w9WgXcQ'
        )
    );

    $this->get(route('faqs'))
      ->assertOk()
      ->assertInertia(fn($page) => $page
        ->component('knowledge-base')
        ->has('faqs', 1)
        ->where('faqs.0.code', 'public-faq')
        ->where(
          'faqs.0.youtube_embed_url',
          'https://www.youtube.com/embed/dQw4w9WgXcQ'
        )
        ->where('contentType', FaqType::Faq->value));
  }
);

it('provides explicit faq and knowledge base model scopes', function () {
  $faq = Faq::factory()->faq()->create();
  $guide = Faq::factory()->knowledgeBase()->create();

  expect(Faq::query()->faqs()->pluck('id')->all())->toContain($faq->id)
    ->not->toContain($guide->id);
  expect(Faq::query()->knowledgeBase()->pluck('id')->all())
    ->toContain($guide->id)
    ->not->toContain($faq->id);
  expect(Faq::factory()->create()->fresh()->type)->toBe(FaqType::Faq);
});

it('filters the manager content library by type', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  Faq::factory()->faq()->create(['code' => 'manager-faq']);
  Faq::factory()
    ->knowledgeBase()
    ->create(['code' => 'manager-guide']);

  actingAs($admin);

  $this->get(route('managers.faqs.index', ['type' => FaqType::KnowledgeBase->value]))
    ->assertOk()
    ->assertInertia(fn($page) => $page
      ->component('managers/faqs/list-faqs')
      ->has('faqs.data', 1)
      ->where('faqs.data.0.code', 'manager-guide')
      ->where('faqs.data.0.type', FaqType::KnowledgeBase->value));

  $this->get(route('managers.faqs.index', ['search' => 'knowledge_base']))
    ->assertOk()
    ->assertInertia(fn($page) => $page
      ->has('faqs.data', 1)
      ->where('faqs.data.0.code', 'manager-guide'));
});

it('seeds faq records and representative knowledge base guides idempotently', function () {
  seed(FaqSeeder::class);

  expect(Faq::query()->faqs()->count())->toBe(61);
  expect(Faq::query()->knowledgeBase()->count())->toBe(5);
  expect(Faq::query()->where('code', 'login-main-dashboard')->value('type'))
    ->toBe(FaqType::Faq);

  seed(FaqSeeder::class);

  expect(Faq::query()->count())->toBe(66);
});

it(
  'allows admin managers to create update toggle preview and delete faqs',
  function () {
    $admin = User::factory()
      ->adminManager()
      ->create();

    actingAs($admin);

    $payload = [
      'name' => 'How do I use FAQ videos?',
      'code' => 'faq-videos',
      'description' =>
        '<h2>Answer</h2><p>Use <strong>YouTube</strong>.</p><script>alert(1)</script>',
      'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
      'type' => FaqType::Faq->value,
      'is_active' => true,
      'sort_order' => 5
    ];

    $this->postJson(route('managers.faqs.store'), $payload)
      ->assertOk()
      ->assertJsonPath('faq.youtube_video_id', 'dQw4w9WgXcQ')
      ->assertJsonPath(
        'faq.youtube_embed_url',
        'https://www.youtube.com/embed/dQw4w9WgXcQ'
      );

    $faq = Faq::query()
      ->where('code', 'faq-videos')
      ->firstOrFail();

    expect($faq->description)
      ->toContain('<h2>Answer</h2>')
      ->not->toContain('<script>');

    $this->putJson(route('managers.faqs.update', [$faq]), [
      ...$payload,
      'name' => 'Updated FAQ',
      'video_url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
      'type' => FaqType::KnowledgeBase->value
    ])
      ->assertOk()
      ->assertJsonPath('faq.name', 'Updated FAQ')
      ->assertJsonPath('faq.type', FaqType::KnowledgeBase->value);

    $this->postJson(route('managers.faqs.toggle', [$faq]))
      ->assertOk()
      ->assertJsonPath('faq.is_active', false);

    $this->get(route('managers.faqs.show', [$faq]))
      ->assertOk()
      ->assertInertia(fn($page) => $page->component('managers/faqs/show-faq'));

    $this->deleteJson(route('managers.faqs.destroy', [$faq]))->assertOk();

    $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
  }
);

it('prevents partner managers from managing faqs', function () {
  $partner = User::factory()
    ->partnerManager()
    ->create();
  $faq = Faq::factory()->create();

  actingAs($partner)
    ->get(route('managers.faqs.index'))
    ->assertRedirect(route('user.dashboard'));

  actingAs($partner)
    ->postJson(route('managers.faqs.store'), [
      'name' => 'Blocked FAQ',
      'code' => 'blocked-faq',
      'description' => '<p>Blocked</p>',
      'is_active' => true
    ])
    ->assertForbidden();

  expect($faq->fresh())->not->toBeNull();
});

it('validates faq youtube urls instead of storing iframe html', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();

  actingAs($admin)
    ->postJson(route('managers.faqs.store'), [
      'name' => 'Invalid video FAQ',
      'code' => 'invalid-video-faq',
      'description' => '<p>Answer</p>',
      'type' => 'invalid',
      'video_url' => 'https://example.com/watch?v=dQw4w9WgXcQ',
      'is_active' => true
    ])
    ->assertUnprocessable()
    ->assertJsonValidationErrors(['type', 'video_url']);
});
