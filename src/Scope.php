<?php

namespace SDesya74\DataManager;

class Scope
{
  protected string $name;
  protected array $entries;
  public function __construct($name)
  {
    $this->name = $name;
    $this->entries = [];
  }

  public function name()
  {
    return $this->name;
  }

  public function entries()
  {
    return $this->entries;
  }

  public function entry(string $key): Entry
  {
    if (!key_exists($key, $this->entries)) {
      $this->entries[$key] = new Entry($key);
    }
    return $this->entries[$key];
  }

  public function entryExists($key): bool
  {
    return key_exists($key, $this->entries());
  }

  public function removeEntry(string $key)
  {
    unset($this->entries[$key]);
  }
}