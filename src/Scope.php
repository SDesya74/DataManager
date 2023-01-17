<?php

namespace SDesya74\DataManager;

/**
 * Internal DataManager's scope, not meant to be used anywhere except callbacks of `subscribe` methods and in handles
 */
class Scope
{
  protected string $name;
  protected array $entries;
  public function __construct($name)
  {
    $this->name = $name;
    $this->entries = [];
  }

  /**
   * Get name of a scope
   * @return string
   */
  public function name(): string
  {
    return $this->name;
  }

  /**
   * Get all entries inside scope
   * @return array
   */
  public function entries(): array
  {
    return $this->entries;
  }

  /**
   * Get an entry
   * 
   * If entry is not exist, this method will create it first
   * 
   * @return Entry
   */
  public function entry(string $key): Entry
  {
    if (!key_exists($key, $this->entries)) {
      $this->entries[$key] = new Entry($key);
    }
    return $this->entries[$key];
  }

  /**
   * Check if entry exists
   * @param mixed $key
   * @return bool
   */
  public function entryExists($key): bool
  {
    return key_exists($key, $this->entries());
  }

  /**
   * Delete an entry
   * @param string $key
   * @return void
   */
  public function removeEntry(string $key)
  {
    unset($this->entries[$key]);
  }
}