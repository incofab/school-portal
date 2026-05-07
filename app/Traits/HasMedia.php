<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->latest('id');
    }

    public function latestMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->latestOfMany('id');
    }

    public function latestMediaForCollection(string $collectionName): ?Media
    {
        return $this
            ->media()
            ->where('collection_name', $collectionName)
            ->first();
    }
}
