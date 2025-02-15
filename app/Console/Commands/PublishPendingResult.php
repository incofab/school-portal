<?php

namespace App\Console\Commands;

use App\Enums\ManagerRole;
use App\Enums\PriceLists\PaymentStructure;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\ResultPublication;
use App\Models\TermResult;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PublishPendingResult extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'app:publish-pending-result';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Auto publish all the pending results to kickstart our result publication systems. This is used to make sure existing results can still be accessed after the publish result has taken effect.';

  /**
   * Execute the console command.
   */
  public function handle()
  {
    if (!$this->canRun()) {
      $this->comment('Command cannot run -> Deadline exceeded');
      return;
    }
    $terms = TermType::cases();
    $academicSessions = AcademicSession::all();
    $institutions = Institution::query()
      ->with('institutionGroup')
      ->get();

    $unpublishedResultsCount = TermResult::query()
      ->isPublished(false)
      ->count();
    $this->comment(
      "Before start: Unpublished result count $unpublishedResultsCount"
    );

    foreach ($academicSessions as $key => $academicSession) {
      foreach ($terms as $key => $term) {
        foreach ($institutions as $key => $institution) {
          $this->publish($institution, $academicSession, $term->value);
        }
      }
    }

    $unpublishedResultsCount = TermResult::query()
      ->isPublished(false)
      ->count();
    $this->comment(
      "After running: Unpublished result count $unpublishedResultsCount"
    );
  }

  private function canRun()
  {
    $deadline = '2025-03-01';
    return now()->lessThan(Carbon::parse($deadline));
  }

  private function publish(
    Institution $institution,
    AcademicSession $academicSession,
    string $term
  ) {
    $this->createInstitutionGroup($institution);
    $this->comment(
      "{$term} term, {$academicSession->title} session, running for {$institution->name}"
    );
    $binding = [
      'institution_id' => $institution->id,
      'term' => $term,
      'academic_session_id' => $academicSession->id
    ];

    $resultsToPublish = TermResult::query()
      ->where($binding)
      ->whereNull('result_publication_id')
      ->get();

    $publication = ResultPublication::create([
      'institution_id' => $institution->id,
      'institution_group_id' => $institution->institutionGroup->id,
      'term' => $term,
      'academic_session_id' => $academicSession->id,
      'num_of_results' => $resultsToPublish->count(),
      'staff_user_id' => $institution->user_id,
      'payment_structure' => PaymentStructure::PerTerm
    ]);

    //== Update the each $resultsToPublish - Mark as Published
    TermResult::whereIn('id', $resultsToPublish->pluck('id'))->update([
      'result_publication_id' => $publication->id
    ]);
  }

  function createInstitutionGroup(Institution $institution)
  {
    if ($institution->institutionGroup) {
      return;
    }
    $institutionGroup = $institution->institutionGroup()->create([
      'name' => $institution->name,
      'user_id' => $institution->user_id
    ]);
    $institution
      ->fill(['institution_group_id' => $institutionGroup->id])
      ->save();
    $institution->load('institutionGroup');
  }
}
