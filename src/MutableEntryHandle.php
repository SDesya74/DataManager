<?php

namespace SDesya74\DataManager;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class MutableEntryHandle extends ReadonlyEntryHandle
{
  public function __construct(Scope $scope, Entry $entry)
  {
    parent::__construct($scope, $entry);
  }

  public function readonly()
  {
    return new ReadonlyEntryHandle($this->scope, $this->entry);
  }

  public function set(mixed $value)
  {
    $previousValue = $this->entry->initialized() ? $this->entry->get() : null;
    $this->entry->set($value);
    Event::dispatch($this->fullPath(), [$this->entry, $previousValue]);
  }
}