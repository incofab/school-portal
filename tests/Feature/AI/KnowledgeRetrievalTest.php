<?php

use App\DTO\AI\AssistantActorContext;
use App\Models\Faq;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationService;
use App\Services\AI\EduManagerAssistant;
use App\Services\AI\Knowledge\DatabaseKnowledgeRetriever;
use App\Services\AI\Knowledge\FaqPassageChunker;
use App\Services\AI\LaravelAiTextAgent;

beforeEach(function () {
  $this->retriever = app(DatabaseKnowledgeRetriever::class);
});

it('searches active FAQ entries directly and returns their source link', function () {
  $faq = Faq::factory()
    ->faq()
    ->create([
      'name' => 'How do I create a new student?',
      'description' => '<p>Open Students and choose Add Student to create a student profile.</p>'
    ]);

  $results = $this->retriever->search(
    'I need to add a student to my school',
    new AssistantActorContext(null, null, null, true)
  )->toArray();

  expect($results)
    ->toHaveCount(1)
    ->and($results[0]['id'])
    ->toBe("faq:{$faq->id}")
    ->and($results[0]['url'])
    ->toBe(url("faqs#{$faq->id}"));
});

it('searches knowledge base entries stored in the same faq table', function () {
  $article = Faq::factory()
    ->knowledgeBase()
    ->create([
      'name' => 'How do I publish a result?',
      'description' => '<p>Open Class Result Analysis and publish the processed result when it is ready.</p>'
    ]);

  $results = $this->retriever->search(
    'where can I publish a processed result',
    new AssistantActorContext(null, null, null, true)
  )->toArray();

  expect($results[0]['id'])
    ->toBe("faq:{$article->id}")
    ->and($results[0]['url'])
    ->toBe(url("knowledge-base#{$article->id}"));
});

it('uses FAQ updates immediately and ignores inactive entries', function () {
  $inactive = Faq::factory()->faq()->create([
    'name' => 'Inactive attendance guide',
    'description' => '<p>Use the attendance tool to mark students present.</p>',
    'is_active' => false,
  ]);

  $context = new AssistantActorContext(null, null, null, true);

  expect($this->retriever->search('attendance guide', $context)->isEmpty())
    ->toBeTrue();

  $inactive->update([
    'is_active' => true,
    'description' => '<p>Open Attendance, select a class, then mark students present or absent.</p>',
  ]);

  expect($this->retriever->search('where do I mark attendance', $context)->isEmpty())
    ->toBeFalse();
});

it('splits faq descriptions into passages and returns the best matching passage', function () {
  config([
    'ai.knowledge.chunk_size' => 200,
    'ai.knowledge.chunk_overlap' => 40,
  ]);
  $faq = Faq::factory()->knowledgeBase()->create([
    'name' => 'Using school reports',
    'description' => '<p>'.str_repeat('General information not about this report. ', 8).'</p>'
      .'<p>Attendance reports: open Attendance, select the class, then choose the date range.</p>',
  ]);

  $result = $this->retriever->search(
    'where can I find attendance reports for a class by date',
    new AssistantActorContext(null, null, null, true)
  )->toArray()[0];

  expect($result['id'])
    ->toBe("faq:{$faq->id}")
    ->and($result['excerpt'])
    ->toContain('Attendance reports')
    ->not->toContain('General information')
    ->and(mb_strlen($result['excerpt']))
    ->toBeLessThanOrEqual(420);
});

it('splits long paragraphs into bounded overlapping passages', function () {
  config([
    'ai.knowledge.chunk_size' => 200,
    'ai.knowledge.chunk_overlap' => 40,
  ]);

  $chunks = app(FaqPassageChunker::class)->chunk(str_repeat('0123456789', 60));

  expect(count($chunks))
    ->toBeGreaterThan(1)
    ->and(max(array_map('mb_strlen', $chunks)))
    ->toBeLessThanOrEqual(200)
    ->and(mb_substr($chunks[0], -40))
    ->toBe(mb_substr($chunks[1], 0, 40));
});

it('ranks the faq passage that best answers a question above distractors', function () {
  foreach ([
    [
      'How do I record attendance?',
      '<p>Open Attendance and mark each student present or absent.</p>',
    ],
    [
      'How do I add a bank account?',
      '<p>Open Bank Accounts and supply the account number and bank name.</p>',
    ],
    [
      'How do I generate result checker pins?',
      '<p>Open Pins and generate a batch of result checker pins for the term.</p>',
    ],
    [
      'How do I pay school fees online?',
      '<p>Open Fees, choose the outstanding fee and pay online with a card or bank transfer.</p>',
    ],
  ] as [$name, $description]) {
    Faq::factory()->faq()->create(compact('name', 'description'));
  }

  $results = $this->retriever->search(
    'my son needs to settle his outstanding school fees over the internet',
    new AssistantActorContext(null, null, null, true)
  )->toArray();

  expect($results)->not->toBeEmpty()
    ->and($results[0]['title'])->toBe('How do I pay school fees online?');
});

it('returns no source when no faq passage matches the question', function () {
  Faq::factory()->faq()->create([
    'name' => 'How do I record attendance?',
    'description' => '<p>Open Attendance and mark each student present or absent.</p>',
  ]);

  $results = $this->retriever->search(
    'what is the launch date of the next hardware product line',
    new AssistantActorContext(null, null, null, true)
  );

  expect($results->isEmpty())->toBeTrue();
});

it('respects the configured maximum number of source entries', function () {
  config(['ai.knowledge.max_results' => 2]);

  foreach (range(1, 6) as $index) {
    Faq::factory()->faq()->create([
      'name' => "How do I publish a result, part {$index}?",
      'description' => "<p>Publishing a result requires processing the result first. Step {$index}.</p>",
    ]);
  }

  $results = $this->retriever->search(
    'how do I publish a result',
    new AssistantActorContext(null, null, null, true)
  );

  expect(count($results->toArray()))->toBeLessThanOrEqual(2);
});

it('includes FAQ citations with grounded assistant answers', function () {
  $faq = Faq::factory()->faq()->create([
    'name' => 'How do I create classes?',
    'description' => '<p>Open Classes and create the main class before adding divisions.</p>',
  ]);
  LaravelAiTextAgent::fake([
    'Create the main class first, then add divisions if needed.'
  ]);

  $context = app(AssistantContextResolver::class)->resolve();
  $conversation = app(AssistantConversationService::class)->create(
    $context,
    'grounded-test-token'
  );
  $updated = app(EduManagerAssistant::class)->reply(
    $conversation,
    'How do I create classes?',
    $context
  );
  $message = $updated->messages()->where('role', 'assistant')->firstOrFail();

  expect($message->meta['grounded'])
    ->toBeTrue()
    ->and($message->meta['sources'][0]['id'])
    ->toBe("faq:{$faq->id}")
    ->and($message->content)
    ->toContain('Sources:');
});
