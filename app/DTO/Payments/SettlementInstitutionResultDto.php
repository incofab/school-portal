<?php

namespace App\DTO\Payments;

class SettlementInstitutionResultDto
{
  public function __construct(public bool $settled, public float $amount = 0.0)
  {
  }
}
