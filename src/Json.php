<?php

namespace Hail\Serializer;

use Hail\Serializer\Exception\SerializerException;
use Hail\Singleton\SingletonTrait;

class Json extends AbstractSerializer
{
    use SingletonTrait;

    private int $depth = 512;

    private ?int $options;

    public function withDepth(int $depth): self
    {
        $this->depth = $depth;

        return $this;
    }

    public function withOptions(int $options): self
    {
        $this->options = $options;

        return $this;
    }

    protected function doEncode($value): string
    {
        if (\is_object($value)) {
            throw new \RuntimeException('JSON does not support encode object');
        }

        if ($this->options === null) {
            $this->options = JSON_UNESCAPED_UNICODE;
        }

        $options = $this->options | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;

        try {
            $json = \json_encode($value, $options);
        } catch (\JsonException $e) {
            throw new SerializerException('JSON encode error', $e);
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
            throw new SerializerException('JSON decode error', $e);
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
