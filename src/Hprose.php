<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;
use Hail\Singleton\SingletonTrait;

\defined('HPROSE_EXTENSION') || \define('HPROSE_EXTENSION', \extension_loaded('hprose'));

class Hprose
{
    use SingletonTrait,
        ObjectTrait;

    protected function init()
    {
        if (!HPROSE_EXTENSION) {
            throw new SerializerException('Hprose extension not loaded');
        }
    }

    protected function doEncode($value): string
    {
        return \hprose_serialize($value);
    }

    protected function doDecode(string $value)
    {
        return \hprose_unserialize($value);
    }
}
