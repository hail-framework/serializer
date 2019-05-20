<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;
use Hail\Singleton\SingletonTrait;

\defined('IGBINARY_EXTENSION') || \define('IGBINARY_EXTENSION', \extension_loaded('igbinary'));

class Igbinary
{
    use SingletonTrait,
        ObjectTrait;

    protected function init()
    {
        if (!IGBINARY_EXTENSION) {
            throw new SerializerException('Igbinary extension not loaded');
        }
    }

    protected function doEncode($value): string
    {
        return \igbinary_serialize($value);
    }

    protected function doDecode(string $value)
    {
        return \igbinary_unserialize($value);
    }
}
