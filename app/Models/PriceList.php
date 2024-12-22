<?php

namespace App\Models;

use App\Enums\PriceLists\PaymentStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceList extends Model
{
    use HasFactory;

    protected $table = 'price_lists';
    protected $guarded = [];

    protected $casts = [
        'institution_group_id' => 'integer',
        'payment_structure' => PaymentStructure::class,
        'amount' => 'float',
    ];

    public function institutionGroup()
    {
        return $this->belongsTo(InstitutionGroup::class);
    }
}
