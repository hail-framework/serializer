<?php
/**
 * @see https://github.com/nilportugues/php-serializer/
 *      Copyright (c) 2015 Nil Portugués Calderó
 */
namespace Hail\Serializer\Serializer;

use ReflectionClass;
use Hail\Serializer\Exception\{
    SerializationException, UnserializationException
};

class ObjectSerializer
{
    public const CLASS_IDENTIFIER_KEY = '@type';
    public const CLASS_PARENT_KEY = '@parent';
    public const SCALAR_VALUE = '@value';

    /**
     * Storage for object.
     *
     * Used for recursion
     *
     * @var \SplObjectStorage
     */
    protected static $objectStorage;

    /**
     * Object mapping for recursion.
     *
     * @var array
     */
    protected static $objectMapping = [];

    /**
     * Object mapping index.
     *
     * @var int
     */
    protected static $objectMappingIndex = 0;

    /**
     * @var array
     */
    protected static $dateTimeClassType = [
        \DateTime::class,
        \DateTimeImmutable::class,
        \DateTimeZone::class,
        \DateInterval::class,
        \DatePeriod::class,
    ];

    protected static $serializers = [
        \SplFixedArray::class => SplFixedArraySerializer::class,
        \SplDoublyLinkedList::class => SplDoublyLinkedListSerializer::class,
        \Closure::class => ClosureSerializer::class,
    ];

    /**
     * Serialize the value in JSON.
     *
     * @param mixed $value
     *
     * @return string JSON encoded
     */
    public static function serialize($value)
    {
        self::reset();

        return self::serializeData($value);
    }

    /**
     * Reset variables.
     */
    private static function reset(): void
    {
        self::$objectStorage = new \SplObjectStorage();
        self::$objectMapping = [];
        self::$objectMappingIndex = 0;
    }

    /**
     * Parse the data to be json encoded.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function serializeData($value)
    {
        if (\is_resource($value)) {
            throw new SerializationException('Resource is not supported in Serializer');
        }

        if (\is_object($value)) {
            foreach (self::$serializers as $classFQN => $serializer) {
                if (self::isInstanceOf($value, $classFQN)) {
                    $obj = $serializer::serialize($value);
                    self::$objectMapping[self::$objectMappingIndex++] = $obj;

                    return $obj;
                }
            }

            return self::serializeObject($value);
        }

        if (\is_array($value)) {
            return \array_map(['self', __FUNCTION__], $value);
        }

        return $value;
    }

    /**
     * Check if a class is instance or extends from the expected instance.
     *
     * @param object $value
     * @param string $classFQN
     *
     * @return bool
     */
    private static function isInstanceOf($value, string $classFQN)
    {
        return \strtolower(\get_class($value)) === \strtolower($classFQN) ||
            \is_subclass_of($value, $classFQN, true);
    }

    /**
     * Unserialize the value from string.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function unserialize($value)
    {
        self::reset();

        return self::unserializeData($value);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function unserializeData($value)
    {
        if ($value === null || !\is_array($value)) {
            return $value;
        }

        if (isset($value[self::CLASS_PARENT_KEY])) {
            if (isset(self::$serializers[$value[self::CLASS_PARENT_KEY]])) {
                $class = self::$serializers[$value[self::CLASS_PARENT_KEY]];
                return $class::unserialize($value);
            }

            throw new UnserializationException('Unserializer of ' . $value[self::CLASS_PARENT_KEY] . ' not defined');
        }

        if (isset($value[self::CLASS_IDENTIFIER_KEY])) {
            return self::unserializeObject($value);
        }

        return \array_map(['self', __FUNCTION__], $value);
    }

    /**
     * Convert the serialized array into an object.
     *
     * @param array $value
     *
     * @return object
     */
    protected static function unserializeObject(array $value)
    {
        $className = $value[self::CLASS_IDENTIFIER_KEY];
        unset($value[self::CLASS_IDENTIFIER_KEY]);

        if ($className[0] === '@') {
            return self::$objectMapping[\substr($className, 1)];
        }

        if (!\class_exists($className)) {
            throw new UnserializationException('Unable to find class ' . $className);
        }

        return self::unserializeDateTimeFamilyObject($value, $className) ??
            self::unserializeUserDefinedObject($value, $className);
    }

