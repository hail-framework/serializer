<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializationException;
use Hail\Serializer\Exception\UnserializationException;
use Hail\Singleton\SingletonTrait;

class Json
{
    use SingletonTrait,
        ObjectTrait;

    private $depth = 512;
    private $options;

    public function setDepth(int $depth)
    {
        $this->depth = $depth;

        return $this;
    }

    public function setOptions(int $options)
    {
        $this->options = $options;
    }

    protected function doEncode($value): string
    {
        if ($this->options === null) {
            $this->options = JSON_UNESCAPED_UNICODE;
        }

        $options = $this->options | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;
        $this->options = null;

        // php >= 7.3
//        try {
//            $json = \json_encode($value, $options | JSON_THROW_ON_ERROR);
//        } catch (\JsonException $e) {
//            throw new SerializationException('JSON encode error', $e);
//        }
        $json = \json_encode($value, $options, $this->depth);
        if (JSON_ERROR_NONE !== ($error = \json_last_error())) {
            throw new SerializationException('JSON encode error',
                new \RuntimeException(\json_last_error_msg(), $error)
            );
        }

        return $json;
    }

    protected function doDecode(string $json)
    {
        if ($this->options === null) {
            $this->options = JSON_OBJECT_AS_ARRAY;
        }

        $options = $this->options | JSON_BIGINT_AS_STRING;
        $assoc = ($options & JSON_OBJECT_AS_ARRAY) !== 0;
        $this->options = null;

        // php >= 7.3
//        try {
//            $decode = \json_decode($json, $assoc, $this->depth, $options);
//        } catch (\JsonException $e) {
//            throw new UnserializationException('JSON decode error', $e);
//        }
        $decode = \json_decode($json, $assoc, $this->depth, $options);
        if (JSON_ERROR_NONE !== ($error = \json_last_error())) {
            throw new UnserializationException('JSON decode error',
                new \RuntimeException(\json_last_error_msg(), $error)
            );
        }

        return $decode;
    }
}
