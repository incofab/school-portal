<?php
namespace App\Actions;

use App\Models\Assignment;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Support\SettingsHandler;
use Illuminate\Support\Facades\DB;

class RecordAssignment
{
  /**
   * @param array{
   *  course_id: int,
   *  max_score: int,
   *  content: string,
   *  expires_at: string,
   *  classification_ids: int[],
   * } $data
   */
  function __construct(
    private Institution $institution,
    private InstitutionUser $institutionUser,
    private array $data
  ) {
  }

  public function create()
  {
    DB::beginTransaction();
    $settingsHandler = SettingsHandler::makeFromRoute();

    $assignment = $this->institution
      ->assignments()
      ->create([
        ...collect($this->data)->except('classification_ids')->toArray(),
        'institution_user_id' => $this->institutionUser->id,
        'academic_session_id' => $settingsHandler->getCurrentAcademicSession(),
        'term' => $settingsHandler->getCurrentTerm()
      ]);

      $this->syncClasses($assignment);

    DB::commit();

    return $assignment;
  }

  function update(Assignment $assignment)
  {
    DB::beginTransaction();

    $assignment
      ->fill(
        collect($this->data)
          ->except('classification_ids')
          ->toArray()
      )
      ->save();

      $this->syncClasses($assignment);

    DB::commit();
  }

  private function syncClasses(Assignment $assignment)
  {
    $assignment
      ->classifications()
      ->syncWithPivotValues(
        $this->data['classification_ids'],
        ['institution_id' => $this->institution->id]
      );
  }
}
