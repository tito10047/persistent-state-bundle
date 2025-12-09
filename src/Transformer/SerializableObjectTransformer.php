<?php

namespace Tito10047\PersistentStateBundle\Transformer;

use Tito10047\PersistentStateBundle\Storage\StorableEnvelope;

/**
 * Support all basic types. Int, String, Bool, Float, Null
 */
class SerializableObjectTransformer implements ValueTransformerInterface{

    public function supports(mixed $value): bool {
        return is_object($value);
    }

    public function transform(mixed $value): StorableEnvelope {
        return new StorableEnvelope("serializable",serialize($value));
    }

    public function supportsReverse(StorableEnvelope $value): bool {
        return $value->className === "serializable";
    }

    public function reverseTransform(StorableEnvelope $value): mixed {
        return unserialize($value->data);
    }
}