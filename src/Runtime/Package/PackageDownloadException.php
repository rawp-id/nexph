<?php
namespace Core\Runtime\Package;

class PackageDownloadException extends \RuntimeException
{
    public function __construct(string $package, string $reason, ?\Throwable $previous = null)
    {
        parent::__construct("Failed to download package '{$package}': {$reason}", 0, $previous);
    }
}
