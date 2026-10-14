<?php

namespace App\Models;

use App\Traits\InstitutionScope;

class CertificateType extends BaseModel
{
    use InstitutionScope;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'custom_values' => 'array'];

    public function template()
    {
        return $this->belongsTo(
            CertificateTemplate::class,
            'certificate_template_id'
        );
    }

    public function certificates()
    {
        return $this->hasMany(
            Certificate::class,
            'certificate_type_id'
        );
    }

    public function batches()
    {
        return $this->hasMany(CertificateBatch::class, 'certificate_type_id');
    }
}
