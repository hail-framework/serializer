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
 * @property-read MsgPack $msgpak
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
    private const MAP = [
        'msgpack' => MsgPack::class,
        'igbinary' => Igbinary::class,
        'hprose' => Hprose::class,
        'json' => Json::class,
        'php' => Serialize::class,
    ];

    /**
     * @var string
     */
    private $default;

    public function __construct(string $type = null)
    {
        $type = $type ?? (\getenv('HAIL_SERIALIZER_TYPE') ?: 'php');

        if (!isset(self::MAP[$type])) {
            throw new SerializerException('Serializer type not defined: ' . $type);
        }

        $this->default = $type;
    }


    public function __get($name)
    {
        if (!isset(self::MAP[$name])) {
            throw new SerializerException('Serializer type not defined: ' . $name);
        }

        return $this->$name = (self::MAP[$name])::getInstance();
    }

    public function __call($name, $arguments)
    {
        if (isset(static::MAP[$name])) {
            return $this->$name;
        }

        throw new SerializerException('Serializer type not defined: ' . $name);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function doEncode($value): string
    {
        return $this->{$this->default}->encode($value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function doDecode(string $value)
    {
        return $this->{$this->default}->decode($value);
    }
}
