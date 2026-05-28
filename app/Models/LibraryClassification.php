<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryClassification extends Model
{
    use HasFactory, InstitutionScope;

    protected $guarded = [];

    protected $casts = [
        'institution_id' => 'integer',
        'library_id' => 'integer',
        'classification_id' => 'integer',
    ];

    public function library()
    {
        return $this->belongsTo(Library::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}
