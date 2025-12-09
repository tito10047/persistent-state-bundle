<?php

namespace Tito10047\PersistentStateBundle\Preference\Service;

use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;

/**
 * Decorates a Preference to report changes to the DataCollector in debug mode.
 */
final class TraceablePreference implements PreferenceInterface
{
    public function __construct(
        private readonly string $managerName,
        private readonly PreferenceInterface $inner,
        private readonly PreferenceDataCollector $collector
    ) {}

    public function getContext(): string
    {
        return $this->inner->getContext();
    }

    public function set(string $key, mixed $value): self
    {
        $this->inner->set($key, $value);
        $this->collector->onPreferenceChanged($this->managerName, $this->getContext(), $this->inner->all());
        return $this;
    }

    public function import(array $values): self
    {
        $this->inner->import($values);
        $this->collector->onPreferenceChanged($this->managerName, $this->getContext(), $this->inner->all());
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->inner->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return $this->inner->getInt($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return $this->inner->getBool($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->inner->has($key);
    }

    public function remove(string $key): self
    {
        $this->inner->remove($key);
        $this->collector->onPreferenceChanged($this->managerName, $this->getContext(), $this->inner->all());
        return $this;
    }

    public function all(): array
    {
        return $this->inner->all();
    }
}
