<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationGuardian extends Model
{
    use HasFactory;

    public $guarded = [];
    protected $table = 'application_guardians';
    protected $casts = [
        'admission_application_id' => 'integer'
    ];

    // Define the relationship with AdmissionApplication
    public function admissionApplication()
    {
        return $this->belongsTo(AdmissionApplication::class, 'admission_application_id');
    }
}
