<?php

namespace Tito10047\PersistentPreferenceBundle\Service;

use Tito10047\PersistentPreferenceBundle\DataCollector\PreferenceDataCollector;

final class TraceablePreferenceManager implements PreferenceManagerInterface
{
    public function __construct(
        private readonly PreferenceManagerInterface $inner,
        private readonly PreferenceDataCollector $collector,
        private readonly string $managerName,
    ) {}

    public function getPreference(object|string $context): PreferenceInterface
    {
        $preference = $this->inner->getPreference($context);
        return new TraceablePreference($this->managerName, $preference, $this->collector);
    }

    public function getStorage(): \Tito10047\PersistentPreferenceBundle\Storage\StorageInterface
    {
        return $this->inner->getStorage();
    }
}
