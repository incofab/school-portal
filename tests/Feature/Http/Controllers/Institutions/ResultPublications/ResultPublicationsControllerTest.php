<?php

use App\Enums\InstitutionSettingType;
use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\PriceList;
use App\Models\ResultPublication;
use App\Models\TermResult;
use App\Support\SettingsHandler;
use App\Models\Partner;

use function Pest\Laravel\postJson;

/**
 * ./vendor/bin/pest --filter ResultPublicationsControllerTest
 */

// Setup shared data
beforeEach(function () {
  // Create an Institution
  SettingsHandler::clear();
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();
  $this->term = TermType::First;
  $this->academicSessionInstitutionSetting = InstitutionSetting::factory()
    ->academicSession($this->institution, $this->academicSession)
    ->create();
  $this->termInstitutionSetting = InstitutionSetting::factory()
    ->term($this->institution, $this->term->value)
    ->create();

  // Create Classifications for the Institution
  $this->classes = Classification::factory(3)
    ->for($this->institution)
    ->create();

  // Create an Institution Group
  $this->institutionGroup = $this->institution->institutionGroup;

  $this->priceList = $this->institutionGroup
    ->priceLists()
    ->where('type', PriceType::ResultChecking)
    ->first();
  $this->priceList->update(['amount' => 100]);

  $this->institutionGroup
    ->fill(['credit_wallet' => $this->priceList->amount * 10])
    ->save();

  // Authenticate a User
  $this->actingAs($this->instAdmin);
});

// Test index method
it(
  'returns classifications for the institution in the index view',
  function () {
    $response = $this->get(
      route('institutions.result-publications.create', $this->institution)
    );

    $response->assertInertia(
      fn($page) => $page
        ->component(
          'institutions/result-publications/create-result-publication'
        )
        ->has('classifications', 3)
    );
  }
);

it('publishes results when valid data is provided', function () {
  $termResults = TermResult::factory(3)
    ->for($this->academicSession)
    ->withInstitution($this->institution)
    ->create([
      'classification_id' => $this->classes->first()->id,
      'term' => TermType::First->value
    ]);

  $payload = [
    'classifications' => [$this->classes->first()->id]
  ];

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  $this->assertDatabaseHas('result_publications', [
    'institution_id' => $this->institution->id,
    'num_of_results' => $termResults->count()
  ]);

  $resultPublication = ResultPublication::query()
    ->latest()
    ->first();
  foreach ($termResults as $result) {
    $this->assertDatabaseHas('term_results', [
      'id' => $result->id,
      'result_publication_id' => $resultPublication->id
    ]);
  }
});

// Test store method failure case: no unpublished results
it(
  'fails to publish results when no unpublished results are found',
  function () {
    $payload = [
      'classifications' => [$this->classes->first()->id]
    ];

    postJson(
      route('institutions.result-publications.store', $this->institution),
      $payload
    )->assertStatus(401);
    expect(ResultPublication::all())->toBeEmpty();
  }
);

// Test store method failure case: insufficient credit
it(
  'fails to publish results when there is insufficient credit balance',
  function () {
    // Reduce credit wallet to simulate insufficient balance
    $this->institutionGroup->update(['credit_wallet' => 0]);

    $termResults = TermResult::factory(3)
      ->withInstitution($this->institution)
      ->create([
        'classification_id' => $this->classes->first()->id,
        'academic_session_id' => $this->academicSession->id,
        'term' => TermType::First->value,
        'result_publication_id' => null
      ]);

    $payload = [
      'classifications' => [$termResults->first()->classification_id]
    ];

    postJson(
      route('institutions.result-publications.store', $this->institution),
      $payload
    )->assertStatus(401);

    $this->assertDatabaseMissing('result_publications', [
      'institution_id' => $this->institution->id
    ]);
  }
);

// Test store method failure case: insufficient credit
it('tests for payment Structure: PerTerm', function () {
  $paymentStructure = PaymentStructure::PerTerm;
  $amount = 100000;
  $this->institutionGroup->fill(['credit_wallet' => $amount])->save();
  $this->priceList
    ->fill(['payment_structure' => $paymentStructure->value, 'amount' => 5000])
    ->save();

  $termResults = TermResult::factory(3)
    ->withInstitution($this->institution)
    ->create([
      'classification_id' => $this->classes->first()->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $this->term->value
    ]);

  $payload = [
    'classifications' => [$termResults->first()->classification_id]
  ];

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  //   assertTrue($this->institutionGroup->fresh()->credit_wallet === $amount - $this->priceList->amount);
  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - $this->priceList->amount
  );

  SettingsHandler::clear();
  $newTerm = TermType::Second;
  $this->termInstitutionSetting
    ->fill([
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => $newTerm->value
    ])
    ->save();
  // Calling a different term
  $termResults = TermResult::factory(4)
    ->withInstitution($this->institution)
    ->create([
      'classification_id' => $this->classes->first()->id,
      'academic_session_id' => $this->academicSession->id,
      'term' => $newTerm->value
    ]);

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();
  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 2 * $this->priceList->amount
  );
});

