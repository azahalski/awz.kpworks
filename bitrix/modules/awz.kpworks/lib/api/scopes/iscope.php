<?php
namespace Awz\Kpworks\Api\Scopes;

interface IScope
{
    public function enableScope();
    public function disableScope();
    public function checkRequire();
}