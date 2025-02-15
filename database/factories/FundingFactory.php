<?php

namespace Database\Factories;

use App\Models\Funding;
use App\Models\InstitutionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class FundingFactory extends Factory
{
    protected $model = Funding::class;

    public function definition()
    {
        $amount = $this->faker->randomFloat(2, 1000, 10000);
        $prevBal = $this->faker->randomFloat(2, 0, 5000);
        $newBal = $amount + $prevBal;

        // Create a new institution group for the funding record
        $institutionGroup = InstitutionGroup::factory()->create();

        // Update the wallet_balance in the institution_groups table
        $institutionGroup->wallet_balance += $newBal;
        $institutionGroup->save();

        return [
            'institution_group_id' => $institutionGroup->id, // Set the foreign key
            'amount' => $amount,
            'previous_balance' => $prevBal,
            'new_balance' => $newBal,
            'remark' => $this->faker->sentence(),
            'reference' => strtoupper($this->faker->unique()->lexify('FUND-????-????')),
        ];
    }
}
