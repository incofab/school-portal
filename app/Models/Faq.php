<?php

namespace App\Models;

use App\Enums\FaqType;
use App\Support\Faq\FaqContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Faq extends BaseModel
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'type' => FaqType::class,
    'is_active' => 'boolean',
    'sort_order' => 'integer'
  ];

  protected $appends = [
    'description_html',
    'youtube_video_id',
    'youtube_embed_url'
  ];

  public function scopeActive(Builder $query): Builder
  {
    return $query->where('is_active', true);
  }

  public function scopeFaqs(Builder $query): Builder
  {
    return $query->where('type', FaqType::Faq->value);
  }

  public function scopeKnowledgeBase(Builder $query): Builder
  {
    return $query->where('type', FaqType::KnowledgeBase->value);
  }

  public function scopeOrdered(Builder $query): Builder
  {
    return $query
      ->orderByRaw('sort_order is null')
      ->orderBy('sort_order')
      ->orderBy('name');
  }

  protected function description(): Attribute
  {
    return Attribute::set(fn(?string $value) => FaqContent::cleanHtml($value));
  }

  public function getDescriptionHtmlAttribute(): string
  {
    return FaqContent::cleanHtml($this->description);
  }

  public function getYoutubeVideoIdAttribute(): ?string
  {
    return FaqContent::youtubeVideoId($this->video_url);
  }

  public function getYoutubeEmbedUrlAttribute(): ?string
  {
    return FaqContent::youtubeEmbedUrl($this->video_url);
  }
}
