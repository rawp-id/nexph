<?php
namespace Core\Runtime\Package;

class PackageVerifyException extends \RuntimeException
{
    public function __construct(string $package, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Package verification failed for '{$package}': {$reason}", 0, $previous);
    }
}
