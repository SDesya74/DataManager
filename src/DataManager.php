<?php

namespace SDesya74\DataManager;

use ArrayAccess;
use Closure;
use ErrorException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

class DataManager implements ArrayAccess
{
  private const GLOBAL_SCOPE = "__GLOBAL";
  private array $scopes = [];

  public function __construct()
  {
    $this->registerScope(self::GLOBAL_SCOPE);
  }

  public function global ()
  {
    return $this->scope(self::GLOBAL_SCOPE);
  }

  function subscribe(Closure $callback)
  {
    Event::listen(
      "dm.*",
      function (string $key, array $args) use ($callback) {
        [$_, $scopeName, $entryKey] = explode(".", $key);

        $scope = $this->scopes[$scopeName];
        $entry = $scope->entry($entryKey);
        App::call($callback, ["scope" => $scope, "entry" => $entry, "previousValue" => $args[1]]);
      }
    );
  }

  public function registerScope(string $name)
  {
    if (key_exists($name, $this->scopes)) {
      throw new ErrorException("Scope `$name` is already registered");
    }
    $this->scopes[$name] = new Scope($name);
  }

  public function scope(string $name): ScopeBuilder
  {
    if (!key_exists($name, $this->scopes)) {
      throw new ErrorException("There is no scope `$name`. To register new scope use `\$dm->registerScope(\"$name\");` in boot section");
    }
    return new ScopeBuilder($this->scopes[$name], false);
  }

  public function allPublic()
  {
    $output = [];
    foreach ($this->scopes as $scope) {
      foreach ($scope->entries() as $entry) {
        if ($entry->isPublic()) {
          $output[$entry->key()] = $entry->get();
        }
      }
    }
    return ["dm" => $output]; // FIXME: remove 'dm', it is only for testing purposes
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
   * Returns private readonly scope with specified name.
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