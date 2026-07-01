<?php

namespace App\Services\Messaging\Whatsapp;

use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WhatsappStudentResolver
{
  public function __construct(private PhoneNumberNormalizer $normalizer)
  {
  }

  /**
   * @return Collection<int, Student>
   */
  public function resolve(string $phone): Collection
  {
    $variants = $this->normalizer->lookupVariants($phone);
    if (empty($variants)) {
      return new Collection();
    }

    return Student::query()
      ->select('students.*')
      ->with(
        'user',
        'guardian',
        'classification',
        'institutionUser.institution.institutionSettings'
      )
      ->where(function (Builder $query) use ($variants) {
        $query
          ->whereHas('user', function (Builder $query) use ($variants) {
            $query->whereIn($this->phoneExpression('users.phone'), $variants);
          })
          ->orWhereIn(
            $this->phoneExpression('students.guardian_phone'),
            $variants
          )
          ->orWhereHas('guardian', function (Builder $query) use ($variants) {
            $query->whereIn($this->phoneExpression('users.phone'), $variants);
          });
      })
      ->get()
      ->unique('id')
      ->values();
  }

  private function phoneExpression(string $column)
  {
    return DB::raw(
      "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '')"
    );
  }
}
