<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Transformer;

use Tito10047\PersistentStateBundle\Storage\StorableEnvelope;

/**
 * Support all basic types. Int, String, Bool, Float, Null.
 */
class ScalarValueTransformer implements ValueTransformerInterface
{
    public function supports(mixed $value): bool
    {
        return is_scalar($value) || null === $value;
    }

    public function transform(mixed $value): StorableEnvelope
    {
        if (!$this->supports($value)) {
            throw new \InvalidArgumentException('Unsupported scalar value');
        }

        return new StorableEnvelope('scalar', $value);
    }

    public function supportsReverse(StorableEnvelope $value): bool
    {
        return 'scalar' === $value->className;
    }

    public function reverseTransform(StorableEnvelope $value): mixed
    {
        return $value->data;
    }

    public function getIdentifier(mixed $value): int|string
    {
        return $value ?? 'null';
    }
}
