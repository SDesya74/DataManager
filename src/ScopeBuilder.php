<?php

namespace SDesya74\DataManager;

class ScopeBuilder
{
  protected Scope $scope;
  protected bool $public;

  public function __construct(Scope $scope, bool $public)
  {
    $this->scope = $scope;
    $this->public = $public;
  }

  public function private ()
  {
    $this->public = false;
    return $this;
  }

  public function public ()
  {
    $this->public = true;
    return $this;
  }

  public function mutable(): MutableScopeHandle
  {
    return new MutableScopeHandle($this->scope, $this->public);
  }

  public function readonly(): ReadonlyScopeHandle
  {
    return new ReadonlyScopeHandle($this->scope, $this->public);
  }
}