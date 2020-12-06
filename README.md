# Serializer

## Example
```php
$data = ['a' => 1, 'b' => 2];
use Hail\Serializer\Serializer;

// msgpack/igbinary/hprose/json/php/yaml
$serializer = new Serializer('msgpack');
$serialized = $serializer->encode($data);
$unserialized = $serializer->decode($serialized);

assert($data === $unserialized);

// =======================================

// serialize \Closure 
$example = function ($a) {
    return $a * 2;
};
$serialized = $serializer->withClosure()->encode($example);
$unserialized = $serializer->withClosure()->decode($serialized);

assert($unserialized(2) === 4);

// =======================================
 
// wrapper of Hprose/Igbinary/Json/MsgPack/Serialize/Yaml
use Hail\Serializer\Json;
$json = Json::getInstance();
$serialized = $json->encode($data);
$unserialized = $json->decode($serialized);

assert($data === $unserialized);

// =======================================
assert($serializer->json === $json);
assert($serializer->json() === $json);

// =======================================
// json wrapper only
$serialized = $json
    ->withDepth(512) // default
    ->withOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) // default JSON_UNESCAPED_UNICODE 
    ->encode($data);
$unserialized = $json
    ->withDepth(512) // default
    ->withOptions(JSON_OBJECT_AS_ARRAY) // default
    ->decode($serialized);

// after encode/decode depths and options will restore to default
```
