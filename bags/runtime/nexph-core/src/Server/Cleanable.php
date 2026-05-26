<?php
namespace Core\Server;

interface Cleanable {
    public function isClean(): bool;
}
