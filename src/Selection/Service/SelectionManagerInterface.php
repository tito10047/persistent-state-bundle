<?php

namespace Tito10047\PersistentStateBundle\Selection\Service;

interface SelectionManagerInterface
{
    /**
     * Registers a selectable source under a namespace and returns its Selection API.
     *
     * The $source can be any type supported by a configured IdentityLoader
     * (e.g. Doctrine Query/QueryBuilder/Collection, arrays, scalars, etc.).
     * The loader is responsible for extracting stable identifiers from the source.
     *
     * @param string                     $namespace A logical key for this selection (used for storage/session)
     * @param mixed                      $source    Data source to derive item identifiers from
     * @param int|\DateInterval|null     $ttl       Optional time-to-live for cached identifier lists
     * @return SelectionInterface                   Fluent API to manipulate selection state
     */
    public function registerSelection(string $namespace, mixed $source, int|\DateInterval|null $ttl = null): SelectionInterface;

    /**
     * Returns the Selection API for an already registered namespace.
     *
     * If $owner is provided, its context may be used by the storage layer
     * (e.g. to scope selection to a user/tenant).
     */
    public function getSelection(string $namespace, object|string $owner = null): SelectionInterface;
}