<?php

namespace SDesya74\DataManager;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

/**
 * Readonly handle for an entry
 */
class ReadonlyEntryHandle
{
  protected Entry $entry;
  protected Scope $scope;

  public function __construct(Scope $scope, Entry $entry)
  {
    $this->scope = $scope;
    $this->entry = $entry;
    $this->entry->initializer = function (Entry $entry, mixed $defaultValue) {
      $entry->set($defaultValue);
      Event::dispatch($this->fullPath(), [$entry, "previousValue" => null]);
    };
  }

  /**
   * Get the value stored in the entry
   * 
   * If value is not initialized, entry will call initializer before returning the value.
   * @param mixed|null $initializer Initializer that can be function that returns a value or value itself
   * @return mixed
   */
  public function get(mixed $initializer = null)
  {
    return $this->entry->get($initializer);
  }

  /**
   * Get full path of entry.
   * 
   * Path looks like this: dm.\<scope\>.\<key\>, where \<scope\> is the name of scope and \<key\> is entry key.
   * @return string
   */
  protected function fullPath()
  {
    return implode(".", ["dm", $this->scope->name(), $this->entry->key()]);
  }

  /**
   * Subscribe on all mutations in this particular entry
   * 
   * Example:
   * ```php
   * $entry->subscribe(
   *   function (Scope $scope, Entry $entry, $previousValue) {
   *     // check if this is initialization
   *     $action = is_null($previousValue) ? 'INITIALIZED' : 'MUTATED';
   * 
   *     // you can set value like this
   *     $entry->set(42);
   * 
   *     // or like this
   *     return 42;
   *   }
   * );
   * ```
   * 
   * @param Closure $callback A function with (Scope $scope, Entry $entry, $previousValue) args
   * @return void
   */
  function subscribe(Closure $callback)
  {
    Event::listen(
      $this->fullPath(),
      function (Entry $entry, $previousValue) use ($callback) {
        $value = App::call($callback, ["scope"->$this->scope, "entry" => $entry, "previousValue" => $previousValue]);
        if (isset($value)) {
          $entry->set($value);
        }
      }
    );
  }

  /**
   * Whether entry is public or not
   * @return bool
   */
  public function isPublic()
  {
    return $this->entry->public;
  }

  /**
   * Set entry public or private
   * @param bool $public If true then entry will be public
   * @return ReadonlyEntryHandle
   */
  public function setPublic(bool $public = true)
  {
    $this->entry->setPublic($public);
    return $this;
  }
}