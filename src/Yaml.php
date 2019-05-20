<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;
use Hail\Singleton\SingletonTrait;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

\defined('YAML_EXTENSION') || \define('YAML_EXTENSION', \extension_loaded('yaml'));

class Yaml
{
    use SingletonTrait,
        ObjectTrait;

    protected function init(): void
    {
        if (!YAML_EXTENSION && !class_exists(SymfonyYaml::class)) {
            throw new SerializerException('Yaml parser not loaded');
        }

        if (YAML_EXTENSION && \ini_get('yaml.decode_php')) {
            \ini_set('yaml.decode_php', 0);
        }
    }

    public static function constant(string $const)
    {
        if (\defined($const)) {
            return \constant($const);
        }

        throw new \RuntimeException(\sprintf('The constant "%s" is not defined.', $const));
    }

    protected function doEncode($value): string
    {
        if (YAML_EXTENSION) {
            return \yaml_emit($value, YAML_UTF8_ENCODING, YAML_LN_BREAK);
        }

        return SymfonyYaml::dump($value, 2, 0, SymfonyYaml::DUMP_EXCEPTION_ON_INVALID_TYPE);
    }

    protected function doDecode(string $value)
    {
        if (YAML_EXTENSION) {
            return \yaml_parse($value, 0, $i, [
                '!php/const' => [self::class, 'constant'],
            ]);
        }

        return SymfonyYaml::parse($value, SymfonyYaml::PARSE_CONSTANT);
    }
}
