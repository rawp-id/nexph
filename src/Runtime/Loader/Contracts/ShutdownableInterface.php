<?php
namespace Core\Runtime\Loader\Contracts;

interface ShutdownableInterface
{
    public function shutdown(): void;
}
