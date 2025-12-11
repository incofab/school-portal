<?php

namespace App\Http\Controllers\Institutions\Attendance;

use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\TermDetail;
use App\Rules\ValidateExistsRule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class StudentAttendanceReportController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  public function index(Request $request, Institution $institution)
  {
    return inertia('institutions/attendances/student-attendance-report', [
      'academicSessions' => AcademicSession::query()
        ->orderByDesc('id')
        ->get(['id', 'title'])
    ]);
  }

  function report(Institution $institution, Request $request)
  {
    $instUserVal = new ValidateExistsRule(InstitutionUser::class);
    $data = $request->validate([
      'institution_user_id' => ['required', 'integer', $instUserVal],
      'academic_session_id' => [
        'required',
        'integer',
        'exists:academic_sessions,id'
      ],
      'term' => ['nullable', new Enum(TermType::class)]
    ]);

    $termDetail = TermDetail::query()
      ->where([
        'academic_session_id' => $data['academic_session_id'],
        'term' => $data['term']
      ])
      ->first();

    abort_unless(
      $termDetail?->end_date && $termDetail?->start_date,
      400,
      'Term detail has not set'
    );

    $institutionUser = $instUserVal->getModel();
    $institutionUser->load('user', 'student');
    $report = $this->buildReport($institutionUser, $termDetail);
    return response()->json([
      'report' => [
        ...$report,
        'academic_sessioon' => AcademicSession::find(
          $data['academic_session_id']
        ),
        'term' => $data['term']
      ]
    ]);
  }

  private function buildReport(
    InstitutionUser $institutionUser,
    ?TermDetail $termDetail
  ): array {
    $attendanceQuery = Attendance::query()
      ->with(['institutionUser.user', 'staffUser.user'])
      ->where('institution_user_id', $institutionUser->id)
      ->orderBy('signed_in_at');

    if ($termDetail?->start_date) {
      $attendanceQuery->whereDate(
        'signed_in_at',
        '>=',
        $termDetail->start_date
      );
    }
    if ($termDetail?->end_date) {
      $attendanceQuery->whereDate('signed_in_at', '<=', $termDetail->end_date);
    }

    $attendance = $attendanceQuery->get();
    $attendanceDays = $attendance
      ->map(fn($record) => Carbon::parse($record->signed_in_at)->toDateString())
      ->unique()
      ->values();

    $lowerBound =
      $termDetail?->start_date?->toDateString() ?? $attendanceDays->min();
    $upperBound =
      $termDetail?->end_date?->toDateString() ?? $attendanceDays->max();

    $activeDays = null;
    if ($lowerBound && $upperBound && $termDetail) {
      $period = CarbonPeriod::create($lowerBound, $upperBound);
      $activeDays = collect($period)->reduce(
        fn($carry, Carbon $date) => $carry +
          ($termDetail->isActiveOnDate($date) ? 1 : 0),
        0
      );
    }

    $expectedAttendance =
      $termDetail?->expected_attendance_count ?? $activeDays;

    return [
      'attendance' => $attendance,
      'attendance_days' => $attendanceDays,
      'lower_bound' => $lowerBound,
      'upper_bound' => $upperBound,
      'active_days' => $activeDays,
      'expected_attendance' => $expectedAttendance,
      'actual_attendance' => $attendanceDays->count(),
      'institution_user' => $institutionUser,
      'termDetail' => $termDetail
    ];
  }
}
