<?php
namespace Core\Runtime\Package;

class PackageInstallException extends \RuntimeException
{
    public function __construct(string $package, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Failed to install package '{$package}': {$reason}", 0, $previous);
    }
}
