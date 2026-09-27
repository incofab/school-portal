<?php

namespace App\Console\Commands\OneTime;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ConvertCourseTeacherIdsToTeacherUserIds extends Command
{
  protected $signature = 'data:convert-course-teacher-ids
    {--table=* : Limit the conversion to one or more table names}
    {--dry-run : Inspect and report without changing data or schema}
    {--force : Run without the confirmation prompt}';

  protected $description =
    'Convert legacy course_teacher_id columns to teacher_user_id columns';

  private array $summary = [
    'tables' => 0,
    'converted' => 0,
    'unresolved' => 0,
    'conflicts' => 0,
    'skipped' => 0
  ];

  public function handle(): int
  {
    $tables = $this->affectedTables();
    $requestedTables = collect($this->option('table'))
      ->filter()
      ->values();

    if ($requestedTables->isNotEmpty()) {
      $unknownTables = $requestedTables->diff($tables);
      if ($unknownTables->isNotEmpty()) {
        $this->warn(
          'These requested tables do not currently contain course_teacher_id: ' .
            $unknownTables->implode(', ')
        );
      }
      $tables = $tables->intersect($requestedTables)->values();
    }

    if ($tables->isEmpty()) {
      $this->info(
        'No database columns named course_teacher_id were found. Nothing to convert.'
      );
      return self::SUCCESS;
    }

    $this->info('Affected tables: ' . $tables->implode(', '));

    if (!$this->option('dry-run') && !$this->option('force')) {
      if (!$this->confirm('Proceed with the schema and data conversion?')) {
        $this->warn('Conversion cancelled. No changes were made.');
        return self::SUCCESS;
      }
    }

    foreach ($tables as $table) {
      $this->summary['tables']++;
      $this->processTable($table);
    }

    $this->newLine();
    $this->line('<info>Overall conversion summary</info>');
    $this->line('Tables processed: ' . $this->summary['tables']);
    $this->line('Rows converted or already valid: ' . $this->summary['converted']);
    $this->line('Unresolved rows: ' . $this->summary['unresolved']);
    $this->line('Conflicting rows: ' . $this->summary['conflicts']);
    $this->line('Tables left unchanged for safety: ' . $this->summary['skipped']);

    return $this->summary['unresolved'] || $this->summary['conflicts']
      ? self::FAILURE
      : self::SUCCESS;
  }

  private function processTable(string $table): void
  {
    $this->newLine();
    $this->line("<comment>Processing table: {$table}</comment>");

    $primaryKey = $this->primaryKey($table);
    if (!$primaryKey) {
      $this->error("{$table}: no single-column primary key was found; skipping.");
      $this->summary['skipped']++;
      return;
    }

    $columns = collect(Schema::getColumnListing($table));
    $hasReplacement = $columns->contains('teacher_user_id');
    if ($hasReplacement &&
      $this->columnType($table, 'teacher_user_id') !==
        $this->columnType('users', 'id')) {
      $this->error(
        "{$table}: teacher_user_id has a different type from users.id; skipping for safety."
      );
      $this->summary['skipped']++;
      return;
    }
    $legacyRows = DB::table($table)
      ->whereNotNull('course_teacher_id')
      ->count();

    $this->line("Rows containing course_teacher_id: {$legacyRows}");

    if (!$hasReplacement) {
      if ($this->option('dry-run')) {
        $this->line(
          "Would add nullable teacher_user_id to {$table} using the users primary-key type."
        );
      } else {
        $this->line("Adding nullable teacher_user_id to {$table}...");
        $this->addReplacementColumn($table);
      }
    } else {
      $this->line("{$table} already has teacher_user_id; reusing it safely.");
    }

    if ($this->option('dry-run')) {
      $this->reportDryRun($table, $legacyRows);
      return;
    }

    $result = $this->populateReplacementColumn($table, $primaryKey);

    if ($result['unresolved'] || $result['conflicts']) {
      $this->warn(
        "{$table}: legacy column retained because not every row was validated."
      );
      $this->summary['skipped']++;
      return;
    }

    if (!$this->validateReplacementValues($table)) {
      $this->warn(
        "{$table}: teacher_user_id contains invalid user references; legacy column retained."
      );
      $this->summary['skipped']++;
      return;
    }

    try {
      $this->line("{$table}: adding teacher_user_id index and foreign key...");
      $this->addReplacementConstraints($table);
      $this->line("{$table}: removing legacy foreign keys and indexes...");
      $this->dropLegacyConstraints($table);
      $this->line("{$table}: dropping course_teacher_id...");
      Schema::table($table, fn(Blueprint $blueprint) =>
        $blueprint->dropColumn('course_teacher_id')
      );
      $this->info("{$table}: conversion complete.");
    } catch (Throwable $exception) {
      $this->error("{$table}: schema cleanup failed: {$exception->getMessage()}");
      $this->summary['skipped']++;
    }
  }

