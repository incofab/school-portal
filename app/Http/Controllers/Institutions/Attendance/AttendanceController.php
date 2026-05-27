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
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
      'classification_group_id' => [
        'required',
        Rule::exists('classification_groups', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'classification_id' => [
        'nullable',
        Rule::exists('classifications', 'id')->where(
          'institution_id',
          $institution->id
        )
      ]
    ]);

    $students = InstitutionUser::query()
      ->where('institution_id', $institution->id)
      ->where('role', InstitutionUserType::Student->value)
      ->whereHas(
        'student.classification',
        fn($query) => $query
          ->where('classification_group_id', $data['classification_group_id'])
          ->when(
            $data['classification_id'] ?? null,
            fn($query, $classificationId) => $query->where(
              'classifications.id',
              $classificationId
            )
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
    ///
    // $validatedData = $request->validate([
    //   'institution_user_id' => 'required|exists:institution_users,id',
    //   'remark' => 'nullable|string',
    //   'type' => ['required', Rule::in(AttendanceType::values())],
    //   'reference' => [
    //     Rule::requiredIf($request->type === AttendanceType::In->value),
    //     function ($attr, $value, $fail) use ($request) {
    //       if ($request->type !== AttendanceType::In->value) {
    //         return;
    //       }
    //       if (Attendance::where('reference', $request->reference)->exists()) {
    //         $fail('Reference must me unique');
    //       }
    //     }
    //   ]
    // ]);

    // $staffUser = currentInstitutionUser();

    // if ($request->type === AttendanceType::In->value) {
    //   Attendance::create([
    //     ...collect($validatedData)->except('type'),
    //     'institution_id' => $institution->id,
    //     'institution_staff_user_id' => $staffUser->id,
    //     'signed_in_at' => now()
    //   ]);
    // } else {
    //   // == Fetch the last signed_in record of the user, then sign him out.

    //   $lastSignIn = Attendance::where(
    //     'institution_user_id',
    //     $request->institution_user_id
    //   )
    //     ->whereNull('signed_out_at')
    //     ->orderBy('created_at', 'desc')
    //     ->first();

    //   if (!$lastSignIn) {
    //     return $this->message('No Signed-In Record Found.', 400);
    //   }

    //   $lastSignIn->update([
    //     'remark' => $lastSignIn->remark . ' ' . $request->remark,
    //     'signed_out_at' => now()
    //   ]);
    // }

    // return $this->ok();
  }

  public function bulkStore(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'institution_user_ids' => ['present', 'array'],
      'institution_user_ids.*' => [
        'required',
        Rule::exists('institution_users', 'id')->where(
          'institution_id',
          $institution->id
        )
      ],
      'unmark_institution_user_ids' => ['present', 'array'],
      'unmark_institution_user_ids.*' => [
        'required',
        Rule::exists('institution_users', 'id')->where(
          'institution_id',
          $institution->id
        )
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
}