// Test store method failure case: insufficient credit
it('tests for payment Structure: PerStudentPerTerm', function () {
  $termResultProp = [
    'classification_id' => $this->classes->first()->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term->value
  ];
  $paymentStructure = PaymentStructure::PerStudentPerTerm;
  $amount = 100000;
  $this->institutionGroup->fill(['credit_wallet' => $amount])->save();
  $this->priceList
    ->fill(['payment_structure' => $paymentStructure->value, 'amount' => 5000])
    ->save();

  $termResults = TermResult::factory(3)
    ->withInstitution($this->institution)
    ->create($termResultProp);
  $payload = ['classifications' => [$this->classes->first()->id]];

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 3 * $this->priceList->amount
  );

  $termResults = TermResult::factory(2)
    ->withInstitution($this->institution)
    ->create($termResultProp);

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 5 * $this->priceList->amount
  );

  // Calling a different term
  SettingsHandler::clear();
  $newTerm = TermType::Second;
  $this->termInstitutionSetting
    ->fill([
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => $newTerm->value
    ])
    ->save();
  $termResults = TermResult::factory(4)
    ->withInstitution($this->institution)
    ->create([...$termResultProp, 'term' => $newTerm->value]);

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();
  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 9 * $this->priceList->amount
  );
});

it('tests for payment Structure: PerSession', function () {
  $paymentStructure = PaymentStructure::PerSession;
  $termResultProp = [
    'classification_id' => $this->classes->first()->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term->value
  ];
  $amount = 100000;
  $this->institutionGroup->fill(['credit_wallet' => $amount])->save();
  $this->priceList
    ->fill(['payment_structure' => $paymentStructure->value, 'amount' => 5000])
    ->save();

  $termResults1 = TermResult::factory(3)
    ->withInstitution($this->institution)
    ->create($termResultProp);

  $payload = ['classifications' => [$termResultProp['classification_id']]];

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  // Different term
  SettingsHandler::clear();
  $newTerm = TermType::Second;
  $this->termInstitutionSetting
    ->fill([
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => $newTerm->value
    ])
    ->save();
  foreach ($termResults1 as $key => $tr) {
    TermResult::factory()
      ->withInstitution($this->institution)
      ->create([
        ...$termResultProp,
        'term' => $newTerm->value,
        'student_id' => $tr->student_id
      ]);
  }
  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - $this->priceList->amount
  );

  // Calling a different Session
  SettingsHandler::clear();
  $newSession = AcademicSession::factory()->create();
  $this->academicSessionInstitutionSetting
    ->fill(['value' => $newSession->id])
    ->save();
  $this->termInstitutionSetting
    ->fill(['value' => $termResultProp['term']])
    ->save();
  $termResults = TermResult::factory(4)
    ->withInstitution($this->institution)
    ->create([...$termResultProp, 'academic_session_id' => $newSession->id]);
  // dd([
  //   $termResults->toArray(),
  //   'props' => $termResultProp,
  //   'settings' => InstitutionSetting::all()->toArray()
  // ]);
  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();
  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 2 * $this->priceList->amount
  );
});

