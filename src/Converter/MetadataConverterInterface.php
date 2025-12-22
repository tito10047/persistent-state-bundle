<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Converter;

/**
 * Defines the contract for extracting and hydrating complex metadata (payload)
 * into a storable/readable representation and back.
 */
interface MetadataConverterInterface
{
    /**
     * Converts a metadata object into a safely storable array representation.
     *
     * The result should contain only scalar/array values suitable for storage
     * (e.g. JSON column). Any nested objects must be normalized here.
     *
     * @param object $metadataObject Arbitrary metadata object (e.g. DomainConfig).
     *
     * @return array<string, mixed> serializable array to be persisted
     */
    public function convertToStorable(object $metadataObject): array;

    /**
     * Converts previously stored array data back into the original metadata object.
     *
     * If the data cannot be hydrated to the target class, implementors may return
     * null to indicate absence/invalid payload.
     *
     * @param array<string, mixed> $storedData  raw metadata as read from storage
     * @param string               $targetClass fully-qualified class name to hydrate
     *
     * @return object|null the hydrated metadata instance or null if not possible
     */
    public function convertFromStorable(array $storedData, string $targetClass): ?object;
}
