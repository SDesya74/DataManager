<?php

namespace SDesya74\DataManager;

use Illuminate\Support\Facades\Event;

/**
 * Mutable handle for an entry
 */
class ArrayEntryHandle extends MutableEntryHandle
{
    public function __construct(Scope $scope, Entry $entry)
    {
        parent::__construct($scope, $entry);
        $entry->initializer = function (Entry $entry) {
            $entry->set([]);
            Event::dispatch($this->fullPath(), [$entry, "previousValue" => null]);
        };
    }

    /**
     * Merge values into inner array
     * 
     * Example:
     * ```php
     * $header = $scope->array("header"); // get an entry
     * $header->merge(["new"]); // add item to array in entry
     * ```
     * 
     * @param array $values Items to merge into
     * @return void
     */
    public function merge(array ...$values)
    {
        $this->update(fn($last) => array_merge($last, ...$values));
    }
}