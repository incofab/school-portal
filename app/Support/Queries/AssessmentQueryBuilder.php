<?php

namespace App\Support\Queries;

use App\Enums\TermType;
use Illuminate\Database\Eloquent\Builder;

class AssessmentQueryBuilder extends Builder
{
  public function forMidTerm(bool|null $forMidTerm = true): self
  {
    $this->where(
      fn($q) => $q
        ->whereNull('assessments.for_mid_term')
        ->when(
          $forMidTerm !== null,
          fn($q2) => $q2->orWhere('assessments.for_mid_term', $forMidTerm)
        )
    );
    return $this;
  }

  public function forTerm(string|TermType|null $term): self
  {
    $this->where(
      fn($q) => $q
        ->whereNull('assessments.term')
        ->when(
          $term !== null,
          fn($q2) => $q2->orWhere('assessments.term', $term)
        )
    );
    return $this;
  }
}
