<?php

namespace Tito10047\PersistentStateBundle\Selection\Loader;

use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

interface IdentityLoaderInterface
{
    /**
     * Extracts all stable item identifiers from a given source.
     *
     * The optional $transformer can be used to normalize values and/or metadata
     * into a storage-friendly representation before returning identifiers.
     *
     * @param ValueTransformerInterface|null $transformer Optional transformer for normalization
     * @param mixed                          $source      Supported data source (e.g. array, Doctrine query)
     *
     * @return array<int|string> List of identifiers
     */
    public function loadAllIdentifiers(?ValueTransformerInterface $transformer, mixed $source): array;

    /**
     * Returns the total number of items available in the given source.
     * This can be used for UI counters and pagination.
     */
    public function getTotalCount(mixed $source): int;

    /**
     * Whether this loader supports the provided source type/value.
     */
    public function supports(mixed $source): bool;

    /**
     * Produces a stable cache key representing the source configuration.
     * Implementations should ensure the key changes when the source changes.
     */
    public function getCacheKey(mixed $source): string;
}
