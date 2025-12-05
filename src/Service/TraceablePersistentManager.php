<?php

namespace Tito10047\PersistentPreferenceBundle\Service;

use Tito10047\PersistentPreferenceBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentPreferenceBundle\Storage\SelectionStorageInterface;

final class TraceablePersistentManager implements PersistentManagerInterface
{
    public function __construct(
        private readonly PersistentManagerInterface $inner,
        private readonly PreferenceDataCollector    $collector,
        private readonly string                     $managerName,
    ) {}

    public function getPreference(object|string $owner): PreferenceInterface
    {
        $preference = $this->inner->getPreference($owner);
        return new TraceablePreference($this->managerName, $preference, $this->collector);
    }

    public function getPreferenceStorage(): \Tito10047\PersistentPreferenceBundle\Storage\PreferenceStorageInterface
    {
        return $this->inner->getPreferenceStorage();
    }

	public function getSelection(string $namespace, mixed $owner = null): SelectionInterface {
		// TODO: Implement getSelection() method.
	}
}
