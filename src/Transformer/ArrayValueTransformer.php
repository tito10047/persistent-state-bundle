<?php

namespace Tito10047\PersistentStateBundle\Transformer;

use Tito10047\PersistentStateBundle\Storage\StorableEnvelope;

/**
 * Support all basic types. Int, String, Bool, Float, Null
 */
class ArrayValueTransformer implements ValueTransformerInterface{

    public function supports(mixed $value): bool {
        return is_array($value);
    }

    public function transform(mixed $value): StorableEnvelope {
        return new StorableEnvelope("array",$value);
    }

    public function supportsReverse(StorableEnvelope $value): bool {
        return $value->className === "array";
    }

    public function reverseTransform(StorableEnvelope $value): mixed {
        return $value->data;
    }

	public function getIdentifier(mixed $value): int|string {
		return md5(serialize($value));
	}
}