<?php
namespace App\Http\Controllers\Institutions;

use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Storage;

class InstitutionController extends Controller
{
  function index()
  {
    return inertia('institutions/dashboard');
  }

  public function profile(Request $request, Institution $institution)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403, 'Access denied');

    return inertia('institutions/institution-profile', [
      'institution' => $institution
    ]);
  }

  public function update(Request $request, Institution $institution)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403, 'Access denied');

    $data = $request->validate(
      [
        'name' => ['required', 'string'],
        'subtitle' => ['nullable', 'string'],
        'caption' => ['nullable', 'string'],
        'phone' => ['nullable', 'string'],
        'email' => ['nullable', 'string'],
        'address' => ['nullable', 'string'],
        'website' => ['nullable', 'string']
      ],
      $request->all()
    );

    $institution->fill($data)->save();

    return response()->json(['institution' => $institution]);
  }

  public function uploadPhoto(Request $request, Institution $institution)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403, 'Access denied');
    $request->validate([
      'photo' => ['required', 'image', 'mimes:jpg,png,jpeg', 'max:2048']
    ]);
    $imagePath = $request->photo->store(
      $institution->folder(S3Folder::Base),
      's3_public'
    );
    $publicUrl = Storage::disk('s3_public')->url($imagePath);

    $institution->fill(['photo' => $publicUrl])->save();

    return response()->json([
      'url' => $publicUrl
    ]);
  }
}
