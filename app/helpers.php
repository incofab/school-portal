<?php

use App\DTO\BreadCrumb;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Support\Res;

if (!function_exists('currentUser')) {
  function currentUser(): User|null
  {
    /** @var User */
    $user = auth()->user();
    return $user;
  }
}

if (!function_exists('currentInstitution')) {
  function currentInstitution(): Institution|null
  {
    $institution = request()->route('institution');
    if (!($institution instanceof Institution)) {
      return null;
    }
    return $institution;
  }
}

if (!function_exists('currentInstitutionUser')) {
  function currentInstitutionUser(): InstitutionUser|null
  {
    return currentInstitution()?->institutionUsers?->first();
  }
}

if (!function_exists('isProduction')) {
  function isProduction(): bool
  {
    return app()->environment('production');
  }
}

if (!function_exists('isLocal')) {
  function isLocal(): bool
  {
    return app()->environment('local');
  }
}

if (!function_exists('isTesting')) {
  function isTesting(): bool
  {
    return app()->environment('testing');
  }
}

if (!function_exists('removeHyphenAndCapitalize')) {
  function removeHyphenAndCapitalize($string): string
  {
    return ucwords(str_replace('-', ' ', $string));
  }
}

if (!function_exists('paginateFromRequest')) {
  function paginateFromRequest(
    $query
  ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
    $perPage = request()->query('perPage', 100);
    $page = request()->query('page');

    return $query->paginate(perPage: (int) $perPage, page: (int) $page);
  }
}

if (!function_exists('failRes')) {
  function failRes($message, array $data = []): Res
  {
    return new Res(['success' => false, 'message' => $message, ...$data]);
  }
}

if (!function_exists('successRes')) {
  function successRes($message = '', array $data = []): Res
  {
    return new Res(['success' => true, 'message' => $message, ...$data]);
  }
}

if (!function_exists('dlog')) {
  /** Helper to log data using json encode with pretty print */
  function dlog($data)
  {
    info(json_encode($data, JSON_PRETTY_PRINT));
  }
}

if (!function_exists('breadCrumb')) {
  function breadCrumb(
    string $title,
    string $route = '',
    string $icon = '',
    bool $active = false
  ) {
    return new BreadCrumb($title, $route, $icon, $active);
  }
}

if (!function_exists('instRoute')) {
  function instRoute($routeSuffix, $moreParam = [], $institution = null)
  {
    $institution = $institution ?? currentInstitution();
    $params = [$institution];
    if (is_array($moreParam)) {
      $params = array_merge($params, $moreParam);
    } else {
      $params[] = $moreParam;
    }
    return route("institutions.{$routeSuffix}", $params);
  }
}

if (!function_exists('randomDigits')) {
  function randomDigits($length)
  {
    $result = '';
    for ($i = 0; $i < $length; $i++) {
      $result .= random_int(0, 9);
    }
    return $result;
  }
}

if (!function_exists('sanitizeFilename')) {
  function sanitizeFilename(string $filename): string
  {
    $filename = basename($filename);
    $sanitized = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    return $extension ? "{$sanitized}.{$extension}" : $sanitized;
  }
}

if (!function_exists('deleteMigrationEntry')) {
  /** Helper to delete migration files
   * @param array $filenames with the .php extension. Remember to manually delete the file from migrations folder
   */
  function deleteMigrationEntry(array $filenames): void
  {
    \DB::table('migrations')
      ->whereIn('migration', $filenames)
      ->delete();

    // foreach ($filenames as $key => $filename) {
    //   $filePath = database_path("migrations/$filename.php");
    //   if (!\File::exists($filePath)) {
    //     continue;
    //   }
    //   try {
    //     File::delete($filePath);
    //   } catch (\Exception $e) {
    //     info("Error deleting file: {$filePath} | {$e->getMessage()}");
    //   }
    // }
  }
}
