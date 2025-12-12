<?php

namespace Tito10047\PersistentStateBundle\Preference\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentStateBundle\Resolver\ContextKeyResolverInterface;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

class PreferenceManager implements PreferenceManagerInterface
{
    /**
     * @param iterable<ContextKeyResolverInterface> $resolvers
     * @param iterable<ValueTransformerInterface>   $transformers
     */
    public function __construct(
        private readonly iterable $resolvers,
        private readonly iterable $transformers,
        private readonly PreferenceStorageInterface $storage,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getPreference(object|string $owner): PreferenceInterface
    {
        $contextKey = $this->resolveContextKey($owner);

        return new Preference($this->transformers, $contextKey, $this->storage, $this->dispatcher);
    }

    public function getPreferenceStorage(): PreferenceStorageInterface
    {
        return $this->storage;
    }

    private function resolveContextKey(object|string $context): string
    {
        // 1. If it's already a string, just use it
        if (is_string($context)) {
            return $context;
        }

        // 3. Try external resolvers (e.g. for Symfony UserInterface)
        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($context)) {
                return $resolver->resolve($context);
            }
        }

        throw new \InvalidArgumentException(sprintf('Could not resolve persistent context for object of type "%s". Implement PersistentContextInterface or register a resolver.', get_class($context)));
    }
}
