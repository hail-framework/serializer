<?php

namespace Hail\Serializer;

use Hail\Singleton\SingletonTrait;
use Opis\Closure\SerializableClosure;

class Serialize extends AbstractSerializer
{
    use SingletonTrait;

    protected function doEncode($value): string
    {
        return \serialize($value);
    }

    protected function doDecode(string $value)
    {
        if ($this->serializeClosure) {
            return \unserialize($value, ['allowed_classes' => [SerializableClosure::class]]);
        }

        return \unserialize($value, ['allowed_classes' => false]);
    }
}
