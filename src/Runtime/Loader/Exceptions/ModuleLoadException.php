<?php
namespace Core\Runtime\Loader\Exceptions;

class ModuleLoadException extends \RuntimeException
{
    public function __construct(string $module, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Failed to load module '{$module}': {$reason}", 0, $previous);
    }
}
