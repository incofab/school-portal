<?php

use App\Enums\NotificationChannelsType;
use App\Enums\NotificationReceiversType;
use App\Enums\SchoolNotificationPurpose;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\ReceiptType;
use App\Models\SchoolNotification;
use App\Models\Student;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

/**
 * ./vendor/bin/pest --filter PaymentNotificationControllerTest
 */

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->admin = $this->institution->createdBy;
  $this->user = $this->institutionUser->user;

  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();

  $this->classification = Classification::factory(3)
    ->for($this->institution)
    ->create();

  $this->classificationIds = $this->classification->pluck('id')->toArray();

  $this->receiptType = ReceiptType::factory()
    ->institution($this->institution)
    ->create();
});

it('can render the create payment notification page', function () {
  $response = actingAs($this->admin)->getJson(
    route('institutions.payment-notifications.create', $this->institution)
  );

  $response->assertOk();
  $response->assertInertia(
    fn($assert) => $assert
      ->component('institutions/payment-notifications/create-notification')
      ->has('receiptTypes')
      ->has('classification')
  );
});

it('can store a payment notification for all students', function () {
  //== Data
  $data = [
    'receipt_type_id' => $this->receiptType->id,
    'reference' => fake()
      ->unique()
      ->word(),
    'receiver' => NotificationReceiversType::AllClasses->value,
    'classification_ids' => null,
    'channel' => NotificationChannelsType::Email->value
  ];

  //== Query
  $response = actingAs($this->admin)->postJson(
    route('institutions.payment-notifications.store', $this->institution),
    $data
  );

  //==
  if ($data['receiver'] === NotificationReceiversType::AllClasses->value) {
    $receiverType = 'classification-group';
  }

  if ($data['receiver'] === NotificationReceiversType::SpecificClass->value) {
    $receiverType = 'classification';
  }

  //== Assert
  $response->assertOk();
  assertDatabaseHas('school_notifications', [
    'reference' => $data['reference'],
    'sender_user_id' => $this->admin->id,
    'receiver_type' => $receiverType,
    'institution_id' => $this->institution->id,
    'purpose' => SchoolNotificationPurpose::Receipt->value
  ]);
});

it('can store a payment notification for specific class', function () {
  //== Data
  $data = [
    'receipt_type_id' => $this->receiptType->id,
    'reference' => fake()
      ->unique()
      ->word(),
    'receiver' => NotificationReceiversType::SpecificClass->value,
    'classification_ids' => $this->classificationIds,
    'channel' => NotificationChannelsType::Email->value
  ];

  //== Query
  $response = actingAs($this->admin)->postJson(
    route('institutions.payment-notifications.store', $this->institution),
    $data
  );

  //==
  if ($data['receiver'] === NotificationReceiversType::AllClasses->value) {
    $receiverType = 'classification-group';
  }

  if ($data['receiver'] === NotificationReceiversType::SpecificClass->value) {
    $receiverType = 'classification';
  }

  // Assert
  $response->assertOk();
  assertDatabaseHas('school_notifications', [
    'reference' => $data['reference'],
    'sender_user_id' => $this->admin->id,
    'receiver_type' => $receiverType,
    // 'receiver_ids' => json_encode($this->classificationIds),
    'institution_id' => $this->institution->id,
    'purpose' => SchoolNotificationPurpose::Receipt->value
  ]);
  expect(
    SchoolNotification::where('reference', $data['reference'])->first()
  )->receiver_ids->toMatchArray($this->classificationIds);
});

it(
  'validates required fields when storing a payment notification',
  function () {
    // Arrange
    $data = [];

    // Act
    $response = actingAs($this->admin)->postJson(
      route('institutions.payment-notifications.store', $this->institution),
      $data
    );

    // Assert
    $response->assertStatus(422); //Validation Failed
    $response->assertJsonValidationErrors([
      'receipt_type_id',
      'receiver',
      'channel'
    ]);
  }
);

it('validates classification ids are valid', function () {
  // Arrange
  $data = [
    'receipt_type_id' => $this->receiptType->id,
    'reference' => fake()
      ->unique()
      ->word(),
    'receiver' => NotificationReceiversType::SpecificClass->value,
    'classification_ids' => [9999],
    'channel' => NotificationChannelsType::Email->value
  ];

  // Act
  $response = actingAs($this->admin)->postJson(
    route('institutions.payment-notifications.store', $this->institution),
    $data
  );

  // Assert
  $response->assertStatus(422); //Validation Failed
  $response->assertJsonValidationErrors(['classification_ids.0']);
});

it('validates reference is unique', function () {
  // Arrange
  $existingNotification = SchoolNotification::factory()->create([
    'institution_id' => $this->institution->id,
    'sender_user_id' => $this->admin->id
  ]);

  $data = [
    'receipt_type_id' => $this->receiptType->id,
    'reference' => $existingNotification->reference,
    'receiver' => NotificationReceiversType::SpecificClass->value,
    'classification_ids' => $this->classificationIds,
    'channel' => NotificationChannelsType::Email->value,
    'receiver_ids' => null
  ];

  // Act
  $response = actingAs($this->admin)->postJson(
    route('institutions.payment-notifications.store', $this->institution),
    $data
  );

  // Assert
  $response->assertStatus(422); //Validation Failed
  $response->assertJsonValidationErrors(['reference']);
});
