<?php

namespace Tito10047\PersistentStateBundle\Resolver;

/**
 * Strategy interface to resolve a persistent context string from an arbitrary object.
 *
 * Implementations are used by the PreferenceManager to turn domain objects
 * (e.g. User, Tenant) into a unique context identifier string like
 * "user_123" or "tenant:acme".
 */
interface ContextKeyResolverInterface
{
    /**
     * Whether this resolver can handle the given context object.
     */
    public function supports(object $context): bool;

    /**
     * Resolves a unique, stable context identifier for the given object.
     *
     * Should only be called if {@see supports()} returned true.
     */
    public function resolve(object $context): string;
}
