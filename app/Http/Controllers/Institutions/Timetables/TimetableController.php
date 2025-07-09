<?php

namespace App\Http\Controllers\Institutions\Timetables;

use App\Models\Timetable;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\InstitutionUser;
use App\Models\TimetableCoordinator;
use App\Rules\ValidateExistsRule;
use Illuminate\Validation\Rule;

class TimetableController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except('index', 'show');
  }

  //
  function index(Request $request, Institution $institution)
  {
    $institutionUser = currentInstitutionUser();

    if ($institutionUser->isStudent()) {
      $student = $institutionUser
        ?->student()
        ->with('classification')
        ->first();
      $classification = Classification::find($student->classification_id);
      return $this->classTimetable($institution, $classification);
    }

    if ($institutionUser->isTeacher()) {
      $getCoordinationIds = TimetableCoordinator::where(
        'institution_user_id',
        $institutionUser->id
      )->pluck('timetable_id');
      $getTimetables = Timetable::whereIn('id', $getCoordinationIds)
        ->with('timetableCoordinators.institutionUser.user')
        ->with('actionable')
        ->orderBy('start_time', 'asc')
        ->get();

      return inertia('institutions/timetables/list-timetables')->with([
        'timetables' => $getTimetables
      ]);
    }
  }

  public function classTimetable(
    Institution $institution,
    Classification $classification
  ) {
    $getTimetables = Timetable::where('classification_id', $classification->id)
      ->with('timetableCoordinators.institutionUser.user')
      ->with('actionable')
      ->orderBy('start_time', 'asc')
      ->get();

    return inertia('institutions/timetables/list-timetables')->with([
      'timetables' => $getTimetables,
      'classificationId' => $classification->id
    ]);
  }

  public function store(Institution $institution, Request $request)
  {
    $data = $request->validate([
      'day' => ['required', 'between:0,6'],
      'institution_id' => ['required'],
      'classification_id' => [
        'required',
        new ValidateExistsRule(Classification::class)
      ],
      'start_time' => ['required', 'date_format:H:i'],
      'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
      'actionable_type' => [
        'required',
        Rule::in(['course', 'school-activity'])
      ],
      'actionable_id' => ['required', 'integer'],
      'coordinators' => ['required', 'array'],
      'coordinators.*.coordinator_user_id' => [
        'required',
        new ValidateExistsRule(InstitutionUser::class)
      ]
    ]);

    $coordinators = $data['coordinators'];

    /** @var Timetable $timetable */
    $timetable = Timetable::query()->updateOrCreate(
      collect($data)
        ->only('day', 'start_time', 'end_time', 'classification_id')
        ->toArray(),
      collect($data)
        ->only(
          'institution_id',
          'start_time',
          'end_time',
          'actionable_type',
          'actionable_id'
        )
        ->toArray()
    );
    foreach ($coordinators as $key => $value) {
      $timetable->timetableCoordinators()->firstOrCreate([
        'institution_user_id' => $value['coordinator_user_id']
      ]);
    }
    $timetable
      ->timetableCoordinators()
      ->whereNotIn(
        'institution_user_id',
        array_map(fn($item) => $item['coordinator_user_id'], $coordinators)
      )
      ->delete();
    return $this->ok();
  }

  function destroy(Institution $institution, Timetable $timetable)
  {
    $timetable->delete();
    return $this->ok();
  }
}