  private function populateReplacementColumn(string $table, string $primaryKey): array
  {
    $ids = DB::table($table)
      ->whereNotNull('course_teacher_id')
      ->distinct()
      ->pluck('course_teacher_id');

    $teachers = $this->courseTeacherMap($ids);
    $hasInstitutionId = Schema::hasColumn($table, 'institution_id');
    $rowCount = DB::table($table)
      ->whereNotNull('course_teacher_id')
      ->count();
    $select = [$primaryKey, 'course_teacher_id', 'teacher_user_id'];
    if ($hasInstitutionId) {
      $select[] = 'institution_id';
    }
    $rows = DB::table($table)
      ->select($select)
      ->whereNotNull('course_teacher_id')
      ->orderBy($primaryKey)
      ->cursor();

    $this->line("{$table}: validating {$rowCount} legacy rows...");
    $progress = $this->output->createProgressBar($rowCount);
    $progress->start();
    $converted = 0;
    $unresolved = 0;
    $conflicts = 0;

    foreach ($rows as $row) {
      $teacher = $teachers->get((int) $row->course_teacher_id);
      $reason = $this->resolutionFailure($teacher, $row, $hasInstitutionId);

      if ($reason) {
        $unresolved++;
        $this->summary['unresolved']++;
        $this->warn(
          "{$table}#{$row->{$primaryKey}}: {$reason}; teacher_user_id left unchanged."
        );
        $progress->advance();
        continue;
      }

      $resolvedUserId = (int) $teacher->user_id;
      if ($row->teacher_user_id !== null &&
        (int) $row->teacher_user_id !== $resolvedUserId) {
        $conflicts++;
        $this->summary['conflicts']++;
        $this->warn(
          "{$table}#{$row->{$primaryKey}}: teacher_user_id {$row->teacher_user_id} conflicts with resolved user {$resolvedUserId}; row skipped."
        );
        $progress->advance();
        continue;
      }

      if ($row->teacher_user_id === null) {
        DB::table($table)
          ->where($primaryKey, $row->{$primaryKey})
          ->update(['teacher_user_id' => $resolvedUserId]);
      }

      $converted++;
      $this->summary['converted']++;
      $progress->advance();
    }

    $progress->finish();
    $this->newLine();
    $this->line(
      "{$table}: {$converted} converted/validated, {$unresolved} unresolved, {$conflicts} conflicts."
    );

    return compact('unresolved', 'conflicts');
  }

  private function courseTeacherMap(Collection $ids): Collection
  {
    if ($ids->isEmpty()) {
      return collect();
    }

    return DB::table('course_teachers')
      ->leftJoin('users', 'users.id', '=', 'course_teachers.user_id')
      ->whereIn('course_teachers.id', $ids->all())
      ->get([
        'course_teachers.id',
        'course_teachers.user_id',
        'course_teachers.institution_id',
        'users.id as resolved_user_id'
      ])
      ->keyBy('id');
  }

  private function resolutionFailure(
    ?object $teacher,
    object $row,
    bool $hasInstitutionId
  ): ?string {
    if (!$teacher) {
      return 'related course teacher was not found';
    }

    if (!$teacher->resolved_user_id) {
      return 'course teacher user was not found';
    }

    if ($hasInstitutionId &&
      isset($row->institution_id) &&
      $row->institution_id !== null &&
      $teacher->institution_id !== null &&
      (int) $row->institution_id !== (int) $teacher->institution_id) {
      return 'course teacher belongs to a different institution';
    }

    return null;
  }

