<?php


namespace Hail\Serializer;

use Opis\Closure\SerializableClosure;

abstract class AbstractSerializer implements SerializerInterface
{
    protected bool $serializeClosure = false;

    public function withClosure(bool $withClosure = true): self
    {
        $this->serializeClosure = $withClosure;

        return $this;
    }

    final public function encode($value): string
    {
        if ($this->serializeClosure && $value instanceof \Closure) {
            $value = new SerializableClosure($value);
        }
        $this->serializeClosure = false;

        return $this->doEncode($value);
    }

    final public function decode(string $value)
    {
        $decode = $this->doDecode($value);

        if ($this->serializeClosure && $decode instanceof SerializableClosure) {
            $decode = $decode->getClosure();
        }
        $this->serializeClosure = false;

        return $decode;
    }

    abstract protected function doEncode($value): string;
    abstract protected function doDecode(string $value);
}
