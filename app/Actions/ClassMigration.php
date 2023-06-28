<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class ClassMigration
{
  private Builder $studentsQuery;
  function __construct(private Classification $sourceClass)
  {
    $this->studentsQuery = Student::query()
      ->select('students.*')
      ->where('classification_id', $this->sourceClass->id);
  }

  public static function make(Classification $sourceClass)
  {
    return new self($sourceClass);
  }

  public function migrate(Classification $destinationClass)
  {
    (clone $this->studentsQuery)->update([
      'classification_id' => $destinationClass->id
    ]);

    // Record this in the class movement activity
  }

  public function moveToAlumni()
  {
    (clone $this->studentsQuery)->update(['classification_id' => null]);
    (clone $this->studentsQuery)
      ->join(
        'institution_users',
        'institution_users.id',
        'students.institution_user_id'
      )
      ->update(['institution_users.role' => InstitutionUserType::Alumni]);

    // Record this in the class movement activity
  }
}
