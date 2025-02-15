<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableCoordinator extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'timetable_coordinators';
    public $guarded = [];
    protected $casts = [
        'timetable_id' => 'integer',
        'institution_user_id' => 'integer',
    ];

    function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    function institutionUser()
    {
        return $this->belongsTo(InstitutionUser::class);
    }
}