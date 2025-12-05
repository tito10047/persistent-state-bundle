<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Service;

interface RegisterSelectionInterface {

	public function registerSource(string $cacheKey, mixed $source, int|\DateInterval|null $ttl = null): static;

	public function hasSource(string $cacheKey): bool;
}