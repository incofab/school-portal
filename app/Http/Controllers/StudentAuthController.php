<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StudentAuthController extends Controller
{
  public function showLogin()
  {
    return Inertia::render('student-login');
  }

  public function login()
  {
    $data = request()->validate([
      'student_code' => ['required', 'string'],
      'password' => ['nullable', 'string']
    ]);

    $student = Student::where('code', $data['student_code'])
      ->with('user')
      ->first();

    abort_unless($student, 403, 'Invalid credentials');

    $credentials = [
      'email' => $student->user->email,
      'password' => $data['password'] ?? config('app.user_default_password')
    ];

    if (!Auth::attempt($credentials)) {
      return response()->json(['should_enter_password' => true]);
    }

    return response()->json(['should_enter_password' => false]);
  }
}
