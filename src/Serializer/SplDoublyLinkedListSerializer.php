<?php


namespace Hail\Serializer\Serializer;

use Hail\Serializer\Exception\UnserializationException;
use ReflectionClass;
use ReflectionException;
use SplDoublyLinkedList;

class SplDoublyLinkedListSerializer
{
    /**
     * @param SplDoublyLinkedList $splDoublyLinkedList
     *
     * @return array
     */
    public static function serialize(SplDoublyLinkedList $splDoublyLinkedList): array
    {
        return [
            ObjectSerializer::CLASS_IDENTIFIER_KEY => \get_class($splDoublyLinkedList),
            ObjectSerializer::CLASS_PARENT_KEY => SplDoublyLinkedList::class,
            ObjectSerializer::SCALAR_VALUE => $splDoublyLinkedList->serialize(),
        ];
    }

    /**
     * @param array $value
     *
     * @return SplDoublyLinkedList
     */
    public static function unserialize(array $value): SplDoublyLinkedList
    {
        $className = $value[ObjectSerializer::CLASS_IDENTIFIER_KEY];

        try {
            /* @var SplDoublyLinkedList $instance */
            $ref = new ReflectionClass($className);
            $instance = $ref->newInstanceWithoutConstructor();
        } catch (ReflectionException $e) {
            throw new UnserializationException(
                'Unserialization of SplDoublyLinkedList failed', $e
            );
        }

        $instance->unserialize($value[ObjectSerializer::SCALAR_VALUE]);

        return $instance;
    }
}
