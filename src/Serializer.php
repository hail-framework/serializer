<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;

/**
 * small array
 * size:         msgpack < igbinary < json < hprose < serialize
 * encode speed: serialize <= msgpack <= json <= igbinary < hprose
 * decode speed: igbinary < msgpack < serialize < hprose << json
 *
 * big array
 * size:         igbinary << hprose << msgpack < json << serialize
 * encode speed: igbinary < serialize < json < msgpack < hprose
 * decode speed: igbinary < hprose < serialize < msgpack << json
 */

/**
 * @property-read MsgPack $msgpack
 * @property-read Igbinary $igbinary
 * @property-read Hprose $hprose
 * @property-read Json $json
 * @property-read Serialize $php
 *
 * @method MsgPack msgpak()
 * @method Igbinary igbinary()
 * @method Hprose hprose()
 * @method Json json()
 * @method Serialize php()
 */
final class Serializer extends AbstractSerializer
{
    private SerializerInterface $default;

    public function __construct(string $type = null)
    {
        if ($type === null) {
            $env = \getenv('HAIL_SERIALIZER_TYPE');
            $type = \is_string($env) ? $env : 'php';
        }

        $this->default = $this->$type;
    }

    public function __get($name)
    {
        switch ($name) {
            case 'igbinary':
                return $this->igbinary = igbinary::getInstance();

            case 'msgpack':
                return $this->msgpack = msgpack::getInstance();

            case 'hprose':
                return $this->hprose = hprose::getInstance();

            case 'php':
                return $this->php = Serialize::getInstance();

            case 'json':
                return $this->json = Json::getInstance();

            default:
                throw new SerializerException('Serializer type not defined: ' . $name);
        }
    }

    public function __call($name, $arguments)
    {
        return $this->$name;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function doEncode($value): string
    {
        return $this->default->encode($value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function doDecode(string $value)
    {
        return $this->default->decode($value);
    }
}
