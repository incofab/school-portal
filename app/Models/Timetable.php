<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Timetable extends Model
{
    use HasFactory, SoftDeletes, InstitutionScope;

    protected $table = 'timetables';
    public $guarded = [];
    protected $casts = [
        'institution_id' => 'integer',
        'classification_id' => 'integer',
        'day' => 'integer',
    ];

    function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    function classification()
    {
        return $this->belongsTo(Classification::class);
    }

    function timetableCoordinators()
    {
        return $this->hasMany(TimetableCoordinator::class);
    }

    // Course | SchoolActivity
    function actionable()
    {
        return $this->morphTo();
    }

    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::createFromFormat("H:i:s", $value)->format("H:i")
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::createFromFormat("H:i:s", $value)->format("H:i")
        );
    }
}