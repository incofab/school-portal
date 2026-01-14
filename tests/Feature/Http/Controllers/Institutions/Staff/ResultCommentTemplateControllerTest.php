<?php

use App\Enums\ResultCommentTemplateType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;

use function Pest\Laravel\{actingAs, assertDatabaseHas, assertDatabaseMissing};

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('lists result comment templates', function () {
  $existing = $this->institution->resultCommentTemplates()->count();

  ResultCommentTemplate::factory()
    ->withInstitution($this->institution)
    ->count(3)
    ->create();

  actingAs($this->admin)
    ->get(
      route('institutions.result-comment-templates.index', $this->institution)
    )
    ->assertOk()
    ->assertInertia(
      fn($assert) => $assert
        ->component(
          'institutions/result-comments/list-result-comment-templates'
        )
        ->has('resultCommentTemplates', $existing + 3)
    );
});

it('creates a result comment template', function () {
  actingAs($this->admin)
    ->post(
      route('institutions.result-comment-templates.store', $this->institution),
      [
        'comment' => 'Great performance',
        'min' => 0,
        'max' => 50,
        'type' => ResultCommentTemplateType::FullTermResult->value,
        'classification_ids' => [$this->classification->id]
      ]
    )
    ->assertOk();

  assertDatabaseHas('result_comment_templates', [
    'institution_id' => $this->institution->id,
    'comment' => 'Great performance',
    'min' => 0,
    'max' => 50,
    'type' => ResultCommentTemplateType::FullTermResult->value
  ]);

  $template = ResultCommentTemplate::where(
    'comment',
    'Great performance'
  )->first();

  assertDatabaseHas('classifiables', [
    'classifiable_id' => $template->id,
    'classifiable_type' => $template->getMorphClass(),
    'classification_id' => $this->classification->id
  ]);
});

it('updates a result comment template', function () {
  $template = ResultCommentTemplate::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->post(
      route('institutions.result-comment-templates.store', [
        $this->institution,
        $template
      ]),
      [
        'comment' => 'Updated comment',
        'min' => 10,
        'max' => 30,
        'type' => ResultCommentTemplateType::FullTermResult->value,
        'classification_ids' => [$this->classification->id]
      ]
    )
    ->assertOk();

  assertDatabaseHas('result_comment_templates', [
    'id' => $template->id,
    'comment' => 'Updated comment',
    'min' => 10,
    'max' => 30,
    'type' => ResultCommentTemplateType::FullTermResult->value
  ]);

  assertDatabaseHas('classifiables', [
    'classifiable_id' => $template->id,
    'classifiable_type' => $template->getMorphClass(),
    'classification_id' => $this->classification->id
  ]);
});

it('prevents creating conflicting templates', function () {
  ResultCommentTemplate::factory()
    ->withInstitution($this->institution)
    ->create([
      'min' => 0,
      'max' => 10,
      'type' => ResultCommentTemplateType::FullTermResult
    ]);

  actingAs($this->admin)
    ->post(
      route('institutions.result-comment-templates.store', $this->institution),
      [
        'comment' => 'Conflict',
        'min' => 5, // overlaps 0–10
        'max' => 15,
        'type' => ResultCommentTemplateType::FullTermResult->value
      ]
    )
    ->assertForbidden()
    ->assertSee("There's a conflict in the min and max values");

  assertDatabaseMissing('result_comment_templates', [
    'comment' => 'Conflict'
  ]);
});

it('can delete a result comment template', function () {
  $template = ResultCommentTemplate::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->delete(
      route('institutions.result-comment-templates.destroy', [
        $this->institution,
        $template
      ])
    )
    ->assertOk();

  assertDatabaseMissing('result_comment_templates', [
    'id' => $template->id
  ]);
});
