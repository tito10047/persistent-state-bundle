<?php

namespace Tito10047\PersistentPreferenceBundle\Selection\Service;

use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;
use Tito10047\PersistentPreferenceBundle\Selection\Storage\SelectionStorageInterface;
use Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope;
use Tito10047\PersistentPreferenceBundle\Transformer\ValueTransformerInterface;

final class Selection implements SelectionInterface, HasModeInterface, RegisterSelectionInterface {

	public function __construct(
		private readonly string                    $key,
		private readonly SelectionStorageInterface $storage,
		private readonly ValueTransformerInterface $transformer,
		private readonly ValueTransformerInterface $metadataTransformer,
	) {
	}

	public function isSelected(mixed $item): bool {
		$id = $this->normalizeIdentifier($item);
		$has = $this->storage->hasIdentifier($this->key, $id);
		return $this->storage->getMode($this->key) === SelectionMode::INCLUDE ? $has : !$has;
	}

	public function select(mixed $item, null|array|object $metadata = null): static {
		$id        = $this->normalizeIdentifier($item);
		$mode      = $this->storage->getMode($this->key);
		$metaArray = null;
		if ($metadata !== null) {
			$metaArray = $this->metadataTransformer->transform($metadata)->toArray();
		}
		if ($mode === SelectionMode::INCLUDE) {
			$this->storage->set($this->key, $id, $metaArray);
		} else {
			// In EXCLUDE mode, selecting means removing the id from the exclusion list
			$this->storage->remove($this->key, [$id]);
		}
		return $this;
	}

	public function unselect(mixed $item): static {
		$id = $this->normalizeIdentifier($item);
		if ($this->storage->getMode($this->key) === SelectionMode::INCLUDE) {
			$this->storage->remove($this->key, [$id]);
		} else {
			$this->storage->set($this->key, $id, null);
		}
		return $this;
	}

	public function selectMultiple(array $items, null|array $metadata = null): static {
		$mode = $this->storage->getMode($this->key);
		// If metadata is provided as a map per-id, we need to process per item.
		// When metadata is null, we can batch add in INCLUDE mode.
		if ($mode === SelectionMode::EXCLUDE) {
			throw new \LogicException('Cannot select multiple items in EXCLUDE mode.');
		}
		if ($metadata === null) {
			$ids = [];
			foreach ($items as $item) {
				$ids[] = $this->normalizeIdentifier($item);
			}
			$this->storage->setMultiple($this->key, $ids);
			return $this;
		}
		foreach ($items as $item) {
			$id = $this->normalizeIdentifier($item);

			$metaForId = null;
			if (array_key_exists($id, $metadata) || array_key_exists((string) $id, $metadata)) {
				$metaForId = $metadata[$id] ?? $metadata[(string) $id];
			} else {
				throw new \LogicException("No metadata found for id $id");
			}

			$metaForId = $this->metadataTransformer->transform($metaForId)->toArray();
			$this->storage->set($this->key, $id, $metaForId);
		}
		return $this;
	}

	public function unselectMultiple(array $items): static {
		$ids = [];
		foreach ($items as $item) {
			$ids[] = $this->normalizeIdentifier($item);
		}
		$this->storage->remove($this->key, $ids);
		return $this;
	}

	public function selectAll(): static {
		$this->storage->clear($this->key);
		$this->storage->setMode($this->key, SelectionMode::EXCLUDE);
		return $this;
	}

	public function unselectAll(): static {
		$this->storage->clear($this->key);
		$this->storage->setMode($this->key, SelectionMode::INCLUDE);
		return $this;
	}

	/**
	 * Prepne stav položky a vráti nový stav (true = vybraný, false = nevybraný).
	 */
	public function toggle(mixed $item, null|array|object $metadata = null): bool {
		if ($this->isSelected($item)) {
			$this->unselect($item);
			return false;
		}
		$this->select($item, $metadata);
		return true;
	}

	public function getSelectedIdentifiers(): array {
		if ($this->storage->getMode($this->key) === SelectionMode::INCLUDE) {
			$data = $this->storage->getStored($this->key);
		} else {
			$excluded = $this->storage->getStored($this->key);
			$all      = $this->storage->getStored($this->getAllContext());
			$data     = array_diff($all, $excluded);
		}
		foreach ($data as $key => $value) {
			if (is_array($value) &&
				($envelope = StorableEnvelope::tryFrom($value)) &&
				$this->transformer->supportsReverse($envelope)) {
				$data[$key] = $this->transformer->reverseTransform($envelope);
			}
		}
		return $data;
	}

	public function update(mixed $item, object|array|null $metadata = null): static {
		$id = $this->normalizeIdentifier($item);
		if ($metadata === null) {
			return $this; // nothing to update
		}
		$metaArray = $this->metadataTransformer->transform($metadata)->toArray();

		$mode = $this->storage->getMode($this->key);
		if ($mode === SelectionMode::INCLUDE) {
			// Ensure metadata is persisted for this id (and id is included)
			$this->storage->set($this->key, $id, $metaArray);
			return $this;
		}
		// In EXCLUDE mode, metadata can only be stored for explicitly excluded ids
		if ($this->storage->hasIdentifier($this->key, $id)) {
			$this->storage->set($this->key, $id, $metaArray);
		}
		return $this;
	}

