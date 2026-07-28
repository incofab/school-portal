<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use App\Models\CourseResult;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** @deprecated No longer in use and can be deleted from the date: 30/08/2026 */
class BackfillCourseResultAssessmentKeys extends Command
{
  protected $signature = 'course-results:backfill-assessment-keys {--chunk=1000 : Number of course results to process per chunk} {--dry-run : Count affected rows without updating them}';

  protected $description = 'Backfill course result assessment_values keys from title-only keys to title|assessment_id keys.';

  /** @var array<string, Collection<int, Assessment>> */
  private array $assessmentCache = [];

  public function handle(): int
  {
    $chunkSize = max(1, (int) $this->option('chunk'));
    $dryRun = (bool) $this->option('dry-run');
    $scanned = 0;
    $updated = 0;

    CourseResult::query()
      ->select([
        'id',
        'institution_id',
        'classification_id',
        'term',
        'for_mid_term',
        'assessment_values'
      ])
      ->whereNotNull('assessment_values')
      ->orderBy('id')
      ->chunkById($chunkSize, function (Collection $courseResults) use (
        &$scanned,
        &$updated,
        $dryRun
      ) {
        foreach ($courseResults as $courseResult) {
          $scanned++;
          $normalizedValues = $this->normalizedAssessmentValues($courseResult);

          if ($normalizedValues === null) {
            continue;
          }

          $updated++;
          if ($dryRun) {
            continue;
          }

          DB::table('course_results')
            ->where('id', $courseResult->id)
            ->update([
              'assessment_values' => json_encode($normalizedValues)
            ]);
        }

        $this->components->info(
          "Scanned {$scanned} course results; " .
            ($dryRun ? 'would update' : 'updated') .
            " {$updated}."
        );
      });

    $this->components->info(
      "Done. Scanned {$scanned} course results; " .
        ($dryRun ? 'would update' : 'updated') .
        " {$updated}."
    );

    return self::SUCCESS;
  }

  private function normalizedAssessmentValues(
    CourseResult $courseResult
  ): ?array {
    $values = (array) ($courseResult->assessment_values ?? []);
    if (empty($values)) {
      return null;
    }

    $normalizedValues = [];
    $changed = false;
    foreach ($values as $key => $score) {
      if (Assessment::resultKeyId((string) $key)) {
        $normalizedValues[$key] = $score;
        continue;
      }

      $assessment = $this->assessmentsFor($courseResult)->first(
        fn(Assessment $assessment) => $assessment->raw_title === $key
      );

      if (!$assessment) {
        $normalizedValues[$key] = $score;
        continue;
      }

      $resultKey = $assessment->assessmentResultKey();
      if (!array_key_exists($resultKey, $values)) {
        $normalizedValues[$resultKey] = $score;
      }
      $changed = true;
    }

    return $changed ? $normalizedValues : null;
  }

  /** @return Collection<int, Assessment> */
  private function assessmentsFor(CourseResult $courseResult): Collection
  {
    $cacheKey = implode('|', [
      $courseResult->institution_id,
      $courseResult->term?->value ?? $courseResult->term,
      (int) $courseResult->for_mid_term,
      $courseResult->classification_id
    ]);

    if (isset($this->assessmentCache[$cacheKey])) {
      return $this->assessmentCache[$cacheKey];
    }

    $assessments = Assessment::query()
      ->withoutGlobalScopes()
      ->withTrashed()
      ->where('institution_id', $courseResult->institution_id)
      ->forTerm($courseResult->term)
      ->forMidTerm((bool) $courseResult->for_mid_term)
      ->with('classifications')
      ->get()
      ->filter(function (Assessment $assessment) use ($courseResult) {
        if ($assessment->classifications->isEmpty()) {
          return true;
        }

        return $assessment->classifications->contains(
          'id',
          $courseResult->classification_id
        );
      })
      ->values();

    return $this->assessmentCache[$cacheKey] = $assessments;
  }
}
