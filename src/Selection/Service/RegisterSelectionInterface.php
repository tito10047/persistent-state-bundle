<?php

namespace Tito10047\PersistentStateBundle\Selection\Service;

interface RegisterSelectionInterface {

    /**
     * Registers a source for later reuse under a stable cache key.
     *
     * Useful when the same namespace needs to operate on multiple sources over
     * time, or when you want to decouple source discovery from selection usage.
     *
     * @param string                 $cacheKey Stable key representing the source
     * @param mixed                  $source   Data source supported by an IdentityLoader
     * @param int|\DateInterval|null $ttl      Optional TTL for identifier caching
     * @return static
     */
    public function registerSource(string $cacheKey, mixed $source, int|\DateInterval|null $ttl = null): static;

    /**
     * Whether a source with the given cache key was already registered.
     */
    public function hasSource(string $cacheKey): bool;
}