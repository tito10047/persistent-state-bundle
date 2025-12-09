<?php

namespace Tito10047\PersistentStateBundle\Preference\Service;

use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentStateBundle\Service\PersistentManagerInterface;

final class TraceablePersistentManager implements PreferenceManagerInterface
{
    public function __construct(
        private readonly PreferenceManagerInterface $inner,
        private readonly PreferenceDataCollector    $collector,
        private readonly string                     $managerName,
    ) {}

    public function getPreference(object|string $owner): PreferenceInterface
    {
        $preference = $this->inner->getPreference($owner);
        return new TraceablePreference($this->managerName, $preference, $this->collector);
    }

    public function getPreferenceStorage(): \Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface
    {
        return $this->inner->getPreferenceStorage();
    }

	public function getSelection(string $namespace, mixed $owner = null): SelectionInterface {
		// TODO: Implement getSelection() method.
	}
}
