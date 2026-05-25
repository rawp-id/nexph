<?php
namespace Test\ValidPackage;

class LazyService
{
    public function run(): string
    {
        return 'lazy loaded';
    }
}
