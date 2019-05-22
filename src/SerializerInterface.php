<?php


namespace Hail\Serializer;


interface SerializerInterface
{
    public function withObject(): SerializerInterface;

    public function encode($value): string;

    public function decode(string $value);
}
