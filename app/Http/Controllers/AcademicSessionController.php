<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrUpdateAcademicSessionRequest;
use App\Models\AcademicSession;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicSessionController extends Controller
{
  public function __construct()
  {
    $this->middleware(function (Request $request, Closure $next) {
      abort_unless(currentInstitutionUser()->isAdmin(), 403);
      return $next($request);
    })->only(['create', 'store', 'edit', 'update', 'destroy']);
  }

  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    return Inertia::render('academic-sessions/list-academic-sessions', [
      'academicSessions' => AcademicSession::query()
        ->latest('title')
        ->get()
    ]);
  }

  public function search()
  {
    return response()->json([
      'result' => AcademicSession::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->latest('title')
        ->get()
    ]);
  }

  /**
   * Show the form for creating a new resource.
   */
  public function create()
  {
    return Inertia::render('academic-sessions/create-academic-session');
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(CreateOrUpdateAcademicSessionRequest $request)
  {
    $academicSession = AcademicSession::create($request->validated());
    return response()->json(['data' => $academicSession]);
  }

  /**
   * Display the specified resource.
   */
  public function show(AcademicSession $academicSession)
  {
    return Inertia::render('academic-sessions/show-academic-session', [
      'academicSession' => $academicSession
    ]);
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(AcademicSession $academicSession)
  {
    return Inertia::render('academic-sessions/create-academic-session', [
      'academicSession' => $academicSession
    ]);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(
    CreateOrUpdateAcademicSessionRequest $request,
    AcademicSession $academicSession
  ) {
    $academicSession->fill($request->validated())->save();
    return response()->json(['data' => $academicSession]);
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(AcademicSession $academicSession)
  {
    $academicSession->delete();
    // return response()->json(['ok' => true]);
  }
}
