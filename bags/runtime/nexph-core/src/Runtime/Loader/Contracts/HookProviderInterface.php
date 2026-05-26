<?php
namespace Core\Runtime\Loader\Contracts;

interface HookProviderInterface
{
    public function hooks(): array;
}
