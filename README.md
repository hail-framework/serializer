# Serializer

## Example
```php
$data = ['a' => 1, 'b' => 2];
 
// wrapper of Hprose/Igbinary/Json/MsgPack/Serialize/Yaml
use Hail\Serializer\Json;
$json = Json::getInstance();
$serialized = $json->encode($data);
$unserialized = $json->decode($serialized);

assert($data === $unserialized);

// =======================================

use Hail\Serializer\Serializer;

// msgpack/igbinary/hprose/json/php/yaml
$serializer = new Serializer('msgpack');
$serialized = $serializer->encode($data);
$unserialized = $serializer->decode($serialized);

assert($data === $unserialized);

// =======================================

// serialize PHP Object, includes Closure 
$example = function ($a) {
    return $a * 2;
};
$serialized = $serializer->withObject()->encode($example);
$unserialized = $serializer->withObject()->decode($serialized);

assert($unserialized(2) === 4);

// =======================================
assert($serializer->json === $json);
assert($serializer->json() === $json);

// =======================================
// json warpper only
$serialized = $json
    ->setDepth(512) // default
    ->setOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) // default JSON_UNESCAPED_UNICODE 
    ->encode($data);
$unserialized = $json
    ->setDepth(512) // default
    ->setOptions(JSON_OBJECT_AS_ARRAY) // default
    ->decode($serialized);

```
