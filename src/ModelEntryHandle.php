<?php

namespace SDesya74\DataManager;

use Illuminate\Support\Facades\Event;

/**
 * Wrapper of entry handle for Eloquent models. Automatically initializes entry value with model
 */
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