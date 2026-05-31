<?php

namespace App\Http\Controllers\CCD;

use App\Enums\Media\MediaVisibility;
use App\Enums\S3Folder;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Support\QuestionCourseable;
use App\Support\Media\MediaManager;
use Illuminate\Http\Request;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Str;

class UploadTinyMceImageController extends Controller
{
  public function __invoke(
    Institution $institution,
    QuestionCourseable $morphable,
    Request $request
  ) {
    $request->validate(['file' => ['required', 'file']]);

    $file = $request->file('file');
    $filename = Str::orderedUuid() . '.' . $file->clientExtension();
    ImageOptimizer::optimize($file);

    $res = app(MediaManager::class)->storeUploadedFile(
      $file,
      $morphable,
      'tinymce_image',
      $institution->folder(S3Folder::CCD),
      $institution,
      currentUser(),
      visibility: MediaVisibility::Public,
      meta: ['requested_filename' => $filename]
    );

    return response()->json([
      'location' => $res->media?->url
    ]);
  }
}
