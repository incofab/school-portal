<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Enums\InstitutionUserType;
use App\Enums\ResultExamMode;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TermDetail;
use App\Support\SettingsHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class TermDetailController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Institution $institution)
  {
    $query = TermDetail::query()
      ->with('academicSession')
      ->latest('academic_session_id')
      ->latest('term');

    return inertia('institutions/term-details/list-term-details', [
      'termDetails' => paginateFromRequest($query)
    ]);
  }

  public function create(Institution $institution)
  {
    $termDetail = SettingsHandler::makeFromRoute()->fetchCurrentTermDetail();
    $termDetail->load('academicSession');

    return inertia('institutions/term-details/create-edit-term-detail', [
      'termDetail' => $termDetail,
      'mode' => 'create'
    ]);
  }

  public function edit(Institution $institution, TermDetail $termDetail)
  {
    $termDetail->load('academicSession');

    return inertia('institutions/term-details/create-edit-term-detail', [
      'termDetail' => $termDetail,
      'mode' => 'edit'
    ]);
  }

  public function update(
    Request $request,
    Institution $institution,
    TermDetail $termDetail
  ) {
    $data = $request->validate([
      'expected_attendance_count' => ['nullable', 'integer'],
      'start_date' => ['nullable', 'date'],
      'end_date' => ['nullable', 'date'],
      'next_term_resumption_date' => ['nullable', 'date'],
      'inactive_weekdays' => ['nullable', 'array'],
      'inactive_weekdays.*' => ['integer', 'between:0,6'],
      'special_active_days' => ['nullable', 'array'],
      'special_active_days.*.date' => [
        'required_with:special_active_days',
        'date'
      ],
      'special_active_days.*.reason' => [
        'required_with:special_active_days',
        'string'
      ],
      'inactive_days' => ['nullable', 'array'],
      'inactive_days.*.date' => ['required_with:inactive_days', 'date'],
      'inactive_days.*.reason' => ['required_with:inactive_days', 'string'],
      'result_exam_mode' => ['nullable', new Enum(ResultExamMode::class)]
    ]);
    $data['inactive_weekdays'] = array_values(
      array_unique(array_map('intval', $data['inactive_weekdays'] ?? []))
    );
    $data['special_active_days'] = $this->filterDayReasons(
      $data['special_active_days'] ?? []
    );
    $data['inactive_days'] = $this->filterDayReasons(
      $data['inactive_days'] ?? []
    );
    $termDetail->fill($data)->save();

    return $this->ok();
  }

  private function filterDayReasons(array $days): array
  {
    return collect($days)
      ->filter(fn($day) => ($day['date'] ?? null) && ($day['reason'] ?? null))
      ->map(
        fn($day) => [
          'date' => $day['date'],
          'reason' => trim($day['reason'])
        ]
      )
      ->values()
      ->all();
  }
}
