<?php

namespace Hail\Serializer;

use Hail\Singleton\SingletonTrait;

class Serialize extends AbstractSerializer
{
    use SingletonTrait;

    protected function doEncode($value): string
    {
        return \serialize($value);
    }

    protected function doDecode(string $value)
    {
        return \unserialize($value, ['allowed_classes' => false]);
    }
}
