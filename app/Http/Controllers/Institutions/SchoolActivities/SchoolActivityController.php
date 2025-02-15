<?php

namespace App\Http\Controllers\Institutions\SchoolActivities;

use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\SchoolActivity;

class SchoolActivityController extends Controller
{
    public function __construct()
    {
        $this->allowedRoles([InstitutionUserType::Admin]);
    }

    //
    public function index(Institution $institution)
    {
        $schoolActivities = $institution->schoolActivities();

        return inertia('institutions/school-activities/list-school-activities', [
            'schoolActivities' => paginateFromRequest($schoolActivities)
        ]);
    }

    function search(Request $request)
    {
        $query = SchoolActivity::query()->when(
            $request->search,
            fn($q, $value) => $q->where('title', 'LIKE', "%$value%")
        );

        return response()->json([
            'result' => $query->get()
        ]);
    }

    function create()
    {
        return inertia('institutions/school-activities/create-edit-school-activity');
    }

    function edit(Institution $institution, SchoolActivity $schoolActivity)
    {
        return inertia('institutions/school-activities/create-edit-school-activity', [
            'schoolActivity' => $schoolActivity
        ]);
    }

    function store(Request $request, Institution $institution)
    {
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        SchoolActivity::create([
            'institution_id' => $institution->id,
            'title' => $data['title'],
            'description' => $data['description'],
        ]);
        return $this->ok();
    }

    function update(Institution $institution, SchoolActivity $schoolActivity, Request $request)
    {
        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        $schoolActivity->fill($data)->save();
        return $this->ok();
    }

    function destroy(Institution $institution, SchoolActivity $schoolActivity)
    {
        $schoolActivity->delete();
        return $this->ok();
    }
}