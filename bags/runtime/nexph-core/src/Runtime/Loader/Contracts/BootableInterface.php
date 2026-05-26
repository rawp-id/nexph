<?php
namespace Core\Runtime\Loader\Contracts;

interface BootableInterface
{
    public function boot(): void;
}
