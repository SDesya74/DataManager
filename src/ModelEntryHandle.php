<?php

namespace SDesya74\DataManager;

use Illuminate\Support\Facades\Event;

class ModelEntryHandle extends ReadonlyEntryHandle
{
  public function __construct(Scope $scope, Entry $entry, $modelClass, $modelId)
  {
    parent::__construct($scope, $entry);
    $entry->initializer = function (Entry $entry) use ($modelClass, $modelId) {
      $entry->set($modelClass::findOrFail($modelId));
      Event::dispatch($this->fullPath(), [$entry, "previousValue" => null]);
    };
  }
}