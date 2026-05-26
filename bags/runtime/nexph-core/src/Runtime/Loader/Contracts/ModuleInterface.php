<?php
namespace Core\Runtime\Loader\Contracts;

interface ModuleInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function getPath(): string;
}
