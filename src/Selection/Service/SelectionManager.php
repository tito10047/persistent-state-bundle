<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Service;

use Tito10047\PersistentStateBundle\Resolver\ContextResolverInterface;
use Tito10047\PersistentStateBundle\Selection\Loader\IdentityLoaderInterface;
use Tito10047\PersistentStateBundle\Transformer\ValueTransformerInterface;

final class SelectionManager implements SelectionManagerInterface
{
    public function __construct(
        private readonly SelectionFactoryInterface $factory,
        private readonly ValueTransformerInterface $transformer,
        /** @var IdentityLoaderInterface[] */
        private readonly iterable $loaders,
        private readonly ContextResolverInterface $contextResolver,
        private readonly ?string $ttl,
    ) {
    }

    public function registerSelection(string $namespace, mixed $source, int|\DateInterval|null $ttl = null): SelectionInterface
    {
        $loader = $this->findLoader($source);

        $selection = $this->factory->create($namespace);

        foreach ($source as $item) {
            if (!$this->transformer->supports($item)) {
                throw new \InvalidArgumentException(sprintf('Item of type "%s" is not supported by transformer "%s".', gettype($item), get_class($this->transformer)));
            }
        }
        $cacheKey = $loader->getCacheKey($source);
        if ($selection instanceof RegisterSelectionInterface && !$selection->hasSource($cacheKey)) {
            $ttl = $ttl ?? $this->ttl;
            if (is_string($ttl)) {
                $ttl = is_numeric($ttl) ? (int) $ttl : new \DateInterval($ttl);
            }
            $selection->registerSource($cacheKey,
                $loader->loadAllIdentifiers($this->transformer, $source),
                $ttl
            );
        }

        return $selection;
    }

    public function getSelection(string $namespace, object|string|null $owner = null): SelectionInterface
    {
        // If an owner is provided, scope the selection namespace by the owner's identity
        // to avoid collisions between different owners using the same logical namespace.
        if (null !== $owner) {
            $namespace = $namespace.'::'.$this->contextResolver->resolveContextKey($owner);
        }

        return $this->factory->create($namespace);
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
}
