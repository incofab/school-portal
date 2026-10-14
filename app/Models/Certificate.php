<?php

namespace App\Models;

use App\Traits\InstitutionScope;

class Certificate extends BaseModel
{
    use InstitutionScope;

    protected $guarded = [];

    protected $casts = [
        'issued_on' => 'date',
        'revoked_at' => 'datetime',
        'render_data' => 'array',
    ];

    public function recipient()
    {
        return $this->belongsTo(InstitutionUser::class, 'institution_user_id');
    }

    public function certificateType()
    {
        return $this->belongsTo(
            CertificateType::class,
            'certificate_type_id'
        );
    }

    public function batch()
    {
        return $this->belongsTo(CertificateBatch::class, 'certificate_batch_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(
            InstitutionUser::class,
            'issued_by_institution_user_id'
        );
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function classification()
    {
        return $this->belongsTo(Classification::class);
    }
}
