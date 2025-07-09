<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\TermType;
use App\Enums\ManagerRole;
use App\Models\Institution;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use App\Models\InstitutionGroup;
use App\Enums\InstitutionUserType;
use App\Enums\InstitutionSettingType;
use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\InstitutionSetting;
use App\Models\PriceList;
use App\Models\Student;
use App\Models\TermResult;
use Spatie\Permission\Models\Role;

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
    // $this->createRoles();
    // $this->createAcademicSession();
    // $this->createManager(1);
    // $this->createInstitutionGroup(5);
    // $this->createInstitution(2);
    // $this->createInstitutionSetting();
    // $this->createClassGroups();
    // $this->createClasses();
    // $this->createStudents(3);
    // $this->createTermResults(null);
    $this->createPriceList();
  }

  function createRoles()
  {
    foreach (ManagerRole::cases() as $key => $roleType) {
      Role::findOrCreate($roleType->value);
    }
  }

  function createManager($num)
  {
    (new UserSeeder())->run();
  }

  function seed()
  {
    (new AcademicSessionSeeder())->run();
    (new UserSeeder())->run();

    $institutionGroups = InstitutionGroup::factory(4)->create();
    foreach ($institutionGroups as $key => $institutionGroup) {
      $institutions = Institution::factory(2)
        ->for($institutionGroup)
        ->create();
      foreach ($institutions as $key => $institution) {
        $institutionAdmin = $institution->createdBy;

        foreach (['JSS 1', 'JSS 2', 'JSS 3'] as $key => $value) {
          Classification::factory()
            ->withInstitution($institution)
            ->create(['title' => $value]);
        }
        $this->createInstitutionSetting($institution);
        $this->createStudents(10);
      }
    }
  }

  /** @deprecated */
  function createInstAdmin()
  {
    $fnp = 'Admin';
    $lnp = 'User';
    $emp = 'admin' . rand(100, 999) . rand(100, 999);

    return $this->createUsers(1, $fnp, $lnp, $emp)[0];
  }

  /** @deprecated */
  function createInstitutionGroup($num)
  {
    $fnp = 'Partner';
    $lnp = 'User';
    $emp = 'partner';

    $users = $this->createUsers($num, $fnp, $lnp, $emp);

    foreach ($users as $key => $user) {
      $partner = $user->syncRoles(ManagerRole::Partner);

      $instGroupData = [
        'partner_user_id' => $partner->id,
        'user_id' => $partner->id,
        'name' => 'SirKris Group of Schools - ' . $key + 1
      ];

      InstitutionGroup::create($instGroupData);
    }
  }

  /** @deprecated */
  function createInstitution($numPerInstGroup)
  {
    $instGroups = InstitutionGroup::all();

    foreach ($instGroups as $instGroup) {
      $instGroupId = $instGroup->id;

      for ($i = 1; $i <= $numPerInstGroup; $i++) {
        $instAdmin = $this->createInstAdmin();

        $instData = [
          'institution_group_id' => $instGroupId,
          'uuid' => Str::orderedUuid(),
          'code' => Institution::generateInstitutionCode(),
          'user_id' => $instAdmin->id,
          'email' => 'inst_' . $instGroupId . '_' . $i . '@email.com',
          'phone' => '07030111' . $instGroupId . $i,
          'name' => 'SiKris Academy - ' . $instGroupId . '_' . $i,
          'address' => fake()
            ->unique()
            ->address()
        ];

        $inst = Institution::create($instData);

        $inst->createdBy
          ->institutionUsers()
          ->firstOrCreate(
            ['institution_id' => $inst->id],
            ['role' => InstitutionUserType::Admin]
          );
      }
    }
  }

  function createInstitutionSetting(Institution $institution)
  {
    // $instGroups = InstitutionGroup::all();
    // $insts = Institution::all();
    $acadSessions = AcademicSession::all()
      ->pluck('title')
      ->toArray();

    // foreach ($insts as $inst) {
    //   $instId = $inst->id;

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
    // }
  }

  /** @deprecated */
  function createClassGroups()
  {
    $institutions = Institution::all();

    foreach ($institutions as $key => $institution) {
      ClassificationGroup::insert([
        [
          'institution_id' => $institution->id,
          'title' => 'JSS 1'
        ],
        [
          'institution_id' => $institution->id,
          'title' => 'JSS 2'
        ]
      ]);
    }
  }

  /** @deprecated */
  function createClasses()
  {
    $institutions = Institution::all();

    foreach ($institutions as $key => $institution) {
      $classGroups = ClassificationGroup::where(
        'institution_id',
        $institution->id
      )->get();

      foreach ($classGroups as $key => $classGroup) {
        $classData = [
          [
            'institution_id' => $institution->id,
            'title' => $classGroup->title . 'A',
            'classification_group_id' => $classGroup->id
          ],
          [
            'institution_id' => $institution->id,
            'title' => $classGroup->title . 'B',
            'classification_group_id' => $classGroup->id
          ]
        ];

        Classification::insert($classData);
      }
    }
  }

  function createStudents($numPerClass)
  {
    $classes = Classification::all();

    foreach ($classes as $key => $class) {
      Student::factory($numPerClass)
        ->withInstitution($class->institution, $class)
        ->create();

      // #== Create Users
      // $fnp = 'Student';
      // $lnp = 'User';
      // $emp = 'student' . rand(100, 999) . rand(100, 999);

      // $users = $this->createUsers($numPerClass, $fnp, $lnp, $emp);

      // foreach ($users as $user) {
      //   $institutionUser = InstitutionUser::create([
      //     'user_id' => $user->id,
      //     'institution_id' => $class->institution_id,
      //     'role' => InstitutionUserType::Student
      //   ]);

      //   Student::create([
      //     'institution_user_id' => $institutionUser->id,
      //     'user_id' => $institutionUser->user_id,
      //     'classification_id' => $class->id,
      //     'code' =>
      //       date('Y') .
      //       fake()
      //         ->unique()
      //         ->numerify('####'),
      //     'guardian_phone' => fake()->phoneNumber()
      //   ]);
      // }
    }
  }

  function createTermResults()
  {
    $students = Student::all();

    foreach ($students as $key => $student) {
      TermResult::factory()
        ->forStudent($student)
        ->create();

      // $instId = $student->institutionUser->institution->id;

      // $instAcademicSession = InstitutionSetting::where(
      //   'institution_id',
      //   $instId
      // )
      //   ->where('key', InstitutionSettingType::CurrentAcademicSession->value)
      //   ->first();

      // $academicSession = AcademicSession::where(
      //   'title',
      //   $instAcademicSession->value
      // )->first();
      // $instAcademicSessionId = $academicSession->id;

      // $instTerm = InstitutionSetting::where('institution_id', $instId)
      //   ->where('key', InstitutionSettingType::CurrentTerm->value)
      //   ->first();

      // TermResult::create([
      //   'term' => !empty($term)
      //     ? ($term == 'first'
      //       ? TermType::First->value
      //       : ($term == 'second'
      //         ? TermType::Second->value
      //         : TermType::Third->value))
      //     : $instTerm->value,
      //   'total_score' => fake()->randomNumber(2),
      //   'position' => fake()->randomDigit(),
      //   'average' => fake()->randomNumber(2),
      //   'remark' => fake()->sentence(),
      //   'academic_session_id' => $instAcademicSessionId,
      //   'institution_id' => $instId,
      //   'student_id' => $student->id,
      //   'classification_id' => $student->classification_id
      // ]);
    }
  }

  function createUsers($num, $fnp, $lnp, $emp)
  {
    //fnp = FirstNamePrefix, $lnp = LastNamePrefix, $emp = EmailPrefix
    $users = [];
    for ($i = 1; $i <= $num; $i++) {
      $userData = [
        'first_name' => ($fnp ?? 'fn') . ($num > 1 ? $i : null),
        'last_name' => ($lnp ?? 'ln') . ($num > 1 ? $i : null),
        'other_names' => "on$i",
        'email' => ($emp ?? 'user') . ($num > 1 ? $i : null) . '@email.com',
        'phone' => "08030000$i",
        'email_verified_at' => now(),
        'password' =>
          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
        'remember_token' => Str::random(10)
      ];

      $users[] = User::create($userData);
    }

    return $users;
  }

  function createAcademicSession()
  {
    AcademicSession::insert([
      [
        'title' => '2012/2013'
      ],
      [
        'title' => '2013/2014'
      ],
      [
        'title' => '2014/2015'
      ]
    ]);
  }

  function createPriceList()
  {
    $instGroups = InstitutionGroup::all();

    foreach ($instGroups as $key => $instGroup) {
      $priceData = [
        'type' => PriceType::ResultChecking->value,
        'institution_group_id' => $instGroup->id,
        'payment_structure' => Arr::random([
          PaymentStructure::PerTerm->value,
          PaymentStructure::PerSession->value,
          PaymentStructure::PerStudentPerTerm->value,
          PaymentStructure::PerStudentPerSession->value
        ]),
        'amount' => rand(300, 20000)
      ];

      PriceList::create($priceData);
    }
  }
}
