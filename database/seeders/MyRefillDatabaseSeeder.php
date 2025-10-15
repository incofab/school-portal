<?php

namespace Database\Seeders;

use App\Enums\TermType;
use App\Models\Institution;
use Illuminate\Support\Arr;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use App\Models\InstitutionGroup;
use App\Enums\InstitutionSettingType;
use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\InstitutionSetting;
use App\Models\PriceList;
use App\Models\Student;

class MyRefillDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // This will never run in production
    if (!config('app.debug')) {
      return;
    }

    $this->seed();
  }

  function seed()
  {
    if (InstitutionGroup::count() > 0) {
      return;
    }
    $institutionGroup = InstitutionGroup::factory()->create([
      'name' => 'Success Academy Group'
    ]);
    $institution = Institution::factory()
      ->for($institutionGroup)
      ->create(['name' => 'Success Academy']);
    $institutionAdmin = $institution->createdBy;
    $institutionAdmin->fill(['email' => 'success@email.com'])->save();

    foreach (['JSS 1', 'JSS 2', 'JSS 3'] as $key => $value) {
      $classificationGroup = ClassificationGroup::factory()
        ->withInstitution($institution)
        ->create(['title' => $value]);
      Classification::factory()
        ->classificationGroup($classificationGroup)
        ->create(['title' => $value]);
    }
    $this->createInstitutionSetting($institution);
    $this->createStudents($institution, 10);

    $this->createPriceList($institutionGroup);
  }

  function createInstitutionSetting(Institution $institution)
  {
    $acadSessions = AcademicSession::all()
      ->pluck('id')
      ->toArray();

    $settingData = [
      [
        'key' => InstitutionSettingType::CurrentTerm->value,
        'value' => Arr::random([
          TermType::First,
          TermType::Second,
          TermType::Third
        ]),
        'institution_id' => $institution->id
      ],
      [
        'key' => InstitutionSettingType::CurrentAcademicSession->value,
        'value' => Arr::random($acadSessions),
        'institution_id' => $institution->id
      ]
    ];

    InstitutionSetting::insert($settingData);
  }

  function createStudents(Institution $institution, $numPerClass)
  {
    $classes = $institution->classifications()->get();
    foreach ($classes as $key => $class) {
      Student::factory($numPerClass)
        ->withInstitution($institution, $class)
        ->create();
    }
  }

  function createPriceList(InstitutionGroup $institutionGroup)
  {
    $priceData = [
      'type' => PriceType::ResultChecking->value,
      'institution_group_id' => $institutionGroup->id,
      'payment_structure' => Arr::random([
        PaymentStructure::PerTerm->value,
        PaymentStructure::PerSession->value,
        PaymentStructure::PerStudentPerTerm->value,
        PaymentStructure::PerStudentPerSession->value
      ]),
      'amount' => rand(300, 20000)
    ];

    PriceList::query()->updateOrCreate(
      collect($priceData)
        ->only('type', 'institution_group_id')
        ->toArray(),
      $priceData
    );
  }
}
