<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use App\Models\User;
use App\Rules\ValidateExistsRule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
  public function login(Request $request)
  {
    $obj = new ValidateExistsRule(Institution::class, 'code');

    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
      'password' => 'required',
      'institution_code' => ['required', $obj]
    ]);

    if ($validator->fails()) {
      return response()->json(
        [
          'errors' => $validator->errors()
        ],
        422
      );
    }

    $institution = $obj->getModel();

    // Check if user exists and password is correct
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
      return response()->json(
        [
          'message' => 'Invalid credentials'
        ],
        401
      );
    }

    // Check if User belong to the institution
    $checkInstitutionUser = InstitutionUser::where(
      'institution_id',
      $institution->id
    )
      ->where('user_id', $user->id)
      ->first();

    if (!$checkInstitutionUser) {
      throw ValidationException::withMessages([
        'institution_uuid' => 'Institution Mismatch'
      ]);
    }

    // Create API token for the user
    $token = $user->createToken('AttendanceLogin')->plainTextToken;

    return response()->json(
      [
        'message' => 'Login successful',
        'user' => [
          ...$user->toArray(),
          'token' => $token,
          'institution_group' => $institution->institutionGroup,
          'institution' => $institution,
          'institution_user' => $checkInstitutionUser
        ]
      ],
      200
    );
  }
}
