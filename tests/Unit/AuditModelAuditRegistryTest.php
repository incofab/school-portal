<?php

use App\Models\Association;
use App\Models\Library;
use App\Models\LiveClass;
use App\Models\PayrollAdjustment;
use App\Models\PayrollAdjustmentType;
use App\Models\RegistrationRequest;
use App\Models\SchemeOfWork;
use App\Models\Timetable;
use App\Models\VacancyPost;
use App\Support\Audit\ModelAuditRegistry;

it(
  'includes newly covered feature-area models in automatic audit coverage',
  function () {
    $models = [
      new RegistrationRequest(),
      new SchemeOfWork(),
      new Library(),
      new PayrollAdjustment(),
      new PayrollAdjustmentType(),
      new VacancyPost(),
      new Timetable(),
      new LiveClass(),
      new Association()
    ];

    foreach ($models as $model) {
      expect(ModelAuditRegistry::shouldAudit($model))->toBeTrue();
    }
  }
);

it(
  'omits high-risk payload fields from automatic model audit values',
  function () {
    expect(
      ModelAuditRegistry::filterValues([
        'title' => 'Visible',
        'data' => ['password' => 'secret'],
        'external_url' => 'https://files.example.test/private',
        'file_url' => 'https://files.example.test/file.pdf',
        'meet_url' => 'https://meet.example.test/private-room',
        'learning_objectives' => 'Long lesson objectives',
        'resources' => 'Private resources',
        'requirements' => 'Long vacancy requirements'
      ])
    )->toBe([
      'title' => 'Visible',
      'data' => ModelAuditRegistry::OMITTED,
      'external_url' => ModelAuditRegistry::OMITTED,
      'file_url' => ModelAuditRegistry::OMITTED,
      'meet_url' => ModelAuditRegistry::OMITTED,
      'learning_objectives' => ModelAuditRegistry::OMITTED,
      'resources' => ModelAuditRegistry::OMITTED,
      'requirements' => ModelAuditRegistry::OMITTED
    ]);
  }
);
