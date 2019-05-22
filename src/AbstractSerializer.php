<?php


namespace Hail\Serializer;

use Hail\Serializer\Serializer\ObjectSerializer;

abstract class AbstractSerializer implements SerializerInterface
{
    protected $serializeObject = false;

    public function withObject(): SerializerInterface
    {
        $this->serializeObject = true;

        return $this;
    }

    public function encode($value): string
    {
        if ($this->serializeObject) {
            $value = ObjectSerializer::serialize($value);
            $this->serializeObject = false;
        }

        return $this->doEncode($value);
    }

    public function decode(string $value)
    {
        $decode = $this->doDecode($value);

        if ($this->serializeObject) {
            $decode = ObjectSerializer::unserialize($decode);
            $this->serializeObject = false;
        }

        return $decode;
    }

    abstract protected function doEncode($value): string;
    abstract protected function doDecode(string $value);
}
