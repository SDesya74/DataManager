<?php

namespace SDesya74\DataManager;

use ArrayAccess;
use Closure;
use ErrorException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

/**
 * Mutable handle for a scope
 */
class MutableScopeHandle implements ArrayAccess
{
  protected Scope $scope;
  protected bool $public;

  public function __construct(Scope $scope, bool $public)
  {
    $this->scope = $scope;
    $this->public = $public;
  }

  /**
   * Get a readonly handle with same properties
   * @return ReadonlyScopeHandle
   */
  public function readonly(): ReadonlyScopeHandle
  {
    return new ReadonlyScopeHandle($this->scope, $this->public);
  }

  /**
   * Get a mutable entry handle
   * @param string $key Entry key
   * @return MutableEntryHandle
   */
  public function entry(string $key): MutableEntryHandle
  {
    $isNewEntry = !$this->scope->entryExists($key);
    $entry = $this->scope->entry($key);

    if ($isNewEntry) {
      $entry->setPublic($this->public);
    } else if (!$entry->isPublic() && $this->public) {
      $scopeName = $this->scope->name();
      $key = $entry->key();
      throw new ErrorException("Entry `$key` is private and accessed from public context of scope `$scopeName`");
    }

    return new MutableEntryHandle($this->scope, $entry->setPublic($this->public));
  }

  /**
   * Get associative array with all values of entries with current scope visibility
   * @return array
   */
  public function all(): array
  {
    return array_map(fn($e) => $e->get(), array_filter($this->scope->entries(), fn($e) => $e->isPublic() === $this->public));
  }

  /**
   * Get an entry handle that makes easier to work with Eloquent models with DataManager
   * 
   * @param string $key Entry key
   * @param mixed $modelClass Class of Eloquent model
   * @param mixed $modelId Primary key of needed model. It uses Model::findOrFail underneath so you can provide anything that can be provided to that method.
   * @return ModelEntryHandle 
   */
  public function model(string $key, $modelClass, $modelId)
  {
    $entry = $this->scope->entry($key)->setPublic($this->public);
    return new ModelEntryHandle($this->scope, $entry, $modelClass, $modelId);
  }

  /**
   * Subscribe on all mutations in this entry
   * 
   * Example:
   * ```php
   * $scope->subscribe(
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
      "dm." . $this->scope->name() . ".*",
      function (string $key, array $args) use ($callback) {
        [$_, $_, $entryKey] = explode(".", $key);

        $entry = $this->scope->entry($entryKey);
        $value = App::call($callback, ["scope" => $this->scope, "entry" => $entry, "previousValue" => $args[1]]);
        if (isset($value)) {
          $entry->set($value);
        }
      }
    );
  }

  /**
   * Whether an entry exists
   *
   * @param string $key A key to check for.
   * @return bool Returns `true` if entry exists or `false` elsewere.
   */
  public function offsetExists($key)
  {
    return $this->scope->entryExists($key);
  }

  /**
   * Returns the value at specified offset.
   *
   * @param string $name The offset to retrieve.
   * @return ReadonlyEntryHandle Can return all value types.
   */
  public function offsetGet($name)
  {
    return $this->entry($name);
  }

  /**
   * Assigns a value to the specified entry.
   *
   * @param string $key The key of entry to assign the value to.
   * @param mixed $value The value to set.
   * @return void
   */
  public function offsetSet($key, $value)
  {
    $this->entry($key)->set($value);
  }

  /**
   * Removes entry from scope. (Work only with mutable scopes)
   *
   * @param string $key A key of entry.
   * @return void
   */
  public function offsetUnset($key)
  {
    $this->scope->removeEntry($key);
  }
}