<?php

namespace Tito10047\PersistentStateBundle\Preference\Service;

use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;
use Tito10047\PersistentStateBundle\Selection\Service\TraceableSelection;

final class TraceablePersistentManager implements PreferenceManagerInterface, SelectionManagerInterface
{
    public function __construct(
        private readonly PreferenceManagerInterface|SelectionManagerInterface $inner,
        private readonly PreferenceDataCollector $collector,
        private readonly string $managerName,
    ) {
    }

    public function getPreference(object|string $owner): PreferenceInterface
    {
        if (!$this->inner instanceof PreferenceManagerInterface) {
            throw new \LogicException('Inner manager does not implement PreferenceManagerInterface');
        }

        $preference = $this->inner->getPreference($owner);

        return new TraceablePreference($this->managerName, $preference, $this->collector);
    }

    public function getPreferenceStorage(): \Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface
    {
        if (!$this->inner instanceof PreferenceManagerInterface) {
            throw new \LogicException('Inner manager does not implement PreferenceManagerInterface');
        }

        return $this->inner->getPreferenceStorage();
    }

    public function registerSelection(string $namespace, mixed $source, int|\DateInterval|null $ttl = null): SelectionInterface
    {
        if (!$this->inner instanceof SelectionManagerInterface) {
            throw new \LogicException('Inner manager does not implement SelectionManagerInterface');
        }

        $selection = $this->inner->registerSelection($namespace, $source, $ttl);

        return new TraceableSelection($this->managerName, $namespace, $selection, $this->collector);
    }

    public function getSelection(string $namespace, object|string|null $owner = null): SelectionInterface
    {
        if (!$this->inner instanceof SelectionManagerInterface) {
            throw new \LogicException('Inner manager does not implement SelectionManagerInterface');
        }

        $selection = $this->inner->getSelection($namespace, $owner);

        return new TraceableSelection($this->managerName, $namespace, $selection, $this->collector);
    }
}
