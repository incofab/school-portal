<?php
namespace App\Http\Controllers\CCD;

use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Support\QuestionCourseable;
use Illuminate\Http\Request;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Storage;
use Str;

class UploadTinyMceImageController extends Controller
{
  function __invoke(
    Institution $institution,
    QuestionCourseable $morphable,
    Request $request
  ) {
    $request->validate(['file' => ['required', 'file']]);

    $file = $request->file('file');
    $filename = Str::orderedUuid() . '.' . $file->clientExtension();
    ImageOptimizer::optimize($file);

    $imagePath = $file->storeAs(
      $institution->folder(S3Folder::CCD),
      $filename,
      's3_public'
    );

    $publicUrl = Storage::disk('s3_public')->url($imagePath);

    return response()->json([
      // 'location' => basename($publicUrl)
      'location' => $publicUrl
    ]);
  }
}
