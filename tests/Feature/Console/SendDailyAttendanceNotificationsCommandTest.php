<?php

use App\Jobs\SendBulksms;
use App\Jobs\SendWhatsappTemplateMessage;
use App\Models\Attendance;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
  Queue::fake();
  Carbon::setTestNow(Carbon::parse('2026-08-16 17:00:00'));
});

afterEach(function () {
  Carbon::setTestNow();
});

it(
  'sends sms attendance notifications for students with attendance activity',
  function () {
    config()->set('services.attendance-notification.channel', 'sms');

    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create([
        'guardian_phone' => '08012345678'
      ]);
    $attendance = Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->create([
        'signed_in_at' => now()->setTime(8, 5),
        'signed_out_at' => now()->setTime(15, 45)
      ]);

    Artisan::call('attendance:notify-guardians');

    Queue::assertPushed(SendBulksms::class, function (SendBulksms $job) {
      return $job->getTo() === '08012345678';
    });
    Queue::assertNotPushed(SendWhatsappTemplateMessage::class);

    $message = Message::query()->first();

    expect($message)
      ->not->toBeNull()
      ->and($message->messageable_type)
      ->toBe($attendance->getMorphClass())
      ->and($message->messageable_id)
      ->toBe($attendance->id)
      ->and($message->meta['attendance_date'])
      ->toBe('2026-08-16')
      ->and($message->body)
      ->toContain('Sign-in: 8:05 AM')
      ->toContain('Sign-out: 3:45 PM')
      ->toContain('Signed in and signed out');
  }
);

it(
  'uses whatsapp when configured and falls back to linked guardian phone',
  function () {
    config()->set('services.attendance-notification.channel', 'whatsapp');

    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create([
        'guardian_phone' => 'bad-phone'
      ]);
    $guardian = User::factory()
      ->guardian($institution)
      ->create([
        'phone' => '08033334444'
      ]);
    GuardianStudent::factory()
      ->withInstitution($institution)
      ->student($student)
      ->guardianUser($guardian)
      ->create();

    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->signedInOnly()
      ->create([
        'signed_in_at' => now()->setTime(7, 55)
      ]);

    Artisan::call('attendance:notify-guardians');

    Queue::assertPushed(SendWhatsappTemplateMessage::class);
    Queue::assertNotPushed(SendBulksms::class);

    expect(
      Message::query()
        ->first()
        ?->messageRecipients()
        ->first()?->recipient_contact
    )->toBe('08033334444');
  }
);

it(
  'does not notify students without attendance activity for the day',
  function () {
    config()->set('services.attendance-notification.channel', 'sms');

    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create([
        'guardian_phone' => '08012345678'
      ]);

    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->create([
        'signed_in_at' => now()
          ->subDay()
          ->setTime(8, 0),
        'signed_out_at' => now()
          ->subDay()
          ->setTime(15, 0)
      ]);

    Artisan::call('attendance:notify-guardians');

    Queue::assertNothingPushed();
    expect(Message::query()->count())->toBe(0);
  }
);

it(
  'does not send duplicate notifications for the same attendance day',
  function () {
    config()->set('services.attendance-notification.channel', 'sms');

    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create([
        'guardian_phone' => '08012345678'
      ]);

    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->signedInOnly()
      ->create([
        'signed_in_at' => now()->setTime(8, 0)
      ]);
    Attendance::factory()
      ->institutionUser($student->institutionUser)
      ->signedOut()
      ->create([
        'signed_in_at' => now()->setTime(8, 5),
        'signed_out_at' => now()->setTime(15, 30)
      ]);

    Artisan::call('attendance:notify-guardians');
    Artisan::call('attendance:notify-guardians');

    Queue::assertPushed(SendBulksms::class, 1);
    expect(Message::query()->count())->toBe(1);
  }
);

it(
  'skips missing guardian phone numbers without stopping other notifications',
  function () {
    config()->set('services.attendance-notification.channel', 'sms');

    $institution = Institution::factory()->create();
    $studentWithoutGuardianPhone = Student::factory()
      ->withInstitution($institution)
      ->create([
        'guardian_phone' => null
      ]);
    $studentWithGuardianPhone = Student::factory()
      ->withInstitution($institution)
      ->create([
        'guardian_phone' => '08099998888'
      ]);

    Attendance::factory()
      ->institutionUser($studentWithoutGuardianPhone->institutionUser)
      ->signedInOnly()
      ->create([
        'signed_in_at' => now()->setTime(8, 0)
      ]);
    Attendance::factory()
      ->institutionUser($studentWithGuardianPhone->institutionUser)
      ->signedInOnly()
      ->create([
        'signed_in_at' => now()->setTime(8, 10)
      ]);

    Artisan::call('attendance:notify-guardians');

    Queue::assertPushed(SendBulksms::class, 1);
    expect(Message::query()->count())->toBe(1);
  }
);
