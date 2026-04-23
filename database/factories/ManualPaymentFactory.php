<?php

namespace Database\Factories;

use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\Institution;
use App\Models\ManualPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class ManualPaymentFactory extends Factory
{
    protected $model = ManualPayment::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'reference' => ManualPayment::generateReference(),
            'amount' => fake()->numberBetween(1000, 10000),
            'purpose' => PaymentPurpose::Fee->value,
            'method' => PaymentMethod::Bank->value,
            'status' => PaymentStatus::Pending->value,
            'depositor_name' => fake()->name(),
            'meta' => [],
        ];
    }

    public function institution(Institution $institution)
    {
        return $this->state(fn () => ['institution_id' => $institution->id]);
    }

    public function payable(Model $model)
    {
        return $this->state(
            fn () => [
                'payable_type' => $model->getMorphClass(),
                'payable_id' => $model->id,
            ]
        );
    }

    public function paymentable(Model $model)
    {
        return $this->state(
            fn () => [
                'paymentable_type' => $model->getMorphClass(),
                'paymentable_id' => $model->id,
            ]
        );
    }
}
