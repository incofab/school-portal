<?php

namespace App\Support\UITableFilters;

use App\Support\SettingsHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;

abstract class BaseUITableFilter
{
  protected array $sortableColumns;
  private $calledFns = [];
  private SettingsHandler $settingHandler;

  public function __construct(
    protected array $requestData,
    protected Builder $baseQuery
  ) {
    $this->settingHandler = SettingsHandler::makeFromRoute();
    $this->validateRequestData();
  }

  protected function extraValidationRules(): array
  {
    return [];
  }

  public static function make(array $requestData, Builder $baseQuery): static
  {
    return new static($requestData, $baseQuery);
  }

  private function validateRequestData(): static
  {
    $this->requestData = Validator::validate($this->requestData, [
      'sortDir' => ['required_with:sortKey', 'string'],
      'sortKey' => ['required_with:sortDir', 'string'],
      'search' => ['nullable', 'string'],
      'institution_id' => ['sometimes', 'integer'],
      ...$this->extraValidationRules()
    ]);

    return $this;
  }

  function forInstitution()
  {
    $this->requestData['institution_id'] = currentInstitution()->id;
    return $this;
  }

  function forCurrentTerm()
  {
    $this->requestData['term'] = $this->settingHandler->getCurrentAcademicSession();
    $this->requestData['term'] = $this->settingHandler->getCurrentTerm();
    return $this;
  }

  public function sortQuery(): static
  {
    $sortDir = $this->requestData['sortDir'] ?? null;
    $sortKey = $this->requestData['sortKey'] ?? null;
    $columnName = $this->sortableColumns[$sortKey] ?? null;

    if (empty($columnName)) {
      return $this;
    }

    $this->baseQuery->orderBy($columnName, $sortDir);

    return $this;
  }
  public function getQuery()
  {
    return $this->baseQuery;
  }

  /** Handle searches from the url request params */
  abstract protected function directQuery();

  /** Perform a search */
  abstract protected function generalSearch(string $search);

  public function filterQuery(): static
  {
    return $this->directQuery()->when(
      $this->requestGet('search'),
      fn(self $that, $search) => $that->generalSearch($search)
    );
  }

  protected function requestGet($key)
  {
    return $this->requestData[$key] ?? null;
  }

  protected function when($value, callable $callback, ?callable $negativeCallback = null): static
  {
    if ($value) {
      $callback($this, $value);
    } else {
      if ($negativeCallback) {
        $negativeCallback($this, $value);
      }
    }
    return $this;
  }

  protected function callOnce($key, callable $callback): static
  {
    if (!in_array($key, $this->calledFns)) {
      $this->calledFns[] = $key;
      $callback();
    }
    return $this;
  }

  private $useCurrentTerm = true;
  /** Should be called before the filter query is called */
  public function dontUseCurrentTerm()
  {
    $this->useCurrentTerm = false;
    return $this;
  }

  protected function getTerm($default = null)
  {
    return $this->requestGet('term') ??
      ($this->useCurrentTerm
        ? $this->settingHandler->getCurrentTerm($default)
        : null);
  }

  protected function getAcademicSession($default = null)
  {
    return $this->requestGet('academicSession') ??
      ($this->useCurrentTerm
        ? $this->settingHandler->getCurrentAcademicSession($default)
        : null);
  }
}