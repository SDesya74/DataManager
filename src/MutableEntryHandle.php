<?php

namespace SDesya74\DataManager;

use Closure;
use Illuminate\Support\Facades\Event;

/**
 * Mutable handle for an entry
 */
class MutableEntryHandle extends ReadonlyEntryHandle
{
  public function __construct(Scope $scope, Entry $entry)
  {
    parent::__construct($scope, $entry);
  }

  /**
   * Get a readonly handle with same params
   * @return ReadonlyEntryHandle
   */
  public function readonly()
  {
    return new ReadonlyEntryHandle($this->scope, $this->entry);
  }

  /**
   * Set value to an entry. 
   * 
   * This method will dispatch events on which you can subscribe using the `subscribe` method that DataManager, Scope handles and Entry handles have.
   * 
   * @param mixed $value
   * @return void
   */
  public function set(mixed $value)
  {
    $previousValue = $this->entry->initialized() ? $this->entry->get() : null;
    $this->entry->set($value);
    Event::dispatch($this->fullPath(), [$this->entry, $previousValue]);
  }

  /**
   * Update value in entry
   * 
   * Example:
   * ```php
   * $header = $scope->entry("header"); // get a scope
   * $header->update(fn($last) => array_merge($last, ["new"]), []); // add item to array in entry
   * ```
   * 
   * @param Closure $updater Closure that accepts 1 argument with last value of an entry
   * @param mixed|null $initializer Optional default value of an entry
   * @return void
   */
  public function update(Closure $updater, mixed $initializer = null)
  {
    $this->set($updater($this->get($initializer)));
  }
}