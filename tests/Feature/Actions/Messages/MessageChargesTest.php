<?php

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Jobs\SendBulksms;
use App\Mail\InstitutionMessageMail;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Transaction;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->institution = Institution::factory()->create();
    $this->institutionGroup = $this->institution->institutionGroup;
    $this->message = Message::factory()
        ->institution($this->institution)
        ->create([
            'channel' => NotificationChannelsType::Sms->value,
            'status' => MessageStatus::Pending->value,
            'sent_at' => null,
            'meta' => null,
        ]);

    Config::set('services.sms-charge', 7);
    Config::set('services.email-charge', 3);
    Config::set('mail.from.address', 'test@example.com');
    Config::set('mail.from.name', 'EduManager Test');
});

it('does not send an SMS when the institution cannot afford it', function () {
    Http::fake();
    $this->institutionGroup->update(['credit_wallet' => 6]);

    (new SendBulksms(
        'Test SMS',
        '08012345678',
        $this->message,
        $this->institution,
        1,
        'sms-insufficient-reference'
    ))->handle();

    Http::assertNothingSent();
    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(6.0);
    expect($this->message->fresh()->status)->toBe(MessageStatus::Failed);
    expect(
        Transaction::query()
            ->where('reference', 'sms-insufficient-reference')
            ->exists()
    )->toBeFalse();
});

it('charges an SMS once when a queued job is retried', function () {
    Http::fake(['https://www.bulksmsnigeria.com/*' => Http::response([])]);
    $this->institutionGroup->update(['credit_wallet' => 20]);

    $job = new SendBulksms(
        'Test SMS',
        '08012345678',
        $this->message,
        $this->institution,
        1,
        'sms-retry-reference'
    );

    $job->handle();
    $job->handle();

    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(13.0);
    expect(
        Transaction::query()
            ->where('reference', 'sms-retry-reference')
            ->count()
    )->toBe(1);
});

it('does not charge an SMS when its configured price is zero', function () {
    Http::fake(['https://www.bulksmsnigeria.com/*' => Http::response([])]);
    Config::set('services.sms-charge', 0);
    $this->institutionGroup->update(['credit_wallet' => 0]);

    (new SendBulksms(
        'Test SMS',
        '08012345678',
        $this->message,
        $this->institution,
        1,
        'sms-free-reference'
    ))->handle();

    Http::assertSentCount(1);
    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(0.0);
    expect(
        Transaction::query()->where('reference', 'sms-free-reference')->exists()
    )->toBeFalse();
});

it('charges institution email delivery at the queued mailable boundary', function () {
    $this->institutionGroup->update(['credit_wallet' => 10]);
    $mailer = app('mail.manager')->mailer('array');

    (new InstitutionMessageMail(
        $this->institution,
        'Test email',
        'Test body',
        $this->message,
        1,
        'email-reference'
    ))
        ->to('parent@example.com')
        ->send($mailer);

    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(7.0);
    expect(
        Transaction::query()->where('reference', 'email-reference')->count()
    )->toBe(1);
});

it('does not send an email when the institution cannot afford it', function () {
    $this->institutionGroup->update(['credit_wallet' => 2]);
    $mailer = app('mail.manager')->mailer('array');

    (new InstitutionMessageMail(
        $this->institution,
        'Test email',
        'Test body',
        $this->message,
        1,
        'email-insufficient-reference'
    ))
        ->to('parent@example.com')
        ->send($mailer);

    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(2.0);
    expect($this->message->fresh()->status)->toBe(MessageStatus::Failed);
    expect(
        Transaction::query()
            ->where('reference', 'email-insufficient-reference')
            ->exists()
    )->toBeFalse();
});

it('does not charge email delivery when its configured price is zero', function () {
    Config::set('services.email-charge', 0);
    $this->institutionGroup->update(['credit_wallet' => 0]);
    $mailer = app('mail.manager')->mailer('array');

    (new InstitutionMessageMail(
        $this->institution,
        'Test email',
        'Test body',
        $this->message,
        1,
        'email-free-reference'
    ))
        ->to('parent@example.com')
        ->send($mailer);

    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(0.0);
    expect(
        Transaction::query()->where('reference', 'email-free-reference')->exists()
    )->toBeFalse();
});
