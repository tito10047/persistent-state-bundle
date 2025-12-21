<?php

namespace Tito10047\PersistentStateBundle\Selection\Service;

use Tito10047\PersistentStateBundle\Resolver\ContextKeyResolverInterface;
use Tito10047\PersistentStateBundle\Selection\Loader\IdentityLoaderInterface;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionStorageInterface;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

final class SelectionManager implements SelectionManagerInterface
{
    public function __construct(
        private readonly SelectionStorageInterface $storage,
        private readonly ValueTransformerInterface $transformer,
        private readonly ValueTransformerInterface $metadataTransformer,
        /** @var IdentityLoaderInterface[] */
        private readonly iterable $loaders,
        /** @var iterable<ContextKeyResolverInterface> $resolvers */
        private readonly iterable $resolvers,
        private readonly ?string $ttl,
    ) {
    }

    public function registerSelection(string $namespace, mixed $source, int|\DateInterval|null $ttl = null): SelectionInterface
    {
        $loader = $this->findLoader($source);

        $selection = new Selection(
            $namespace,
            $this->storage,
            $this->transformer,
            $this->metadataTransformer,
        );

        foreach ($source as $item) {
            if (!$this->transformer->supports($item)) {
                throw new \InvalidArgumentException(sprintf('Item of type "%s" is not supported by transformer "%s".', gettype($item), get_class($this->transformer)));
            }
        }
        $cacheKey = $loader->getCacheKey($source);
        if (!$selection->hasSource($cacheKey)) {
            $selection->registerSource($cacheKey,
                $loader->loadAllIdentifiers($this->transformer, $source),
                $ttl ?? $this->ttl
            );
        }

        return $selection;
    }

    public function getSelection(string $namespace, object|string|null $owner = null): SelectionInterface
    {
        // If an owner is provided, scope the selection namespace by the owner's identity
        // to avoid collisions between different owners using the same logical namespace.
        if (null !== $owner) {
            $namespace = $namespace.'::'.$this->resolveContextKey($owner);
        }

        return new Selection($namespace, $this->storage, $this->transformer, $this->metadataTransformer);
    }

    private function findLoader(mixed $source): IdentityLoaderInterface
    {
        $loader = null;
        foreach ($this->loaders as $_loader) {
            if ($_loader->supports($source)) {
                $loader = $_loader;
                break;
            }
        }
        if (null === $loader) {
            throw new \InvalidArgumentException('No suitable loader found for the given source.');
        }

        return $loader;
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
