<?php

use App\Enums\NotificationChannelsType;
use App\Enums\SchoolNotificationPurpose;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Receipt;
use App\Models\SchoolNotification;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

/**
 * ./vendor/bin/pest --filter PaymentNotificationControllerTest
 */
beforeEach(function () {
  Mail::fake();
  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->institutionGroup->fill(['credit_wallet' => 1000])->save();
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->admin = $this->institution->createdBy;
  $this->user = $this->institutionUser->user;

  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create();

  $this->price = 10;

  Config::set('services.sms-charge', $this->price);
  Config::set('services.email-charge', $this->price);
});

it('can store a payment notification for all owing students', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  [$student1, $student2, $student3] = Student::factory(3)
    ->withInstitution($this->institution, $classification)
    ->guardian($this->institution)
    ->create();

  $feeCategory = FeeCategory::factory()
    ->fee($this->fee)
    ->feeable($classification)
    ->create();

  // Users how have paid will be exempted
  Receipt::factory()
    ->fee($this->fee)
    ->student($student3)
    ->create([
      'amount' => $this->fee->amount,
      'amount_remaining' => 0,
      'amount_paid' => $this->fee->amount
    ]);

  Receipt::factory()
    ->fee($this->fee)
    ->student($student1)
    ->create([
      'amount' => $this->fee->amount,
      'amount_remaining' => 100,
      'amount_paid' => $this->fee->amount - 100
    ]);

  //== Data
  $data = [
    'fee_id' => $this->fee->id,
    'reference' => Str::orderedUuid(),
    'channel' => NotificationChannelsType::Email->value
  ];

  actingAs($this->admin)
    ->postJson(
      route('institutions.payment-notifications.store', $this->institution),
      $data
    )
    ->assertOk();

  assertDatabaseHas('school_notifications', [
    'reference' => $data['reference'],
    'sender_user_id' => $this->admin->id,
    'receiver_type' => MorphMap::key(User::class),
    'institution_id' => $this->institution->id,
    'purpose' => SchoolNotificationPurpose::Receipt->value
  ]);
  expect(
    SchoolNotification::where('reference', $data['reference'])
      ->first()
      ->receiver_ids->toArray()
  )->toEqualCanonicalizing([
    $student1->guardian?->id,
    $student2->guardian?->id
  ]);

  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(
    $this->institutionGroup->credit_wallet - $this->price * 2
  );
});
