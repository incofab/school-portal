<?php

namespace App\Console\Commands;

use App\Models\AdmissionApplication;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\InstitutionSetting;
use App\Models\ManualPayment;
use App\Models\Media;
use App\Models\User;
use App\Support\Media\MediaManager;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MigrateLegacyMediaCommand extends Command
{
  protected $signature = 'media:migrate-legacy
        {--disk=s3_public : The filesystem disk that stores legacy media}
        {--skip-disk-scan : Skip scanning the disk for orphaned files}
        {--dry-run : Report what would be migrated without creating media records}';

  protected $description = 'Backfill the new media table from legacy media references and existing S3 files.';

  private int $createdCount = 0;

  public function handle(MediaManager $mediaManager): int
  {
    $disk = (string) $this->option('disk');
    $dryRun = (bool) $this->option('dry-run');

    $this->line("Migrating legacy media on disk [{$disk}]...");

    $this->migrateModelColumn(
      Institution::query()->whereNotNull('photo'),
      'institutions.photo',
      'profile_photo',
      fn(Institution $institution) => $institution->photo,
      fn(Institution $institution) => $institution,
      $mediaManager,
      $disk,
      $dryRun
    );

    $this->migrateModelColumn(
      User::query()->whereNotNull('photo'),
      'users.photo',
      'profile_photo',
      fn(User $user) => $user->photo,
      fn(User $user) => $user
        ->institutionUsers()
        ->with('institution')
        ->latest('id')
        ->first()?->institution,
      $mediaManager,
      $disk,
      $dryRun
    );

    $this->migrateModelColumn(
      InstitutionGroup::query()->whereNotNull('banner'),
      'institution_groups.banner',
      'banner',
      fn(InstitutionGroup $group) => $group->banner,
      null,
      $mediaManager,
      $disk,
      $dryRun
    );

    $this->migrateModelColumn(
      AdmissionApplication::query()->whereNotNull('photo'),
      'admission_applications.photo',
      'admission_photo',
      fn(AdmissionApplication $application) => $application->photo,
      fn(AdmissionApplication $application) => $application->institution,
      $mediaManager,
      $disk,
      $dryRun
    );

    $this->migrateModelColumn(
      InstitutionSetting::query()->whereNotNull('value'),
      'institution_settings.value',
      'setting_photo',
      function (InstitutionSetting $setting) use ($mediaManager, $disk) {
        return $this->looksLikeMediaPath($setting->value, $mediaManager, $disk)
          ? $setting->value
          : null;
      },
      fn(InstitutionSetting $setting) => $setting->institution,
      $mediaManager,
      $disk,
      $dryRun
    );

    $this->migrateModelColumn(
      ManualPayment::query()
        ->whereNotNull('proof_path')
        ->orWhereNotNull('proof_url'),
      'manual_payments.proof_*',
      'payment_proof',
      fn(ManualPayment $payment) => $payment->proof_path ?: $payment->proof_url,
      fn(ManualPayment $payment) => $payment->institution,
      $mediaManager,
      $disk,
      $dryRun
    );

    if (!$this->option('skip-disk-scan')) {
      $this->scanDiskForOrphans($mediaManager, $disk, $dryRun);
    }

    $summaryLabel = $dryRun ? 'would be created' : 'created';
    $this->info(
      "Legacy media migration complete: {$this->createdCount} records {$summaryLabel}."
    );

    return self::SUCCESS;
  }

  private function migrateModelColumn(
    $query,
    string $label,
    string $collectionName,
    callable $valueResolver,
    ?callable $institutionResolver,
    MediaManager $mediaManager,
    string $disk,
    bool $dryRun
  ): void {
    $this->line("Processing {$label}...");

    $query->chunkById(100, function ($models) use (
      $collectionName,
      $disk,
      $dryRun,
      $institutionResolver,
      $mediaManager,
      $valueResolver
    ) {
      foreach ($models as $model) {
        $value = $valueResolver($model);
        $path = $this->resolvePath($value, $mediaManager, $disk);

        if (
          !$path ||
          $this->alreadyTracked($model, $collectionName, $disk, $path)
        ) {
          continue;
        }

        $institution = $institutionResolver
          ? $institutionResolver($model)
          : null;

        if ($dryRun) {
          $this->createdCount++;

          continue;
        }

        $mediaManager->registerExistingFile(
          $path,
          $model,
          $collectionName,
          $institution,
          null,
          $disk,
          meta: ['migration_source' => 'legacy_backfill']
        );

        $this->createdCount++;
      }
    });
  }

  private function scanDiskForOrphans(
    MediaManager $mediaManager,
    string $disk,
    bool $dryRun
  ): void {
    $this->line('Scanning disk for orphaned legacy files...');

    foreach (Storage::disk($disk)->allFiles() as $path) {
      if (
        Media::query()
          ->where('disk', $disk)
          ->where('path', $path)
          ->exists()
      ) {
        continue;
      }

      if ($dryRun) {
        $this->createdCount++;

        continue;
      }

      $mediaManager->registerExistingFile(
        $path,
        null,
        'legacy_unattached',
        null,
        null,
        $disk,
        meta: ['migration_source' => 'disk_scan']
      );

      $this->createdCount++;
    }
  }

  private function alreadyTracked(
    Model $model,
    string $collectionName,
    string $disk,
    string $path
  ): bool {
    return Media::query()
      ->where('disk', $disk)
      ->where('path', $path)
      ->where('collection_name', $collectionName)
      ->where('mediable_type', $model->getMorphClass())
      ->where('mediable_id', $model->getKey())
      ->exists();
  }

  private function resolvePath(
    mixed $value,
    MediaManager $mediaManager,
    string $disk
  ): ?string {
    if (!is_string($value) || trim($value) === '') {
      return null;
    }

    if (filter_var($value, FILTER_VALIDATE_URL)) {
      return $mediaManager->extractPathFromUrl($value, $disk);
    }

    return ltrim($value, '/');
  }

  private function looksLikeMediaPath(
    mixed $value,
    MediaManager $mediaManager,
    string $disk
  ): bool {
    $path = $this->resolvePath($value, $mediaManager, $disk);

    if (!$path) {
      return false;
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    return in_array(
      $extension,
      [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'pdf',
        'mp4',
        'mov',
        'avi',
        'mkv',
        'mp3',
        'wav'
      ],
      true
    );
  }
}
