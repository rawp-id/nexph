<?php
namespace Core\Runtime\Loader\Contracts;

interface ConfigProviderInterface
{
    public function config(): array;
}
