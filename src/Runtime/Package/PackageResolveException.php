<?php
namespace Core\Runtime\Package;

class PackageResolveException extends \RuntimeException
{
    public function __construct(string $package, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Failed to resolve package '{$package}': {$reason}", 0, $previous);
    }
}
