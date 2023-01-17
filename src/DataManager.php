<?php

namespace SDesya74\DataManager;

use ArrayAccess;
use Closure;
use ErrorException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

/**
 * A storage for merging per-request data together and return it to client side
 * 
 * Usage:
 * ```php
 * // in boot section
 * $dm = $this->app->make(DataManager::class); // uses Laravel's app
 * $dm->registerScope("tasks");
 * 
 * // in some controller
 * $dm = $this->app->make(DataManager::class);
 * $scope = $dm->scope("tasks")->public()->mutable();
 * $scope->entry("task1")->set("Do chores"); // set value to entry
 * ```
 * 
 * DataManager also implements useful ```subscribe``` method that can be used to listen to mutation in scopes and entries.
 * 
 * Example of subscription:
 * ```php
 * $dm->subscribe(
 *    function (Scope $scope, Entry $entry, $previousValue) {
 *      $key = $entry->key();
 *      $scopeName = $scope->name();
 *      $value = $entry->get();
 *      Log::debug("Entry `$key` in scope `$scopeName` mutated with value `$value`");
 * 
 *      // you can also set value to entry with $entry->set(), but it will not dispatch event, so no recursion fear
 *    }
 *  );
 * ```
 */
class DataManager implements ArrayAccess
{
  private const GLOBAL_SCOPE = "__GLOBAL";
  private array $scopes = [];

  /**
   * Create a new DataManager instance with default global scope.
   * 
   * This is not mean to be called manually unless you don't use a DataManagerServiceProvider.
   */
  public function __construct()
  {
    $this->registerScope(self::GLOBAL_SCOPE);
  }

  /**
   * Return a global scope.
   * 
   * This scope is created by default and exists just for convinience.
   * 
   * @return ScopeHandleBuilder A builder for global scope handle.
   */
  public function global ()
  {
    return $this->scope(self::GLOBAL_SCOPE);
  }

  /**
   * Subscribe on all mutations in all scopes
   * 
   * Example:
   * ```php
   * $dm->subscribe(
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
      "dm.*",
      function (string $key, array $args) use ($callback) {
        [$_, $scopeName, $entryKey] = explode(".", $key);

        $scope = $this->scopes[$scopeName];
        $entry = $scope->entry($entryKey);
        $value = App::call($callback, ["scope" => $scope, "entry" => $entry, "previousValue" => $args[1]]);
        if (isset($value)) {
          $entry->set($value);
        }
      }
    );
  }

  /**
   * Register a new scope
   * 
   * @param string $name Name of a new scope
   * @throws ErrorException When scope is already registered
   * @return void
   */
  public function registerScope(string $name)
  {
    if (key_exists($name, $this->scopes)) {
      throw new ErrorException("Scope `$name` is already registered");
    }
    $this->scopes[$name] = new Scope($name);
  }

  /**
   * Return a ScopeBuilder that builds a handle for scope
   * 
   * Scope must be registered before first access, to register scope use `$dm->registerScope($name)` in boot
   * 
   * @param string $name A name of scope
   * @throws ErrorException When scope is not registered
   * @return ScopeHandleBuilder
   */
  public function scope(string $name): ScopeHandleBuilder
  {
    if (!key_exists($name, $this->scopes)) {
      throw new ErrorException("There is no scope `$name`. To register new scope use `\$dm->registerScope(\"$name\");` in boot section");
    }
    return new ScopeHandleBuilder($this->scopes[$name], false);
  }

  /**
   * Return values of all public entries
   *
   * @return array
   */
  public function allPublic(): array
  {
    $output = [];
    foreach ($this->scopes as $scope) {
      foreach ($scope->entries() as $entry) {
        if ($entry->isPublic()) {
          $output[$entry->key()] = $entry->get();
        }
      }
    }
    return $output;
  }

  /**
   * Whether or not a scope exists.
   *
   * @param string $name A scope name to check for.
   * @return bool Returns `true` if a scope exists or `false` is it's not.
   */
  public function offsetExists($name)
  {
    return key_exists($name, $this->scopes);
  }

  /**
   * Return private readonly scope with specified name.
   *
   * @param mixed $name Name of a scope.
   * @return ReadonlyScopeHandle **PRIVATE READONLY** scope handle.
   */
  public function offsetGet($name)
  {
    return new ReadonlyScopeHandle($this->scopes[$name], false);
  }

  /**
   * Not working, implemented due to ArrayAccess interface.
   * 
   * To create new scope, use
   *
   *  ```php
   * $dm->registerScope("scopeName");
   * ```
   * @param string $name The name of a scope.
   * @param string $value Some value.
   * @return void
   */
  public function offsetSet($name, $value)
  {
    throw new ErrorException("Cannot overwrite scope `$name`.");
  }

  /**
   * Not working, implemented due to ArrayAccess interface.
   *
   * @param string $name The name of a scope.
   * @return void
   */
  public function offsetUnset($name)
  {
    throw new ErrorException("Cannot unset scope `$name`.");
  }
}