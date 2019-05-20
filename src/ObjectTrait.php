<?php


namespace Hail\Serializer;


use Hail\Serializer\Exception\UnserializationException;
use Hail\Serializer\Serializer\ObjectSerializer;

trait ObjectTrait
{
    protected $serializeObject = false;

    public function withObject(): self
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
