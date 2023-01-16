<?php

namespace SDesya74\DataManager;

use ArrayAccess;
use Psy\Exception\ErrorException;


class ReadonlyScopeHandle implements ArrayAccess
{
  protected Scope $scope;
  protected bool $public;

  public function __construct(Scope $scope, bool $public)
  {
    $this->scope = $scope;
    $this->public = $public;
  }

  public function entry(string $key): ReadonlyEntryHandle
  {
    return new ReadonlyEntryHandle($this->scope, $this->scope->entry($key)->setPublic($this->public));
  }

  public function model(string $key, $modelClass, $modelId)
  {
    return new ModelEntryHandle($this->scope, $this->scope->entry($key)->setPublic($this->public), $modelClass, $modelId);
  }

  /**
   * Whether or not an entry exists.
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
    return $this->entry($name)->get();
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
    if (key_exists($key, $this->scope->entries())) {
      throw new ErrorException("Cannot override `$key`: scope is readonly, so you can only create new keys"); // TODO: Maybe forbid new keys too?
    }
    $this->scope->entry($key)->set($value)->setPublic($this->public);
  }

  /**
   * Removes entry from scope. (Work only with mutable scopes)
   *
   * @param string $key A key of entry.
   * @return void
   */
  public function offsetUnset($key)
  {
    throw new ErrorException("Cannot remove entry from readonly scope.");
  }
}