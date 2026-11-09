<?php

namespace App\Console\Commands;

use App\Enums\PriceLists\PaymentStructure;
use App\Models\ResultPublication;
use App\Models\Student;
use App\Models\TermResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PublishStudentResult extends Command
{
  protected $signature = 'app:publish-student-result {student_code?}';

  protected $description = 'Publish one selected unpublished term result for a student';

  public function handle(): int
  {
    $studentCode = trim($this->argument('student_code') ?? $this->ask('Student code'));
    $studentCode = last(explode('/', $studentCode));
    $student = Student::query()->with('institution')->where('code', $studentCode)->first();

    if (!$student) {
      $this->error('Student code not found.');
      return self::FAILURE;
    }

    $results = TermResult::query()->where('student_id', $student->id)
      ->where('institution_id', $student->institution_id)
      ->isPublished(false)->with(['academicSession', 'classification'])
      ->get();

    if ($results->isEmpty()) {
      $this->info('This student has no unpublished term results.');
      return self::SUCCESS;
    }

    $choices = $results->mapWithKeys(fn (TermResult $result) => [
      $result->id => "{$result->id}: {$result->term->value} term, {$result->academicSession?->title}, {$result->classification?->name}"
    ])->all();
    do {
      $resultId = $this->choice('Select a result to publish', $choices);
      $result = $results->firstWhere('id', (int) $resultId);

      if (!$result) {
        $this->warn('That result ID is not in the list. Please choose a listed result ID.');
      }
    } while (!$result);
    $institution = $result->institution;

    if (!$institution->institutionGroup) {
      $group = $institution->institutionGroup()->create([
        'name' => $institution->name,
        'user_id' => $institution->user_id
      ]);
      $institution->update(['institution_group_id' => $group->id]);
      $institution->setRelation('institutionGroup', $group);
    }

    DB::transaction(function () use ($result, $institution) {
      $binding = [
        'institution_group_id' => $institution->institution_group_id,
        'academic_session_id' => $result->academic_session_id,
        'payment_structure' => PaymentStructure::PerTerm->value,
        'term' => $result->term->value
      ];
      $publication = ResultPublication::query()->where($binding)->first();
      if (!$publication) {
        $publication = ResultPublication::create($binding + [
          'institution_id' => $institution->id,
          'num_of_results' => 0,
          'num_of_students' => 0,
          'staff_user_id' => $institution->user_id
        ]);
      }
      $publication->increment('num_of_results');
      $publication->update(['num_of_students' => max(1, $publication->num_of_students)]);
      $result->update([
        'result_publication_id' => $publication->id,
        'is_activated' => true
      ]);
    });

    $this->info("Result {$result->id} published and activated.");
    return self::SUCCESS;
  }
}
