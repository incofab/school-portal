<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->admin = $this->institution->createdBy;
    $this->student = User::factory()
        ->student($this->institution)
        ->create();
    $this->otherStudent = User::factory()
        ->student($this->institution)
        ->create();
    $this->otherInstitution = Institution::factory()->create();
    $this->fee = Fee::factory()
        ->institution($this->institution)
        ->create(['amount' => 5000]);
});

function paymentAttemptFor(
    Institution $institution,
    User $user,
    Fee $fee,
    array $attributes = []
): PaymentReference {
    return PaymentReference::factory()
        ->payable($user)
        ->paymentable($fee)
        ->create([
            'institution_id' => $institution->id,
            'user_id' => $user->id,
            'amount' => $fee->amount,
            'purpose' => PaymentPurpose::Fee->value,
            'merchant' => PaymentMerchantType::Paystack->value,
            ...$attributes,
        ]);
}

it('shows only the authenticated user payment attempts on the personal history page', function () {
    $ownPayment = paymentAttemptFor(
        $this->institution,
        $this->student,
        $this->fee
    );
    paymentAttemptFor($this->institution, $this->otherStudent, $this->fee);

    actingAs($this->student)
        ->get(route('payment-attempts.index'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('payments/list-payment-attempts')
                ->where('paymentReferences.data.0.id', $ownPayment->id)
                ->has('paymentReferences.data', 1)
        );
});

it('scopes institution payment attempts to the current institution', function () {
    paymentAttemptFor($this->institution, $this->student, $this->fee);
    $otherFee = Fee::factory()
        ->institution($this->otherInstitution)
        ->create();
    paymentAttemptFor(
        $this->otherInstitution,
        $this->otherInstitution->createdBy,
        $otherFee
    );

    actingAs($this->admin)
        ->get(route('institutions.payment-attempts.index', $this->institution))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('payments/list-payment-attempts')
                ->has('paymentReferences.data', 1)
                ->where('paymentReferences.data.0.institution_id', $this->institution->id)
        );
});

it('lets admin managers see payment attempts across institutions and users', function () {
    paymentAttemptFor($this->institution, $this->student, $this->fee);
    $otherFee = Fee::factory()
        ->institution($this->otherInstitution)
        ->create();
    paymentAttemptFor(
        $this->otherInstitution,
        $this->otherInstitution->createdBy,
        $otherFee
    );
    $adminManager = User::factory()
        ->adminManager()
        ->create();

    actingAs($adminManager)
        ->get(route('managers.payment-attempts.index'))
        ->assertOk()
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('payments/list-payment-attempts')
                ->where('context', 'manager')
                ->has('paymentReferences.data', 2)
        );
});

it('lets a user verify their pending successful payment and prevents duplicate fulfilment', function () {
    $admissionForm = AdmissionForm::factory()
        ->for($this->institution)
        ->create(['price' => 1000]);
    $admissionApplication = AdmissionApplication::factory()
        ->admissionForm($admissionForm)
        ->create();
    $paymentReference = PaymentReference::factory()
        ->payable($admissionForm)
        ->paymentable($admissionApplication)
        ->create([
            'institution_id' => $this->institution->id,
            'user_id' => $this->student->id,
            'amount' => $admissionForm->price,
            'purpose' => PaymentPurpose::AdmissionFormPurchase->value,
            'merchant' => PaymentMerchantType::Paystack->value,
            'meta' => ['admission_application_id' => $admissionApplication->id],
        ]);

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response(
            [
                'status' => true,
                'data' => [
                    'status' => 'success',
                    'amount' => $paymentReference->amount * 100,
                    'reference' => $paymentReference->reference,
                ],
            ],
            200
        ),
    ]);

    actingAs($this->student)
        ->postJson(route('payment-attempts.verify', $paymentReference->reference))
        ->assertOk()
        ->assertJsonPath('success', true);

    actingAs($this->student)
        ->postJson(route('payment-attempts.verify', $paymentReference->reference))
        ->assertStatus(422)
        ->assertJsonPath('message', 'This payment can no longer be verified from here.');

    assertDatabaseHas('payment_references', [
        'id' => $paymentReference->id,
        'status' => PaymentStatus::Confirmed->value,
    ]);
    assertDatabaseCount('admission_form_purchases', 1);
    assertDatabaseHas('admission_form_purchases', [
        'reference' => $paymentReference->reference,
    ]);
});

it('cancels a pending payment when the provider returns a terminal failed status', function () {
    $paymentReference = paymentAttemptFor(
        $this->institution,
        $this->student,
        $this->fee
    );

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response(
            [
                'status' => true,
                'data' => [
                    'status' => 'abandoned',
                    'gateway_response' => 'The transaction was abandoned',
                    'reference' => $paymentReference->reference,
                ],
            ],
            200
        ),
    ]);

    actingAs($this->student)
        ->postJson(route('payment-attempts.verify', $paymentReference->reference))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    assertDatabaseHas('payment_references', [
        'id' => $paymentReference->id,
        'status' => PaymentStatus::Cancelled->value,
    ]);
    assertDatabaseMissing('fee_payments', [
        'reference' => $paymentReference->reference,
    ]);
});

it('prevents users from verifying another user payment attempt', function () {
    $paymentReference = paymentAttemptFor(
        $this->institution,
        $this->student,
        $this->fee
    );

    actingAs($this->otherStudent)
        ->postJson(route('payment-attempts.verify', $paymentReference->reference))
        ->assertForbidden();
});
