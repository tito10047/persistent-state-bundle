<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Service;

use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;

final class TraceableSelection implements SelectionInterface, HasModeInterface, RegisterSelectionInterface
{
    public function __construct(
        private readonly string $managerName,
        private readonly string $namespace,
        private readonly SelectionInterface $inner,
        private readonly PreferenceDataCollector $collector,
    ) {
    }

    public function destroy(): static
    {
        $this->inner->destroy();
        $this->notify();

        return $this;
    }

    public function isSelected(mixed $item): bool
    {
        $result = $this->inner->isSelected($item);
        $this->notify();

        return $result;
    }

    public function isSelectedAll(): bool
    {
        $result = $this->inner->isSelectedAll();
        $this->notify();

        return $result;
    }

    public function select(mixed $item, array|object|null $metadata = null): static
    {
        $this->inner->select($item, $metadata);
        $this->notify();

        return $this;
    }

    public function update(mixed $item, array|object|null $metadata = null): static
    {
        $this->inner->update($item, $metadata);
        $this->notify();

        return $this;
    }

    public function unselect(mixed $item): static
    {
        $this->inner->unselect($item);
        $this->notify();

        return $this;
    }

    public function toggle(mixed $item, array|object|null $metadata = null): bool
    {
        $result = $this->inner->toggle($item, $metadata);
        $this->notify();

        return $result;
    }

    public function selectMultiple(array $items, ?array $metadata = null): static
    {
        $this->inner->selectMultiple($items, $metadata);
        $this->notify();

        return $this;
    }

    public function unselectMultiple(array $items): static
    {
        $this->inner->unselectMultiple($items);
        $this->notify();

        return $this;
    }

    public function selectAll(): static
    {
        $this->inner->selectAll();
        $this->notify();

        return $this;
    }

    public function unselectAll(): static
    {
        $this->inner->unselectAll();
        $this->notify();

        return $this;
    }

    public function getSelectedIdentifiers(): array
    {
        $result = $this->inner->getSelectedIdentifiers();
        $this->notify();

        return $result;
    }

    public function getSelected(): array
    {
        $result = $this->inner->getSelected();
        $this->notify();

        return $result;
    }

    public function getMetadata(mixed $item): array|object|null
    {
        return $this->inner->getMetadata($item);
    }

    public function getTotal(): int
    {
        return $this->inner->getTotal();
    }

    public function getIdentifier(mixed $value): int|string
    {
        return $this->inner->getIdentifier($value);
    }

    public function setMode(SelectionMode $mode): void
    {
        if ($this->inner instanceof HasModeInterface) {
            $this->inner->setMode($mode);
            $this->notify();
        }
    }

    public function getMode(): SelectionMode
    {
        if ($this->inner instanceof HasModeInterface) {
            return $this->inner->getMode();
        }

        return SelectionMode::INCLUDE;
    }

    public function registerSource(string $cacheKey, mixed $source, int|\DateInterval|null $ttl = null): static
    {
        if ($this->inner instanceof RegisterSelectionInterface) {
            $this->inner->registerSource($cacheKey, $source, $ttl);
        }

        return $this;
    }

    public function hasSource(string $cacheKey): bool
    {
        if ($this->inner instanceof RegisterSelectionInterface) {
            return $this->inner->hasSource($cacheKey);
        }

        return false;
    }

    private function notify(): void
    {
        $this->collector->onSelectionChanged(
            $this->managerName,
            $this->namespace,
            $this->inner->getSelectedIdentifiers(),
            $this->getMode(),
            $this->inner->getTotal()
        );
    }
}
