<?php

namespace SDesya74\DataManager;

/**
 * A builder for scope handles. 
 * 
 * Scopes are private by default. 
 * Methods `mutable` and `readonly` returns handles for type system support.
 * 
 * Example:
 * ```php
 * $scope = $dm->scope("private")->private()->mutable();
 * $scope = $dm->scope("private")->public()->readonly();
 * ```
 */
class ScopeHandleBuilder
{
  protected Scope $scope;
  protected bool $public;

  public function __construct(Scope $scope, bool $public)
  {
    $this->scope = $scope;
    $this->public = $public;
  }

  /**
   * Make handle private
   * 
   * @return ScopeHandleBuilder
   */
  public function private ()
  {
    $this->public = false;
    return $this;
  }

  /**
   * Make handle public
   * 
   * @return ScopeHandleBuilder
   */
  public function public ()
  {
    $this->public = true;
    return $this;
  }

  /**
   * Get a mutable scope handle
   * 
   * @return MutableScopeHandle
   */
  public function mutable(): MutableScopeHandle
  {
    return new MutableScopeHandle($this->scope, $this->public);
  }

  /**
   * Get a readonly scope handle
   * 
   * @return ReadonlyScopeHandle
   */
  public function readonly(): ReadonlyScopeHandle
  {
    return new ReadonlyScopeHandle($this->scope, $this->public);
  }

  /**
   * Get associative array with all values of entries
   * @return array
   */
  public function all(): array
  {
    return array_map(fn($e) => $e->get(), $this->scope->entries());
  }
}