    /**
     * @param array  $value
     * @param string $className
     *
     * @return mixed
     */
    protected static function unserializeDateTimeFamilyObject(array $value, string $className)
    {
        $obj = null;

        if (self::isDateTimeFamilyObject($className)) {
            $obj = self::restoreUsingUnserialize($className, $value);
            self::$objectMapping[self::$objectMappingIndex++] = $obj;
        }

        return $obj;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected static function isDateTimeFamilyObject(string $className): bool
    {
        foreach (self::$dateTimeClassType as $class) {
            if ($class === $className || \is_subclass_of($className, $class, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $className
     * @param array  $attributes
     *
     * @return object
     */
    protected static function restoreUsingUnserialize(string $className, array $attributes)
    {
        foreach ($attributes as &$attribute) {
            $attribute = self::unserializeData($attribute);
        }
        unset($attribute);

        $obj = (object) $attributes;
        $serialized = \preg_replace(
            '/^O:\d+:"\w+":/',
            'O:' . strlen($className) . ':"' . $className . '":',
            \serialize($obj)
        );

        return \unserialize($serialized, ['allowed_classes' => [$className]]);
    }

    /**
     * @param array  $value
     * @param string $className
     *
     * @return object
     * @throws
     */
    protected static function unserializeUserDefinedObject(array $value, string $className)
    {
        $ref = new ReflectionClass($className);
        $obj = $ref->newInstanceWithoutConstructor();

        self::$objectMapping[self::$objectMappingIndex++] = $obj;
        self::setUnserializedObjectProperties($value, $ref, $obj);

        if (\method_exists($obj, '__wakeup')) {
            $obj->__wakeup();
        }

        return $obj;
    }

    /**
     * @param array           $value
     * @param ReflectionClass $ref
     * @param mixed           $obj
     *
     * @return mixed
     */
    protected static function setUnserializedObjectProperties(array $value, ReflectionClass $ref, $obj)
    {
        foreach ($value as $property => $propertyValue) {
            try {
                $propRef = $ref->getProperty($property);
                $propRef->setAccessible(true);
                $propRef->setValue($obj, self::unserializeData($propertyValue));
            } catch (\ReflectionException $e) {
                $obj->$property = self::unserializeData($propertyValue);
            }
        }

        return $obj;
    }

    /**
     * Extract the data from an object.
     *
     * @param mixed $value
     *
     * @return array
     * @throws
     */
    protected static function serializeObject($value)
    {
        if (self::$objectStorage->contains($value)) {
            return [self::CLASS_IDENTIFIER_KEY => '@' . self::$objectStorage[$value]];
        }

        self::$objectStorage->attach($value, self::$objectMappingIndex++);

        $reflection = new ReflectionClass($value);
        $className = $reflection->getName();

        return self::serializeInternalClass($value, $className, $reflection);
    }

    /**
     * @param mixed           $value
     * @param string          $className
     * @param ReflectionClass $ref
     *
     * @return array
     */
    protected static function serializeInternalClass($value, $className, ReflectionClass $ref)
    {
        $paramsToSerialize = self::getObjectProperties($ref, $value);
        $data = [self::CLASS_IDENTIFIER_KEY => $className];
        $data += \array_map(['self', 'serializeData'], self::extractObjectData($value, $ref, $paramsToSerialize));

        return $data;
    }

    /**
     * Return the list of properties to be serialized.
     *
     * @param ReflectionClass $ref
     * @param                 $value
     *
     * @return array
     */
    protected static function getObjectProperties(ReflectionClass $ref, $value)
    {
        $props = \get_object_vars($value);
        foreach ($ref->getProperties() as $prop) {
            $props[$prop->getName()] = true;
        }

        return \array_keys($props);
    }

    /**
     * Extract the object data.
     *
     * @param mixed            $value
     * @param \ReflectionClass $rc
     * @param array            $properties
     *
     * @return array
     */
    protected static function extractObjectData($value, ReflectionClass $rc, array $properties)
    {
        $data = [];

        self::extractCurrentObjectProperties($value, $rc, $properties, $data);
        self::extractAllInhertitedProperties($value, $rc, $data);

        return $data;
    }

    /**
     * @param mixed           $value
     * @param ReflectionClass $rc
     * @param array           $properties
     * @param array           $data
     */
    protected static function extractCurrentObjectProperties($value, ReflectionClass $rc, array $properties, array &$data)
    {
        foreach ($properties as $propertyName) {
            try {
                $propRef = $rc->getProperty($propertyName);
                $propRef->setAccessible(true);
                $data[$propertyName] = $propRef->getValue($value);
            } catch (\ReflectionException $e) {
                $data[$propertyName] = $value->$propertyName;
            }
        }
    }

    /**
     * @param mixed           $value
     * @param ReflectionClass $rc
     * @param array           $data
     */
    protected static function extractAllInhertitedProperties($value, ReflectionClass $rc, array &$data)
    {
        do {
            /* @var $property \ReflectionProperty */
            foreach ($rc->getProperties() as $property) {
                $property->setAccessible(true);
                $name = $property->getName();
                if (isset($data[$name]) || \array_key_exists($name, $data)) {
                    continue;
                }

                $data[$name] = $property->getValue($value);
            }
        } while ($rc = $rc->getParentClass());
    }
}
