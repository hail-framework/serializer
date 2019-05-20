<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;

\defined('FUNCTION_ENV') || \define('FUNCTION_ENV', \function_exists('\\env'));

/**
 * 小数组
 * 尺寸:     msgpack < swoole = swoole(fast) < igbinary < json < hprose < serialize
 * 序列化速度:   swoole(fast) << serialize < msgpack < json < swoole << igbinary << hprose
 * 反序列化速度: swoole ~ swoole(fast) << igbinary < msgpack < serialize < hprose << json
 *
 * 大数组
 * 尺寸:     swoole < igbinary << hprose << msgpack < swoole(fast) < json << serialize
 * 序列化速度:   swoole(fast) < swoole << msgpack < serialize < igbinary =< json < hprose
 * 反序列化速度: swoole(fast) < swoole << igbinary < hprose < serialize < msgpack << json
 *
 * swoole serialize 尺寸小，速度快但是官方已经放弃继续支持 PHP7.3+
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
final class Serializer
{
    use ObjectTrait;

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

        if (\strlen($value) > 1 && $value[0] === '"' && $value[-1] === '"') {
            return \substr($value, 1, -1);
        }

        return $value;
    }

    public function __get($name)
    {
        if (!isset(self::MAP[$name])) {
            throw new SerializerException('Serializer type not defined: ' . $name);
        }

        return $this->$name = new (self::MAP[$name])();
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
