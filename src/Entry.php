<?php

namespace SDesya74\DataManager;

use Closure;
use ErrorException;
use Illuminate\Support\Facades\App;

/**
 * Internal Entry, not meant to be used anywhere except callbacks of `subscribe` methods and in handles
 */
class Entry
{
  private string $key;
  private mixed $value;
  private bool $public;
  public Closure $initializer;

  public function __construct(string $key)
  {
    $this->key = $key;
    $this->public = false;
    $this->initializer = function ($defaultValue) {
      return $defaultValue;
    };
  }

  /**
   * Get entry key
   * @return string
   */
  public function key()
  {
    return $this->key;
  }

  /**
   * Call initializer of the entry that sets a value to it
   * @param mixed|null $initializer
   * @return void
   */
  public function init(mixed $initializer = null)
  {
    $defaultValue = $initializer instanceof Closure ? App::call($initializer) : $initializer;
    $value = App::call($this->initializer, ["entry" => $this, "defaultValue" => $defaultValue]);
    if (!is_null($value)) {
      $this->value = $value;
    }
  }

  /**
   * Returns true if entry initialized, false otherwise
   * @return bool
   */
  public function initialized()
  {
    return isset($this->value);
  }

  /**
   * Get a value stored inside the entry. This method calls entry initializer if entry is not initialized.
   * @param mixed|null $initializer A function that returns value or a value inself.
   * @throws ErrorException When entry is not initialized after initializer call
   * @return mixed Value stored inside entry
   */
  public function get(mixed $initializer = null)
  {
    if (!isset($this->value)) {
      $this->init($initializer);

      // this disaster can happen when $initializer did not return anything and did not set value to entry in initializer
      if (!isset($this->value)) {
        $key = $this->key();
        throw new ErrorException("Value of entry `$key` is not initialized. Check its initializer, if it is a function, it must call `\$entry->set(...)` or return some value.");
      }
    }

    return $this->value;
  }

  /**
   * Set entry value
   * @param mixed $value Value to store
   * @return void
   */
  public function set(mixed $value)
  {
    $this->value = $value;
  }

  /**
   * Returns `true` if entry is public and `false` otherwise
   * @return bool
   */
  public function isPublic()
  {
    return $this->public;
  }

  /**
   * Set entry public or private
   * @param bool $public If true then entry will be public
   * @return Entry
   */
  public function setPublic(bool $public = true)
  {
    $this->public = $public;
    return $this;
  }

}