  private function validateReplacementValues(string $table): bool
  {
    $invalid = DB::table($table)
      ->leftJoin('users', 'users.id', '=', $table . '.teacher_user_id')
      ->whereNotNull($table . '.teacher_user_id')
      ->whereNull('users.id')
      ->count();

    if ($invalid) {
      $this->error("{$table}: {$invalid} teacher_user_id values do not reference users.");
      $this->summary['unresolved'] += $invalid;
      return false;
    }

    return true;
  }

  private function addReplacementColumn(string $table): void
  {
    $userType = $this->columnType('users', 'id');
    Schema::table($table, function (Blueprint $blueprint) use ($userType) {
      $column = match (true) {
        str_contains($userType, 'bigint') => $blueprint->unsignedBigInteger('teacher_user_id'),
        str_contains($userType, 'int') => $blueprint->unsignedInteger('teacher_user_id'),
        default => throw new \RuntimeException(
          "Unsupported users.id type: {$userType}"
        )
      };
      $column->nullable()->after('course_teacher_id');
    });
  }

  private function addReplacementConstraints(string $table): void
  {
    $indexes = $this->indexesForColumn($table, 'teacher_user_id');
    if ($indexes->isEmpty()) {
      Schema::table($table, fn(Blueprint $blueprint) =>
        $blueprint->index('teacher_user_id')
      );
    }

    if (!$this->hasUserForeignKey($table, 'teacher_user_id')) {
      Schema::table($table, fn(Blueprint $blueprint) =>
        $blueprint
          ->foreign('teacher_user_id')
          ->references('id')
          ->on('users')
          ->nullOnDelete()
      );
    }
  }

  private function dropLegacyConstraints(string $table): void
  {
    foreach ($this->foreignKeysForColumn($table, 'course_teacher_id') as $name) {
      Schema::table($table, fn(Blueprint $blueprint) =>
        $blueprint->dropForeign($name)
      );
    }

    foreach ($this->indexesForColumn($table, 'course_teacher_id') as $name) {
      if ($name === 'PRIMARY') {
        continue;
      }
      Schema::table($table, fn(Blueprint $blueprint) =>
        $blueprint->dropIndex($name)
      );
    }
  }

  private function affectedTables(): Collection
  {
    return collect(Schema::getTables())
      ->map(fn($table) => is_array($table) ? ($table['name'] ?? null) : $table->name)
      ->filter()
      ->filter(fn(string $table) => Schema::hasColumn($table, 'course_teacher_id'))
      ->unique()
      ->values();
  }

  private function primaryKey(string $table): ?string
  {
    $primaryColumns = DB::select(
      'select column_name as column_name from information_schema.key_column_usage where table_schema = database() and table_name = ? and constraint_name = \'PRIMARY\' order by ordinal_position',
      [$table]
    );

    return count($primaryColumns) === 1 ? $primaryColumns[0]->column_name : null;
  }

  private function columnType(string $table, string $column): string
  {
    $result = DB::selectOne(
      'select column_type as column_type from information_schema.columns where table_schema = database() and table_name = ? and column_name = ?',
      [$table, $column]
    );

    return strtolower($result->column_type ?? '');
  }

  private function indexesForColumn(string $table, string $column): Collection
  {
    return collect(DB::select(
      'select distinct index_name as index_name from information_schema.statistics where table_schema = database() and table_name = ? and column_name = ?',
      [$table, $column]
    ))->pluck('index_name');
  }

  private function foreignKeysForColumn(string $table, string $column): Collection
  {
    return collect(DB::select(
      'select distinct constraint_name as constraint_name from information_schema.key_column_usage where table_schema = database() and table_name = ? and column_name = ? and referenced_table_name is not null',
      [$table, $column]
    ))->pluck('constraint_name');
  }

  private function hasUserForeignKey(string $table, string $column): bool
  {
    return (bool) DB::selectOne(
      'select constraint_name from information_schema.key_column_usage where table_schema = database() and table_name = ? and column_name = ? and referenced_table_name = \'users\' and referenced_column_name = \'id\' limit 1',
      [$table, $column]
    );
  }

  private function reportDryRun(string $table, int $legacyRows): void
  {
    $this->line(
      "{$table}: dry-run would validate {$legacyRows} rows, add users FK/index, then drop the legacy column only if all rows resolve cleanly."
    );
  }
}
