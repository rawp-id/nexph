<?php
namespace Core\Runtime\Loader\Exceptions;

class ModuleConflictException extends \RuntimeException
{
    public function __construct(string $moduleA, string $moduleB, string $reason)
    {
        parent::__construct("Module conflict between '{$moduleA}' and '{$moduleB}': {$reason}");
    }
}
