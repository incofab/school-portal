<?php

namespace App\Http\Controllers\Managers\AcademicSessions;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrUpdateAcademicSessionRequest;
use App\Models\AcademicSession;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = AcademicSession::query()
            ->latest('order_index')
            ->latest('id');

        return Inertia::render('managers/academic-sessions/list-academic-sessions', [
            'academicSessions' => paginateFromRequest($query),
        ]);
    }

    public function create()
    {
        return Inertia::render('managers/academic-sessions/create-edit-academic-session');
    }

    public function store(CreateOrUpdateAcademicSessionRequest $request)
    {
        $academicSession = AcademicSession::query()->create($request->validated());

        return $this->ok(['academicSession' => $academicSession]);
    }

    public function edit(AcademicSession $academicSession)
    {
        return Inertia::render('managers/academic-sessions/create-edit-academic-session', [
            'academicSession' => $academicSession,
        ]);
    }

    public function update(
        CreateOrUpdateAcademicSessionRequest $request,
        AcademicSession $academicSession
    ) {
        $academicSession->fill($request->validated())->save();

        return $this->ok(['academicSession' => $academicSession]);
    }

    public function destroy(AcademicSession $academicSession)
    {
        $academicSession->delete();

        return $this->ok();
    }
}
