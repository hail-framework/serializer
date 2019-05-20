<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;
use Hail\Singleton\SingletonTrait;

\defined('MSGPACK_EXTENSION') || \define('MSGPACK_EXTENSION', \extension_loaded('msgpack'));

class MsgPack
{
    use SingletonTrait,
        ObjectTrait;

    protected function init()
    {
        if (!MSGPACK_EXTENSION) {
            throw new SerializerException('MsgPack extension not loaded');
        }
    }

    protected function doEncode($value): string
    {
        return \msgpack_pack($value);
    }

    protected function doDecode(string $value)
    {
        return \msgpack_unpack($value);
    }
}
