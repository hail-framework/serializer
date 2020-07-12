<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializationException;
use Hail\Serializer\Exception\UnserializationException;
use Hail\Singleton\SingletonTrait;

class Json extends AbstractSerializer
{
    use SingletonTrait;

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

        $options = $this->options | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

        try {
            $json = \json_encode($value, $options);
        } catch (\JsonException $e) {
            throw new SerializationException('JSON encode error', $e);
        }

        $this->reset();

        return $json;
    }

    protected function doDecode(string $json)
    {
        if ($this->options === null) {
            $this->options = JSON_OBJECT_AS_ARRAY;
        }

        $options = $this->options | JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR;
        $assoc = ($options & JSON_OBJECT_AS_ARRAY) !== 0;

        try {
            $decode = \json_decode($json, $assoc, $this->depth, $options);
        } catch (\JsonException $e) {
            throw new UnserializationException('JSON decode error', $e);
        }

        $this->reset();

        return $decode;
    }

    protected function reset(): void
    {
        $this->depth = 512;
        $this->options = null;
    }
}
