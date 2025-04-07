<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignmentClassification extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'institution_id' => 'integer',
        'assignment_id' => 'integer',
        'classification_id' => 'integer'
    ];

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
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