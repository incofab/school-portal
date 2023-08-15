<?php
namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use Illuminate\Http\Request;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Storage;

class UploadTinyMceImageController extends Controller
{
  function __invoke(CourseSession $courseSession, Request $request)
  {
    $request->validate(['file' => ['required', 'file']]);

    ImageOptimizer::optimize($request->file);

    $imagePath = $request->file->store(
      "content/{$courseSession->course_id}/{$courseSession->id}",
      's3_public'
    );
    $publicUrl = Storage::disk('s3_public')->url($imagePath);

    return response()->json([
      // 'location' => basename($publicUrl)
      'location' => $publicUrl
    ]);
  }
}
