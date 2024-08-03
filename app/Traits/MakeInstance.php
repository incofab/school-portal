<?php
namespace App\Traits;

trait MakeInstance
{
  private static $instance;
  /** @return static */
  public static function make()
  {
    if (!self::$instance) {
      self::$instance = new static();
    }
    return self::$instance;
  }
}
