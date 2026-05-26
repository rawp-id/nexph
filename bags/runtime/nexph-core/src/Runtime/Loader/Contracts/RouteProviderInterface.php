<?php
namespace Core\Runtime\Loader\Contracts;

interface RouteProviderInterface
{
    public function routes(): array;
}
