<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Resolver;

interface ContextResolverInterface
{
    /**
     * Resolves a unique, stable context identifier for the given object or string.
     *
     * @param object|string $context
     * @return string
     *
     * @throws \InvalidArgumentException if the context cannot be resolved.
     */
    public function resolveContextKey(object|string $context): string;
}
