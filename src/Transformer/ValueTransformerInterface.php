<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Transformer;

use Tito10047\PersistentStateBundle\Storage\StorableEnvelope;

/**
 * Responsibility: Handles two-way conversion between PHP Objects and Storage Data.
 *
 * If you want to store complex objects and retrieve them back as objects,
 * implement this interface.
 */
interface ValueTransformerInterface
{
    /**
     * Checks if the transformer can convert the PHP value to storage format.
     * Used during set().
     */
    public function supports(mixed $value): bool;

    /**
     * Converts PHP value to a storage-friendly format (scalar/array).
     * Example: Object -> ['__type' => 'MyClass', 'data' => {...}].
     */
    public function transform(mixed $value): StorableEnvelope;

    public function getIdentifier(mixed $value): int|string;

    /**
     * Checks if the raw value from storage looks like something this transformer created.
     * Used during get().
     *
     * Example: Checks if array has '__type' key.
     */
    public function supportsReverse(StorableEnvelope $value): bool;

    /**
     * Converts storage format back to PHP value.
     * Example: ['__type' => 'MyClass', ...] -> Object.
     */
    public function reverseTransform(StorableEnvelope $value): mixed;
}
