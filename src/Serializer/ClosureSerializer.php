<?php
/**
 * @see https://github.com/jeremeamia/super_closure
 *      Copyright (c) 2010-2015 Jeremy Lindblom
 *
 * @see https://github.com/opis/closure
 *      Copyright (c) 2018-2019 Zindex Software
 */

namespace Hail\Serializer\Serializer;

use Closure;
use ParseError;
use ReflectionException;
use Hail\Serializer\Exception\SerializationException;
use Hail\Serializer\Exception\UnserializationException;
use Hail\Serializer\Reflection\ReflectionClosure;

class ClosureSerializer
{
    /**
     * The special value marking a recursive reference to a closure.
     *
     * @var string
     */
    public const RECURSION = '{{RECURSION}}';

    /**
     * The keys of closure data required for serialization.
     *
     * @var array
     */
    private static $dataToKeep = [
        'code' => true,
        'context' => true,
        'binding' => true,
        'scope' => true,
        'isStatic' => true,
    ];

    public static function serialize(Closure $closure): array
    {
        try {
            return self::getData($closure);
        } catch (ReflectionException $e) {
            throw new SerializationException(
                'Serialization of ' . \get_class($closure) . ' failed', $e
            );
        }
    }

    /**
     * Unserializes the closure.
     *
     * Unserializes the closure's data and recreates the closure using a
     * simulation of its original context. The used variables (context) are
     * extracted into a fresh scope prior to redefining the closure. The
     * closure is also rebound to its former object and scope.
     *
     * @param array $data
     *
     * @return Closure
     */
    public static function unserialize(array $data): Closure
    {
        $closure = __reconstruct_closure($data);

        // Throw an exception if the closure could not be reconstructed.
        if (!$closure instanceof Closure) {
            throw new UnserializationException(Closure::class);
        }

        // Rebind the closure to its former binding and scope.
        if ($data['binding'] || $data['isStatic']) {
            $closure = $closure->bindTo(
                $data['binding'],
                $data['scope'] ?? 'static'
            );
        }

        return $closure;
    }

    /**
     * @param Closure $closure
     *
     * @return array
     * @throws ReflectionException
     */
    private static function getData(Closure $closure): array
    {
        $reflection = new ReflectionClosure($closure);
        $scope = $reflection->getClosureScopeClass();

        $data = [
            'reflection' => $reflection,
            'code' => $reflection->getCode(),
            'hasThis' => $reflection->isBindingRequired(),
            'context' => $reflection->getUseVariables(),
            'hasRefs' => false,
            'binding' => $reflection->getClosureThis(),
            'scope' => $scope ? $scope->getName() : null,
            'isStatic' => $reflection->isStatic(),
        ];

        // If there is no reference to the binding, don't serialize it.
        if (!$data['hasThis']) {
            $data['binding'] = null;
        }

        // Remove data about the closure that does not get serialized.
        $data = \array_intersect_key($data, self::$dataToKeep);

        // Wrap any other closures within the context.
        foreach ($data['context'] as &$value) {
            if ($value instanceof Closure) {
                $value = ($value === $closure)
                    ? self::RECURSION
                    : ObjectSerializer::serializeData($value);
            }
        }

        return [
            ObjectSerializer::CLASS_IDENTIFIER_KEY => \get_class($closure),
            ObjectSerializer::CLASS_PARENT_KEY => Closure::class,
            ObjectSerializer::SCALAR_VALUE => $data,
        ];
    }
}

/**
 * Reconstruct a closure.
 *
 * HERE BE DRAGONS!
 *
 * The infamous `eval()` is used in this method, along with the error
 * suppression operator, and variable variables (i.e., double dollar signs) to
 * perform the unserialization logic. I'm sorry, world!
 *
 * This is also done inside a plain function instead of a method so that the
 * binding and scope of the closure are null.
 *
 * @param array $__data Unserialized closure data.
 *
 * @return Closure|null
 * @internal
 */
function __reconstruct_closure(array $__data)
{
    // Simulate the original context the closure was created in.
    foreach ($__data['context'] as $__var_name => &$__value) {
        if ($__value === ClosureSerializer::RECURSION) {
            // Track recursive references (there should only be one).
            $__recursive_reference = $__var_name;
        } elseif (isset($__value[ObjectSerializer::CLASS_IDENTIFIER_KEY])) {
            // Unbox any SerializableClosures in the context.
            $__value = ObjectSerializer::unserializeData($__value);
        }

        // Import the variable into this scope.
        ${$__var_name} = $__value;
    }
    unset($__value);

    // Evaluate the code to recreate the closure.
    try {
        if (isset($__recursive_reference)) {
            // Special handling for recursive closures.
            @eval("\${$__recursive_reference} = {$__data['code']};");
            $__closure = ${$__recursive_reference};
        } else {
            @eval("\$__closure = {$__data['code']};");
        }
    } catch (ParseError $e) {
        // Discard the parse error.
    }

    return $__closure ?? null;
}
