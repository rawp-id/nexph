<?php
namespace Core\Runtime\Loader\Contracts;

interface ServiceProviderInterface
{
    public function register(): void;
    public function boot(): void;
}
