<?php


namespace Hail\Serializer;

use Hail\Serializer\Serializer\ObjectSerializer;

abstract class AbstractSerializer implements SerializerInterface
{
    protected $serializeObject = false;

    private $objectSerializer;

    public function withObject(): SerializerInterface
    {
        if ($this->objectSerializer === null) {
            $this->objectSerializer = clone $this;
            $this->objectSerializer->serializeObject = true;
        }

        return $this->objectSerializer;
    }

    public function encode($value): string
    {
        if ($this->serializeObject) {
            $value = ObjectSerializer::serialize($value);
        }

        return $this->doEncode($value);
    }

    public function decode(string $value)
    {
        $decode = $this->doDecode($value);

        if ($this->serializeObject) {
            $decode = ObjectSerializer::unserialize($decode);
        }

        return $decode;
    }

    abstract protected function doEncode($value): string;
    abstract protected function doDecode(string $value);
}
