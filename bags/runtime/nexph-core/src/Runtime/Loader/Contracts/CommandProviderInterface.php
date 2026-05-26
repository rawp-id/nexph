<?php
namespace Core\Runtime\Loader\Contracts;

interface CommandProviderInterface
{
    public function commands(): array;
}
