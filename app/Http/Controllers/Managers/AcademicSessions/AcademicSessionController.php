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

    return Inertia::render(
      'managers/academic-sessions/list-academic-sessions',
      [
        'academicSessions' => paginateFromRequest($query)
      ]
    );
  }

  public function create()
  {
    return Inertia::render(
      'managers/academic-sessions/create-edit-academic-session'
    );
  }

  public function store(CreateOrUpdateAcademicSessionRequest $request)
  {
    $data = $request->validated();
    $academicSession = AcademicSession::query()->create($data);

    if ($data['is_active'] ?? false) {
      $academicSession->activate();
    }

    return $this->ok(['academicSession' => $academicSession]);
  }

  public function edit(AcademicSession $academicSession)
  {
    return Inertia::render(
      'managers/academic-sessions/create-edit-academic-session',
      [
        'academicSession' => $academicSession
      ]
    );
  }

  public function update(
    CreateOrUpdateAcademicSessionRequest $request,
    AcademicSession $academicSession
  ) {
    $data = $request->validated();
    $academicSession->fill($data)->save();

    if ($data['is_active'] ?? false) {
      $academicSession->activate();
    }

    return $this->ok(['academicSession' => $academicSession]);
  }

  public function activate(AcademicSession $academicSession)
  {
    $academicSession->activate();

    return $this->ok(['academicSession' => $academicSession]);
  }

  public function destroy(AcademicSession $academicSession)
  {
    $academicSession->delete();

    return $this->ok();
  }
}
