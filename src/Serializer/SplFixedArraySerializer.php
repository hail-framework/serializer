<?php
/**
 * @see https://github.com/nilportugues/php-serializer/
 *      Copyright (c) 2015 Nil Portugués Calderó
 */

namespace Hail\Serializer\Serializer;

use Hail\Serializer\Exception\UnserializationException;
use ReflectionClass;
use ReflectionException;
use SplFixedArray;

class SplFixedArraySerializer
{
    /**
     * @param ObjectSerializer $serializer
     * @param SplFixedArray    $splFixedArray
     *
     * @return array
     */
    public static function serialize(SplFixedArray $splFixedArray): array
    {
        $value = [];
        foreach ($splFixedArray->toArray() as $key => $field) {
            $value[$key] = ObjectSerializer::serializeData($field);
        }

        return [
            ObjectSerializer::CLASS_IDENTIFIER_KEY => \get_class($splFixedArray),
            ObjectSerializer::CLASS_PARENT_KEY => SplFixedArray::class,
            ObjectSerializer::SCALAR_VALUE => $value,
        ];
    }

    /**
     * @param array $value
     *
     * @return SplFixedArray
     */
    public static function unserialize(array $value): SplFixedArray
    {
        $className = $value[ObjectSerializer::CLASS_IDENTIFIER_KEY];
        $data = ObjectSerializer::unserializeData($value[ObjectSerializer::SCALAR_VALUE]);

        try {
            /* @var SplFixedArray $instance */
            $ref = new ReflectionClass($className);
            $instance = $ref->newInstanceWithoutConstructor();
        } catch (ReflectionException $e) {
            throw new UnserializationException(
                'Unserialization of SplFixedArray failed', $e
            );
        }

        $instance->setSize(count($data));
        foreach ($data as $k => $v) {
            $instance[$k] = $v;
        }

        return $instance;
    }
}
