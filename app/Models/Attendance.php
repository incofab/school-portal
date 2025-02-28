<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes, InstitutionScope;

    protected $guarded = [];
    protected $casts = [
        'institution_id' => 'integer',
        'institution_user_id' => 'integer',
        'institution_staff_user_id' => 'integer',
        'signed_in_at' => 'datetime',
        'signed_out_at' => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }
    
    public function institutionUser()
    {
        return $this->belongsTo(InstitutionUser::class, 'institution_user_id');
    }

    public function staffUser()
    {
        return $this->belongsTo(InstitutionUser::class, 'institution_staff_user_id');
    }
}
