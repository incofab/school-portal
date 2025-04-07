<?php
namespace App\DTO;

class PaymentKeyDto
{
  function __construct(private ?string $publicKey, private ?string $privateKey)
  {
  }

  function getPrivateKey()
  {
    return $this->privateKey;
  }

  function getPublicKey()
  {
    return $this->publicKey;
  }
}
