<?php

namespace SDesya74\DataManager;

use Closure;
use ErrorException;
use Illuminate\Support\Facades\App;

class Entry
{
  private string $key;
  private mixed $value;
  private bool $public;
  public Closure $initializer;

  public function key()
  {
    return $this->key;
  }

  public function init(mixed $initializer = null)
  {
    $defaultValue = $initializer instanceof Closure ? App::call($initializer) : $initializer;
    $value = App::call($this->initializer, ["entry" => $this, "defaultValue" => $defaultValue]);
    if (!is_null($value)) {
      $this->value = $value;
    }
  }

  public function initialized()
  {
    return isset($this->value);
  }

  public function get(mixed $initializer = null)
  {
    if (!isset($this->value)) {
      $this->init($initializer);

      // this disaster can happen when $initializer did not return anything and did not set value to entry in initializer
      if (!isset($this->value)) {
        $key = $this->key();
        throw new ErrorException("Value of entry `$key` is not initialized. Check its initializer, if it is function, it must call `\$entry->set(...)` or return some value.");
      }
    }

    return $this->value;
  }

  public function set(mixed $value)
  {
    $this->value = $value;
  }

  public function isPublic()
  {
    return $this->public;
  }

  public function setPublic(bool $public = true)
  {
    $this->public = $public;
    return $this;
  }

  public function __construct(string $key)
  {
    $this->key = $key;
    $this->public = false;
    $this->initializer = function ($defaultValue) {
      return $defaultValue;
    };
  }
}