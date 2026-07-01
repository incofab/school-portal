<?php

namespace App\Services\Messaging\Whatsapp;

class PhoneNumberNormalizer
{
  public function normalize(?string $phone): ?string
  {
    $phone = $this->digits($phone);
    if (!$phone) {
      return null;
    }

    if (str_starts_with($phone, '00')) {
      $phone = substr($phone, 2);
    }

    if (str_starts_with($phone, '0') && strlen($phone) === 11) {
      return '234' . substr($phone, 1);
    }

    if (strlen($phone) === 10 && str_starts_with($phone, '8')) {
      return '234' . $phone;
    }

    return $phone;
  }

  /**
   * @return string[]
   */
  public function lookupVariants(?string $phone): array
  {
    $normalized = $this->normalize($phone);
    if (!$normalized) {
      return [];
    }

    $variants = [$normalized];

    if (str_starts_with($normalized, '234') && strlen($normalized) === 13) {
      $local = substr($normalized, 3);
      $variants[] = '0' . $local;
      $variants[] = $local;
    }

    return array_values(array_unique(array_filter($variants)));
  }

  private function digits(?string $phone): ?string
  {
    $digits = preg_replace('/\D+/', '', $phone ?? '');

    return filled($digits) ? $digits : null;
  }
}