// Test store method failure case: insufficient credit
it('tests for payment Structure: PerStudentPerSession', function () {
  $termResultProp = [
    'classification_id' => $this->classes->first()->id,
    'academic_session_id' => $this->academicSession->id,
    'term' => $this->term->value
  ];
  $paymentStructure = PaymentStructure::PerStudentPerSession;
  $amount = 100000;
  $this->institutionGroup->fill(['credit_wallet' => $amount])->save();
  $this->priceList
    ->fill(['payment_structure' => $paymentStructure->value, 'amount' => 5000])
    ->save();

  $termResults1 = TermResult::factory(3)
    ->withInstitution($this->institution)
    ->create($termResultProp);
  $payload = ['classifications' => [$this->classes->first()->id]];

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 3 * $this->priceList->amount
  );

  SettingsHandler::clear();
  $newTerm = TermType::Second;
  $this->termInstitutionSetting
    ->fill([
      'key' => InstitutionSettingType::CurrentTerm->value,
      'value' => $newTerm->value
    ])
    ->save();

  foreach ($termResults1 as $key => $tr) {
    TermResult::factory()
      ->withInstitution($this->institution)
      ->create([
        ...$termResultProp,
        'term' => $newTerm->value,
        'student_id' => $tr->student_id
      ]);
  }
  $termResults = TermResult::factory(2)
    ->withInstitution($this->institution)
    ->create([...$termResultProp, 'term' => $newTerm->value]);

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 5 * $this->priceList->amount
  );

  // Calling a different session
  SettingsHandler::clear();
  $newSession = AcademicSession::factory()->create();
  $this->academicSessionInstitutionSetting
    ->fill(['value' => $newSession->id])
    ->save();
  $this->termInstitutionSetting
    ->fill(['value' => $termResultProp['term']])
    ->save();

  foreach ($termResults1 as $key => $tr) {
    TermResult::factory()
      ->withInstitution($this->institution)
      ->create([
        ...$termResultProp,
        'academic_session_id' => $newSession->id,
        'student_id' => $tr->student_id
      ]);
  }

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();
  expect($this->institutionGroup->fresh())->credit_wallet->toBe(
    $amount - 8 * $this->priceList->amount
  );
});

it('publishes result with loan', function () {
  $termResultProp = [
    'classification_id' => $this->classes->first()->id,
    'academic_session_id' => $this->academicSession->id
  ];
  $paymentStructure = PaymentStructure::PerSession;
  $amount = 5000;
  $this->institutionGroup
    ->fill([
      'credit_wallet' => 0,
      'debt_wallet' => 0,
      'loan_limit' => $amount - 100
    ])
    ->save();
  $this->priceList
    ->fill([
      'payment_structure' => $paymentStructure->value,
      'amount' => $amount
    ])
    ->save();

  $termResults = TermResult::factory(3)
    ->withInstitution($this->institution)
    ->create($termResultProp);
  $payload = ['classifications' => [$termResultProp['classification_id']]];

  // Won't publish is loan limit not enough
  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertStatus(401);

  $this->institutionGroup->fill(['loan_limit' => $amount])->save();

  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  expect($this->institutionGroup->fresh())->credit_wallet->toBe(0.0);
  expect($this->institutionGroup->fresh())->debt_wallet->toBe(
    $this->priceList->amount
  );
});

it('credits partners after successful result publication', function () {
  $this->priceList
    ->fill(['payment_structure' => PaymentStructure::PerTerm])
    ->save();
  // 1) Arrange: give the institution group a single partner, with a known commission rate
  $referringPartner = Partner::factory()
    ->withReferral($this->institutionGroup)
    ->create();
  $partner = $referringPartner->referrals()->first();

  // 2) Create some TermResult records to publish
  $termResults = TermResult::factory(5)
    ->withInstitution($this->institution)
    ->for($this->academicSession, 'academicSession')
    ->create([
      'classification_id' => $this->classes->first()->id,
      'term' => $this->term->value
    ]);

  $payload = [
    'classifications' => [$this->classes->first()->id]
  ];

  // 3) Act: hit the store endpoint
  postJson(
    route('institutions.result-publications.store', $this->institution),
    $payload
  )->assertOk();

  // 4) Find the newly created publication and its related transaction
  $publication = ResultPublication::latest()->first();
  $transaction = $publication->transaction;

  // 5) Calculate what the partner *should* have received
  $totalAmountPaid = $this->priceList->amount;
  $partnerCommissionRate = $partner->commission / 100;
  $expectedCommission = $totalAmountPaid * $partnerCommissionRate;

  // 6) Assert: there is a commission record tying that partner to this transaction
  $this->assertDatabaseHas('commissions', [
    'partner_id' => $referringPartner->id,
    'commissionable_id' => $transaction->id,
    'commissionable_type' => $transaction->getMorphClass(),
    'amount' => $totalAmountPaid * ($partner->referral_commission / 100)
  ]);
  $this->assertDatabaseHas('commissions', [
    'partner_id' => $partner->id,
    'commissionable_id' => $transaction->id,
    'commissionable_type' => $transaction->getMorphClass(),
    'amount' => $expectedCommission
  ]);
});
