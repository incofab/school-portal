<?php
namespace App\DTO;

use App\Models\User;

class TokenUser
{
  public string|null $user_id = null;
  public string $reference = '';
  public string $email = '';
  public string $phone = '';
  public string $name = '';

  function __construct()
  {
  }

  static function createFromData(array $data): self
  {
    $token = new self();
    $token->setData($data);
    return $token;
  }

  static function createFromUser(User $user): self
  {
    $token = new self();
    $token->user_id = $user->id;
    $token->email = $user->email;
    $token->phone = $user->phone;
    $token->name = $user->full_name;
    return $token;
  }
  function getUserId(): string
  {
    return $this->user_id;
  }
  function setData(array $data)
  {
    foreach ($data as $key => $value) {
      $this->{$key} = $value;
    }
    return $this;
  }
  function getReference(): string
  {
    return $this->reference;
  }
  function getEmail(): string
  {
    return $this->email;
  }
  function getPhone(): string
  {
    return $this->phone;
  }
  function getName(): string
  {
    return $this->name;
  }
}
