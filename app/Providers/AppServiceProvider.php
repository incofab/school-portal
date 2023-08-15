<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    //
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    Relation::enforceMorphMap([
      'user' => \App\Models\User::class,
      'course-session' => \App\Models\CourseSession::class,
      'question' => \App\Models\Question::class,
      'passage' => \App\Models\Passage::class,
      'instruction' => \App\Models\Instruction::class,
      'institution' => \App\Models\Institution::class,
      'institution-user' => \App\Models\InstitutionUser::class,
      'institution-setting' => \App\Models\InstitutionSetting::class,
      'course' => \App\Models\Course::class,
      'fee' => \App\Models\Fee::class,
      'fee-payment' => \App\Models\FeePayment::class,
      'fee-payment-track' => \App\Models\FeePaymentTrack::class,
      'academic-session' => \App\Models\AcademicSession::class,
      'admin' => \App\Models\Admin::class,
      'Admission-application' => \App\Models\AdmissionApplication::class,
      'assessment' => \App\Models\Assessment::class,
      'classification' => \App\Models\Classification::class,
      'class-result-info' => \App\Models\ClassResultInfo::class,
      'course-result' => \App\Models\CourseResult::class,
      'course-teacher' => \App\Models\CourseTeacher::class,
      'learning-evaluation' => \App\Models\LearningEvaluation::class,
      'learning-evaluation-domain' =>
        \App\Models\LearningEvaluationDomain::class,
      'pin' => \App\Models\Pin::class,
      'pin-print' => \App\Models\PinPrint::class,
      'pin-generator' => \App\Models\PinGenerator::class,
      'session-result' => \App\Models\SessionResult::class,
      'student' => \App\Models\Student::class,
      'summary' => \App\Models\Summary::class,
      'term-result' => \App\Models\TermResult::class,
      'topic' => \App\Models\Topic::class
    ]);
  }
}
