<?php
namespace Core\Runtime\Loader\Exceptions;

class ManifestValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(string $path, array $errors)
    {
        $this->errors = $errors;
        parent::__construct("Invalid manifest at '{$path}': " . implode(', ', $errors));
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
