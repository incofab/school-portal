<?php

use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\ActivityLog;
use App\Models\BankAccount;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\User;
use App\Support\Audit\ActivityLogSanitizer;
use App\Support\Audit\FinancialActivityLogger;

it('logs manual payment approval with compact financial metadata', function () {
    $institution = Institution::factory()->create();
    $actor = $institution->createdBy;
    $student = User::factory()
        ->student($institution)
        ->create();
    $fee = Fee::factory()
        ->institution($institution)
        ->create();
    $bankAccount = BankAccount::factory()
        ->accountable($institution->institutionGroup)
        ->create(['account_number' => '1234567890']);
    $manualPayment = ManualPayment::factory()
        ->institution($institution)
        ->payable($student)
        ->paymentable($fee)
        ->create([
            'user_id' => $student->id,
            'bank_account_id' => $bankAccount->id,
            'amount' => 15000,
            'purpose' => PaymentPurpose::Fee->value,
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by_user_id' => $actor->id,
            'reviewed_at' => now(),
            'processed_at' => now(),
        ]);

    app(FinancialActivityLogger::class)->manualPaymentReviewed(
        $manualPayment,
        $actor,
        true
    );

    $log = ActivityLog::query()
        ->where('event', 'finance.manual_payment.approved')
        ->firstOrFail();

    expect($log->actor_id)
        ->toBe($actor->id)
        ->and($log->institution_id)
        ->toBe($institution->id)
        ->and($log->severity)
        ->toBe('critical')
        ->and($log->properties['amount'])
        ->toBe(15000)
        ->and($log->properties['reference'])
        ->toBe($manualPayment->reference)
        ->and($log->properties['bank_account']['account_number_last4'])
        ->toBe('7890')
        ->and(json_encode($log->properties->toArray()))
        ->not->toContain('1234567890');
});

it('logs bank account changes as security events without full account numbers', function () {
    $institution = Institution::factory()->create();
    $bankAccount = BankAccount::factory()
        ->accountable($institution->institutionGroup)
        ->create(['account_number' => '9876543210']);

    app(FinancialActivityLogger::class)->bankAccountChanged(
        $bankAccount,
        'created',
        $institution->institutionGroup
    );

    $log = ActivityLog::query()
        ->where('event', 'finance.bank_account.created')
        ->firstOrFail();

    expect($log->institution_group_id)
        ->toBe($institution->institution_group_id)
        ->and($log->severity)
        ->toBe('security')
        ->and($log->properties['bank_account']['account_number_last4'])
        ->toBe('3210')
        ->and(json_encode($log->properties->toArray()))
        ->not->toContain('9876543210');
});

it('sanitizes external payment webhook metadata', function () {
    $institution = Institution::factory()->create();
    $fee = Fee::factory()
        ->institution($institution)
        ->create();

    app(FinancialActivityLogger::class)->providerWebhookReceived(
        'paystack',
        [
            'reference' => 'REF-001',
            'status' => 'success',
            'authorization' => 'Bearer secret-token',
            'secret_key' => 'sk_test_private',
        ],
        $institution,
        $fee
    );

    $log = ActivityLog::query()
        ->where('event', 'finance.payment_webhook.received')
        ->firstOrFail();

    expect($log->category)
        ->toBe('integration')
        ->and($log->severity)
        ->toBe('security')
        ->and($log->properties['metadata']['authorization'])
        ->toBe(ActivityLogSanitizer::REDACTED)
        ->and($log->properties['metadata']['secret_key'])
        ->toBe(ActivityLogSanitizer::REDACTED)
        ->and(json_encode($log->properties->toArray()))
        ->not->toContain('sk_test_private')
        ->not->toContain('Bearer secret-token');
});

it('logs payment credential changes without storing credential values', function () {
    $institution = Institution::factory()->create();

    app(FinancialActivityLogger::class)->paymentCredentialsChanged(
        $institution,
        [
            'paystack' => [
                'public_key' => 'pk_old',
                'private_key' => 'sk_old',
            ],
        ],
        [
            'paystack' => [
                'public_key' => 'pk_new',
                'private_key' => 'sk_new',
            ],
        ]
    );

    $log = ActivityLog::query()
        ->where('event', 'finance.payment_credentials.updated')
        ->firstOrFail();

    expect($log->severity)
        ->toBe('security')
        ->and($log->old_values['credentials']['paystack']['private_key_present'])
        ->toBeTrue()
        ->and($log->new_values['credentials']['paystack']['public_key_present'])
        ->toBeTrue()
        ->and(json_encode([
            $log->properties->toArray(),
            $log->old_values->toArray(),
            $log->new_values->toArray(),
        ]))
        ->not->toContain('pk_old')
        ->not->toContain('sk_old')
        ->not->toContain('pk_new')
        ->not->toContain('sk_new');
});
