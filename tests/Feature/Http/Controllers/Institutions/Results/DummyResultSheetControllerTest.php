<?php

use App\Enums\InstitutionSettingType;
use App\Enums\ResultSettingType;
use App\Enums\ResultTemplateType;
use App\Models\Course;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it(
  'renders a dummy result sheet with the institution result template',
  function () {
    $institution = Institution::factory()->create();
    InstitutionSetting::query()
      ->where('institution_id', $institution->id)
      ->where('key', InstitutionSettingType::Result->value)
      ->delete();
    InstitutionSetting::factory()->create([
      'institution_id' => $institution->id,
      'key' => InstitutionSettingType::Result->value,
      'value' => json_encode([
        ResultSettingType::Template->value =>
          ResultTemplateType::Template6->value
      ]),
      'type' => 'array'
    ]);
    Course::factory()
      ->count(16)
      ->withInstitution($institution)
      ->create();

    actingAs($institution->createdBy)
      ->get(route('institutions.result-sheets.dummy', $institution))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/result-sheets/template-6')
          ->has('courseResults', 14)
          ->has('courseResultInfoData', 14)
          ->where('termResult.average', fn($average) => is_numeric($average))
      );
  }
);

it(
  'tops up dummy result sheet subjects when institution subjects are fewer than ten',
  function () {
    $institution = Institution::factory()->create();

    actingAs($institution->createdBy)
      ->get(route('institutions.result-sheets.dummy', $institution))
      ->assertOk()
      ->assertInertia(
        fn(Assert $page) => $page
          ->component('institutions/result-sheets/template-1')
          ->has('courseResults', 10)
          ->has('assessments', 2)
      );
  }
);
