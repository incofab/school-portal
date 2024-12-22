<?php

namespace Database\Seeders;

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\PriceList;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PriceListSeeder extends Seeder
{
    public function run(): void
    {
        PriceList::create([
            'type' => PriceType::ResultChecking->value,
            'institution_group_id' => 29,
            'payment_structure' => PaymentStructure::PerStudentPerTerm->value,
            'amount' => 500,
        ]);
    }
}