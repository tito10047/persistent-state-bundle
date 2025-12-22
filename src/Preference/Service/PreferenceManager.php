<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Preference\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentStateBundle\Resolver\ContextResolverInterface;

class PreferenceManager implements PreferenceManagerInterface
{
    public function __construct(
        private readonly ContextResolverInterface $contextResolver,
        private readonly PreferenceFactoryInterface $factory,
        private readonly PreferenceStorageInterface $storage,
    ) {
    }

    public function getPreference(object|string $owner): PreferenceInterface
    {
        $contextKey = $this->contextResolver->resolveContextKey($owner);

        return $this->factory->create($contextKey);
    }

    public function getPreferenceStorage(): PreferenceStorageInterface
    {
        return $this->storage;
    }
}
