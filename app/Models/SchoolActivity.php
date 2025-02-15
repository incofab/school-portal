<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'school_activities';
    public $guarded = [];
    protected $casts = [
        'institution_id' => 'integer',
    ];

    function institution()
    {
        return $this->belongsTo(Institution::class);
    }
}