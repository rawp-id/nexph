<?php
namespace Core\Runtime\Loader\Exceptions;

class ModuleNotFoundException extends \RuntimeException
{
    public function __construct(string $module)
    {
        parent::__construct("Module not found: '{$module}'");
    }
}
