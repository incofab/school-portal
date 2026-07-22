<?php

namespace App\Models;

use App\Enums\LibrarySourceType;
use App\Enums\TermType;
use App\Support\Queries\LibraryQueryBuilder;
use App\Traits\HasMedia;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Library extends BaseModel
{
  use HasFactory, HasMedia, InstitutionScope;

  protected $guarded = [];

  protected $appends = ['access_url', 'source_label', 'file_size_label'];

  protected $casts = [
    'institution_id' => 'integer',
    'institution_user_id' => 'integer',
    'academic_session_id' => 'integer',
    'course_id' => 'integer',
    'is_public' => 'boolean',
    'is_published' => 'boolean',
    'file_size' => 'integer',
    'source_type' => LibrarySourceType::class,
    'term' => TermType::class,
    'published_at' => 'datetime'
  ];

  public static function query(): LibraryQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new LibraryQueryBuilder($query);
  }

  public function getAccessUrlAttribute(): ?string
  {
    if ($this->source_type === LibrarySourceType::External) {
      return $this->external_url;
    }

    return $this->file_url ?: $this->latestMediaForCollection('resource')?->url;
  }

  public function getSourceLabelAttribute(): string
  {
    return $this->source_type === LibrarySourceType::External
      ? 'External link'
      : 'Uploaded file';
  }

  public function getFileSizeLabelAttribute(): ?string
  {
    if (!$this->file_size) {
      return null;
    }

    if ($this->file_size >= 1048576) {
      return round($this->file_size / 1048576, 2) . ' MB';
    }

    return round($this->file_size / 1024, 1) . ' KB';
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function libraryClassifications()
  {
    return $this->hasMany(LibraryClassification::class);
  }

  public function classifications()
  {
    return $this->belongsToMany(
      Classification::class,
      'library_classifications'
    )->withTimestamps();
  }
}