	public function getSelected(): array {
		$mode = $this->storage->getMode($this->key);
		if ($mode === SelectionMode::INCLUDE) {
			$ids      = $this->storage->getStored($this->key);
			$hydrated = [];
			foreach ($ids as $id) {
				$meta = $this->storage->getMetadata($this->key, $id);
				if ($meta = StorableEnvelope::tryFrom($meta)) {
					$hydrated[$id] = $this->metadataTransformer->reverseTransform($meta);
				} else {
					$hydrated[$id] = [];
				}
			}
			return $hydrated;
		}
		// EXCLUDE mode
		$excluded = $this->storage->getStored($this->key);
		$all      = $this->storage->getStored($this->getAllContext());
		$selected = array_values(array_diff($all, $excluded));
		$result   = [];
		foreach ($selected as $id) {
			$meta = $this->storage->getMetadata($this->key, $id);
			$envelope = StorableEnvelope::tryFrom($meta);
			if ($envelope !== null) {
				$result[$id] = $this->metadataTransformer->reverseTransform($envelope);
			} else {
				$result[$id] = [];
			}
		}
		return $result;
	}

	public function getMetadata(mixed $item): null|array|object {
		$id   = $this->normalizeIdentifier($item);
		$meta = $this->storage->getMetadata($this->key, $id);
		if ($meta === [] || $meta === null) {
			return null;
		}
		$meta = StorableEnvelope::fromArray($meta);
		if ($this->metadataTransformer->supportsReverse($meta)) {
			return $this->metadataTransformer->reverseTransform($meta);
		}
		return $meta;
	}

	public function rememberAll(array $ids): static {
		$this->storage->setMultiple($this->getAllContext(), $ids);
		return $this;
	}

	public function setMode(SelectionMode $mode): void {
		$this->storage->setMode($this->key, $mode);
	}

	public function getMode(): SelectionMode {
		return $this->storage->getMode($this->key);
	}

	private function getAllContext(): string {
		return $this->key . '__ALL__';
	}

	private function getAllMetaContext(): string {
		return $this->key . '__ALL_META__';
	}

	/**
	 * Normalize an item into a storage identifier (int|string|array).
	 */
	private function normalizeIdentifier(mixed $item): int|string|array {
		if (is_scalar($item)) {
			return $item;
		}
		$transformed = $this->transformer->transform($item);
		if ($transformed instanceof StorableEnvelope) {
			// If transformer can reverse, prefer scalar identifier (e.g., ObjectId -> int)
			if ($this->transformer->supportsReverse($transformed)) {
				$reversed = $this->transformer->reverseTransform($transformed);
				if (is_scalar($reversed)) {
					return $reversed;
				}
			}
			// Fallback to serializable array form
			return $transformed->toArray();
		}
		return $transformed; // assume transformer returns scalar or array
	}

	public function destroy(): static {
		$this->storage->clear($this->key);
		$this->storage->clear($this->getAllContext());
		$this->storage->clear($this->getAllMetaContext());
		return $this;
	}

	public function isSelectedAll(): bool {
		return $this->getMode() == SelectionMode::EXCLUDE && count($this->getSelectedIdentifiers()) == 0;
	}

	public function getTotal(): int {
		return count($this->storage->getStored($this->getAllContext()));
	}


	public function hasSource(string $cacheKey): bool {
		// First ensure we have a marker for this source
		if (!$this->storage->hasIdentifier($this->getAllMetaContext(), $cacheKey)) {
			return false;
		}

		// Check TTL metadata if present
		$meta = $this->storage->getMetadata($this->getAllMetaContext(), $cacheKey);
		if (!is_array($meta) || $meta === []) {
			return true; // no TTL -> considered present
		}

		if (isset($meta['expiresAt'])) {
			$expiresAt = (int) $meta['expiresAt'];
			if ($expiresAt !== 0 && time() >= $expiresAt) {
				// expired
				return false;
			}
		}
		return true;
	}

	public function registerSource(string $cacheKey, mixed $source, int|\DateInterval|null $ttl = null): static {
		// If source already registered, do nothing
		if ($this->hasSource($cacheKey)) {
			return $this;
		}

		// Expecting $source to be an array of scalar identifiers already normalized
		$ids = is_array($source) ? array_values($source) : [];
		if (!empty($ids)) {
			$this->rememberAll($ids);
		}

		// Mark the source as registered in ALL_META context, optionally with TTL metadata
		$meta = null;
		if ($ttl !== null) {
			$expiresAt = 0; // 0 == never expire
			if ($ttl instanceof \DateInterval) {
				$expiresAt = (new \DateTimeImmutable('now'))
					->add($ttl)
					->getTimestamp();
			} else {
				// int seconds (can be zero or negative -> already expired)
				$expiresAt = time() + (int) $ttl;
			}
			$meta = ['expiresAt' => $expiresAt];
		}

		$this->storage->set($this->getAllMetaContext(), $cacheKey, $meta);

		return $this;
	}
}