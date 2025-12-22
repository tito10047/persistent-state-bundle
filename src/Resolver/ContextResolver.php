<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Resolver;

final readonly class ContextResolver implements ContextResolverInterface
{
    /**
     * @param iterable<ContextKeyResolverInterface> $resolvers
     */
    public function __construct(
        private iterable $resolvers,
    ) {
    }

    public function resolveContextKey(object|string $context): string
    {
        if (is_string($context)) {
            return $context;
        }

        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($context)) {
                return $resolver->resolve($context);
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Could not resolve persistent context for object of type "%s". Implement PersistentContextInterface or register a resolver.',
            get_debug_type($context)
        ));
    }
}
