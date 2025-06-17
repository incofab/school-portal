<?php
namespace App\Actions;

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\Institution;
use App\Models\PriceList;

class SeedSetupData
{
  function __construct(private Institution $institution)
  {
  }

  public static function run(Institution $institution)
  {
    $obj = new self($institution);
    $obj->seedAssessment();
    $obj->seedPriceList();
  }

  static function seedAllInstitutions()
  {
    $institutions = Institution::query()
      ->whereHas('classifications')
      ->get();
    foreach ($institutions as $institution) {
      self::run($institution);
    }
  }

  private function seedAssessment()
  {
    if (
      $this->institution
        ->assessments()
        ->get()
        ->count() > 0
    ) {
      return;
    }
    $this->institution
      ->assessments()
      ->firstOrCreate(['title' => 'first_assessment'], ['max' => 20]);
    $this->institution
      ->assessments()
      ->firstOrCreate(['title' => 'second_assessment'], ['max' => 20]);
  }

  private function seedPriceList()
  {
    $priceLists = [
      // [
      //   'type' => PriceType::EmailSending->value,
      //   'payment_structure' => PaymentStructure::PerUnit->value,
      //   'amount' => 3
      // ],
      // [
      //   'type' => PriceType::SmsSending->value,
      //   'payment_structure' => PaymentStructure::PerUnit->value,
      //   'amount' => 7
      // ],
      [
        'type' => PriceType::ResultChecking->value,
        'payment_structure' => PaymentStructure::PerStudentPerTerm->value,
        'amount' => 400
      ]
    ];

    $institutionGroup = $this->institution->institutionGroup;
    foreach ($priceLists as $key => $priceList) {
      PriceList::query()->firstOrCreate(
        [
          'type' => $priceList['type'],
          'institution_group_id' => $institutionGroup->id
        ],
        [
          'payment_structure' => $priceList['payment_structure'],
          'amount' => $priceList['amount']
        ]
      );
    }
  }
}
