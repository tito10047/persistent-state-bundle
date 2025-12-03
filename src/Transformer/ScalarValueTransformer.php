<?php

namespace Tito10047\PersistentPreferenceBundle\Transformer;

/**
 * Support all basic types. Int, String, Bool, Float, Null
 */
class ScalarValueTransformer implements ValueTransformerInterface{

    public function supports(mixed $value): bool {
        return is_scalar($value) || $value === null;
    }

    public function transform(mixed $value): mixed {
        return $value;
    }

    public function supportsReverse(mixed $value): bool {
        return is_scalar($value) || $value === null;
    }

    public function reverseTransform(mixed $value): mixed {
        return $value;
    }
}