<?php

namespace App\Http\Controllers\Institutions\Attendance;

use Inertia\Inertia;
use App\Models\Attendance;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\AttendanceType;
use App\Models\InstitutionUser;
use Illuminate\Validation\Rule;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Support\UITableFilters\AttendanceUITableFilters;

class AttendanceController extends Controller
{
  function create()
  {
    return Inertia::render('institutions/attendances/create-attendance', []);
  }

  public function index(Request $request)
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

  public function search(Request $request)
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
    $validatedData = $request->validate([
      'institution_user_id' => 'required|exists:institution_users,id',
      'remark' => 'nullable|string',
      'type' => ['required', Rule::in(AttendanceType::values())],
      'reference' => [
        Rule::requiredIf($request->type === AttendanceType::In->value),
        function ($attr, $value, $fail) use ($request) {
          if ($request->type !== AttendanceType::In->value) {
            return;
          }
          if (Attendance::where('reference', $request->reference)->exists()) {
            $fail('Reference must me unique');
          }
        }
      ]
    ]);

    $staffUser = currentInstitutionUser();

    if ($request->type === AttendanceType::In->value) {
      Attendance::create([
        ...collect($validatedData)->except('type'),
        'institution_id' => $institution->id,
        'institution_staff_user_id' => $staffUser->id,
        'signed_in_at' => now()
      ]);
    } else {
      // == Fetch the last signed_in record of the user, then sign him out.

      $lastSignIn = Attendance::where(
        'institution_user_id',
        $request->institution_user_id
      )
        ->whereNull('signed_out_at')
        ->orderBy('created_at', 'desc')
        ->first();

      if (!$lastSignIn) {
        return $this->message('No Signed-In Record Found.', 400);
      }

      $lastSignIn->update([
        'remark' => $lastSignIn->remark . ' ' . $request->remark,
        'signed_out_at' => now()
      ]);
    }

    return $this->ok();
  }

  function destroy(Institution $institution, Attendance $attendance)
  {
    $staffUser = currentInstitutionUser();

    abort_unless($staffUser->isAdmin(), 403, 'Unauthorized Operation');

    $attendance->delete();
    return $this->ok();
  }
}
