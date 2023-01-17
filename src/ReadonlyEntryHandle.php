<?php

namespace SDesya74\DataManager;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

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

  public function get(mixed $initializer = null)
  {
    return $this->entry->get($initializer);
  }

  protected function fullPath()
  {
    return implode(".", ["dm", $this->scope->name(), $this->entry->key()]);
  }

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

  public function isPublic()
  {
    return $this->entry->public;
  }

  public function setPublic(bool $public = true)
  {
    $this->entry->setPublic($public);
    return $this;
  }
}