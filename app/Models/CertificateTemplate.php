<?php

namespace App\Models;

class CertificateTemplate extends BaseModel
{
    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean', 'placeholders' => 'array'];

    public function certificateTypes()
    {
        return $this->hasMany(CertificateType::class);
    }
}
