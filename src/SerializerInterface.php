<?php


namespace Hail\Serializer;


interface SerializerInterface
{
    public function withClosure(bool $withClosure = true): self;

    public function encode($value): string;

    public function decode(string $value);
}
