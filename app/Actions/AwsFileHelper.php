<?php
namespace App\Actions;

use File;
use Storage;

class AwsFileHelper
{
  static function uploadDirectory($source, $target, $cut = false)
  {
    $files = File::files($source);
    $disk = Storage::disk('s3_public');
    foreach ($files as $file) {
      $file_name = pathinfo($file, PATHINFO_BASENAME);
      // $file_name = $path_info['basename'];
      $disk->put("{$target}/{$file_name}", file_get_contents($file));
    }
    if ($cut) {
      File::deleteDirectory($source);
    }
  }

  static function downloadFromS3(
    $source,
    $target,
    bool $useFileFullPath = false
  ) {
    if (!File::exists($target)) {
      File::makeDirectory($target, 0777, true, true);
    }

    $disk = Storage::disk('s3_public');
    $files = $disk->allFiles($source);

    foreach ($files as $key => $file) {
      $filename = $useFileFullPath
        ? "$target/$file"
        : "$target/" . pathinfo($file, PATHINFO_BASENAME);
      File::put($filename, $disk->get($file));
    }
  }
}
