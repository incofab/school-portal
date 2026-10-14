<?php

namespace App\Models;

use App\Traits\InstitutionScope;

class CertificateBatch extends BaseModel
{
    use InstitutionScope;

    protected $guarded = [];

    protected $casts = ['issued_on' => 'date'];

    public function certificateType()
    {
        return $this->belongsTo(
            CertificateType::class,
            'certificate_type_id'
        );
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class, 'certificate_batch_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(
            InstitutionUser::class,
            'created_by_institution_user_id'
        );
    }
}
