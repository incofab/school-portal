<?php

namespace App\Http\Controllers\Institutions\Attendance;

use App\Actions\RecordBulkAttendance;
use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\InstitutionUserType;
use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Rules\ValidateExistsRule;
use App\Support\UITableFilters\AttendanceUITableFilters;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
  function create(Institution $institution)
  {
    $staff = InstitutionUser::query()
      ->where('institution_id', $institution->id)
      ->whereIn('role', [
        InstitutionUserType::Admin->value,
        InstitutionUserType::Teacher->value,
        InstitutionUserType::Accountant->value
      ])
      ->with('user')
      ->orderBy('role')
      ->latest('institution_users.id')
      ->get();

    return Inertia::render('institutions/attendances/create-attendance', [
      'staff' => $this->withTodayAttendanceStatus($staff)
    ]);
  }

  public function students(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'classification_id' => [
        'required',
        new ValidateExistsRule(Classification::class)
      ]
    ]);

    $students = InstitutionUser::query()
      ->where('institution_id', $institution->id)
      ->where('role', InstitutionUserType::Student->value)
      ->whereHas(
        'student.classification',
        fn($query) => $query->where(
          'classifications.id',
          $data['classification_id']
        )
      )
      ->with('user', 'student.classification')
      ->join('users', 'users.id', 'institution_users.user_id')
      ->orderBy('users.last_name')
      ->orderBy('users.first_name')
      ->select('institution_users.*')
      ->get();

    return response()->json([
      'result' => $this->withTodayAttendanceStatus($students)
    ]);
  }

  public function index(Request $request, Institution $institution)
  {
    $query = AttendanceUITableFilters::make(
      $request->all(),
      Attendance::query()
    )
      ->filterQuery()
      ->getQuery()
      ->with('institutionUser.user', 'institutionUser.student.classification')
      ->latest('attendances.id');
    return Inertia::render(
      'institutions/attendances/list-institution-attendances',
      [
        'attendance' => paginateFromRequest($query)
      ]
    );
  }

  public function search(Request $request, Institution $institution)
  {
    $query = AttendanceUITableFilters::make(
      $request->all(),
      Attendance::query()
    )
      ->filterQuery()
      ->getQuery()
      ->with('institutionUser.user', 'institutionUser.student.classification')
      ->latest('attendances.id');
    return response()->json([
      'result' => paginateFromRequest($query)
    ]);
  }

  public function store(Request $request, Institution $institution)
  {
    $data = $request->validate(Attendance::createRule());
    $staffUser = currentInstitutionUser();
    abort_unless(
      $staffUser->isStaff(),
      403,
      'You are not authorized to record attendance'
    );

    $res = (new RecordAttendance($institution, $staffUser, $data))->run();

    return $this->apiRes($res, 401);
  }

  public function bulkStore(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'institution_user_ids' => ['present', 'array'],
      'institution_user_ids.*' => [
        'required',
        new ValidateExistsRule(InstitutionUser::class)
      ],
      'unmark_institution_user_ids' => ['present', 'array'],
      'unmark_institution_user_ids.*' => [
        'required',
        new ValidateExistsRule(InstitutionUser::class)
      ],
      'remark' => ['nullable', 'string'],
      'type' => ['required', Rule::in(AttendanceType::values())]
    ]);

    abort_if(
      empty($data['institution_user_ids']) &&
        empty($data['unmark_institution_user_ids']),
      422,
      'No attendance changes were submitted.'
    );

    $staffUser = currentInstitutionUser();
    abort_unless(
      $staffUser->isStaff(),
      403,
      'You are not authorized to record attendance'
    );

    $res = (new RecordBulkAttendance($institution, $staffUser, $data))->run();

    return $this->apiRes($res, 401);
  }

  public function classRegister(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'classification_id' => [
        'required',
        new ValidateExistsRule(Classification::class)
      ],
      'mode' => ['required', Rule::in(['day', 'week'])],
      'date' => ['nullable', 'date']
    ]);

    $date = Carbon::parse($data['date'] ?? now()->toDateString());
    $startDate =
      $data['mode'] === 'week' ? $date->copy()->startOfWeek() : $date->copy();
    $endDate =
      $data['mode'] === 'week' ? $date->copy()->endOfWeek() : $date->copy();

    $students = InstitutionUser::query()
      ->where('institution_id', $institution->id)
      ->where('role', InstitutionUserType::Student->value)
      ->whereHas(
        'student.classification',
        fn($query) => $query->where(
          'classifications.id',
          $data['classification_id']
        )
      )
      ->with('user', 'student.classification')
      ->join('users', 'users.id', 'institution_users.user_id')
      ->orderBy('users.last_name')
      ->orderBy('users.first_name')
      ->select('institution_users.*')
      ->get();

    $attendance = Attendance::query()
      ->where('institution_id', $institution->id)
      ->whereIn('institution_user_id', $students->pluck('id'))
      ->whereDate('signed_in_at', '>=', $startDate->toDateString())
      ->whereDate('signed_in_at', '<=', $endDate->toDateString())
      ->orderBy('signed_in_at')
      ->get()
      ->groupBy(fn(Attendance $attendance) => $attendance->institution_user_id)
      ->map(
        fn($items) => $items
          ->groupBy(
            fn(
              Attendance $attendance
            ) => $attendance->signed_in_at->toDateString()
          )
          ->map(fn($items) => $this->formatAttendanceCell($items->first()))
      );

    $days = collect();
    $cursor = $startDate->copy();
    while ($cursor->lte($endDate)) {
      $days->push([
        'date' => $cursor->toDateString(),
        'label' => $cursor->format('D'),
        'day' => $cursor->format('M j')
      ]);
      $cursor->addDay();
    }

    return response()->json([
      'result' => [
        'students' => $students,
        'days' => $days,
        'attendance' => $attendance,
        'start_date' => $startDate->toDateString(),
        'end_date' => $endDate->toDateString(),
        'mode' => $data['mode']
      ]
    ]);
  }

  public function classRegisterView(Institution $institution)
  {
    return Inertia::render(
      'institutions/attendances/class-attendance-register'
    );
  }

  function destroy(Institution $institution, Attendance $attendance)
  {
    $staffUser = currentInstitutionUser();

    abort_unless($staffUser->isAdmin(), 403, 'Unauthorized Operation');

    $attendance->delete();
    return $this->ok();
  }

  private function withTodayAttendanceStatus($institutionUsers)
  {
    $institutionUserIds = $institutionUsers->pluck('id');
    $today = now()->toDateString();

    $checkedInIds = Attendance::query()
      ->whereIn('institution_user_id', $institutionUserIds)
      ->whereDate('signed_in_at', $today)
      ->pluck('institution_user_id')
      ->all();

    $checkedOutIds = Attendance::query()
      ->whereIn('institution_user_id', $institutionUserIds)
      ->whereDate('signed_out_at', $today)
      ->pluck('institution_user_id')
      ->all();

    return $institutionUsers->map(function (
      InstitutionUser $institutionUser
    ) use ($checkedInIds, $checkedOutIds) {
      $institutionUser->setAttribute('attendance_status', [
        'checked_in' => in_array($institutionUser->id, $checkedInIds),
        'checked_out' => in_array($institutionUser->id, $checkedOutIds)
      ]);

      return $institutionUser;
    });
  }

  private function formatAttendanceCell(Attendance $attendance): array
  {
    return [
      'id' => $attendance->id,
      'signed_in_at' => $attendance->signed_in_at?->toISOString(),
      'signed_out_at' => $attendance->signed_out_at?->toISOString(),
      'remark' => $attendance->remark
    ];
  }
}
