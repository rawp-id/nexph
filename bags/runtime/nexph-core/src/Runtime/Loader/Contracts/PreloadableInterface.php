<?php
namespace Core\Runtime\Loader\Contracts;

interface PreloadableInterface
{
    public function preloadFiles(): array;
}
