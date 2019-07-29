<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;

\defined('FUNCTION_ENV') || \define('FUNCTION_ENV', \function_exists('\\env'));

/**
 * small array
 * size:         msgpack < swoole = swoole(fast) < igbinary < json < hprose < serialize
 * encode speed: swoole(fast) << serialize < msgpack < json < swoole << igbinary << hprose
 * decode speed: swoole ~ swoole(fast) << igbinary < msgpack < serialize < hprose << json
 *
 * big array
 * size:         swoole < igbinary << hprose << msgpack < swoole(fast) < json << serialize
 * encode speed: swoole(fast) < swoole << msgpack < serialize < igbinary =< json < hprose
 * decode speed: swoole(fast) < swoole << igbinary < hprose < serialize < msgpack << json
 *
 * swoole serialize not support > PHP7.3
 */

/**
 * @property-read MsgPack   $msgpak
 * @property-read Igbinary  $igbinary
 * @property-read Hprose    $hprose
 * @property-read Json      $json
 * @property-read Serialize $php
 * @property-read Yaml      $yaml
 * @method MsgPack msgpak()
 * @method Igbinary igbinary()
 * @method Hprose hprose()
 * @method Json json()
 * @method Serialize php()
 * @method Yaml yaml()
 */
final class Serializer extends AbstractSerializer
{
    private const MAP = [
        'msgpack' => MsgPack::class,
        'igbinary' => Igbinary::class,
        'hprose' => Hprose::class,
        'json' => Json::class,
        'php' => Serialize::class,
        'yaml' => Yaml::class,
    ];

    /**
     * @var string
     */
    private $default;

    public function __construct(string $type = null)
    {
        $type = $type ?? self::env('HAIL_SERIALIZER_TYPE') ?? 'php';

        if (!isset(self::MAP[$type])) {
            throw new SerializerException('Serializer type not defined: ' . $type);
        }

        $this->default = $type;
    }

    private static function env(string $name)
    {
        if (FUNCTION_ENV) {
            return \env($name);
        }

        $value = \getenv($name);
        if ($value === false) {
            return null;
        }

        return $value;
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
