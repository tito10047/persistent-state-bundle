<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Service;

interface SelectionInterface
{
    /**
     * Destroys all data for this selection namespace and resets to default state.
     * Typically clears identifiers and sets mode back to INCLUDE.
     */
    public function destroy(): static;

    /**
     * Checks whether a given item is currently considered selected.
     * Accepts an entity/object or a raw identifier.
     */
    public function isSelected(mixed $item): bool;

    /**
     * Returns true if the selection is in "select all" state.
     * This is usually when mode is EXCLUDE and no exclusions are stored.
     */
    public function isSelectedAll(): bool;

    /**
     * Marks an item as selected, optionally attaching metadata.
     * In EXCLUDE mode this removes the item from the exclusion list.
     */
    public function select(mixed $item, array|object|null $metadata = null): static;

    /**
     * Updates metadata for a previously selected item.
     * If the item was not selected yet, implementations may select it.
     */
    public function update(mixed $item, array|object|null $metadata = null): static;

    /**
     * Marks an item as unselected.
     * In EXCLUDE mode this adds the item to the exclusion list.
     */
    public function unselect(mixed $item): static;

    /**
     * Toggles the selection state of an item and returns the new state.
     * Optional metadata is attached when toggling into the selected state.
     */
    public function toggle(mixed $item, array|object|null $metadata = null): bool;

    /**
     * Selects many items at once.
     * When providing metadata, pass an associative map keyed by normalized ID.
     * Example: [101 => ['qty' => 5], 102 => ['qty' => 1]].
     *
     * @param array                                     $items    List of items (entities/objects or identifiers)
     * @param array<int|string, array|object|null>|null $metadata Map of id => metadata
     */
    public function selectMultiple(array $items, ?array $metadata = null): static;

    /**
     * Unselects many items at once.
     */
    public function unselectMultiple(array $items): static;

    /**
     * Puts the selection into "select all" state (EXCLUDE mode with empty exclusions).
     */
    public function selectAll(): static;

    /**
     * Clears the selection and returns to INCLUDE mode (nothing selected).
     */
    public function unselectAll(): static;

    /**
     * Returns a list of normalized identifiers representing the raw storage.
     * Note: In INCLUDE mode these are selected; in EXCLUDE mode these are exclusions.
     *
     * @return array<string|int>
     */
    public function getSelectedIdentifiers(): array;

    /**
     * Returns a map of id => metadata for currently stored items.
     * Implementations may hydrate metadata into objects.
     *
     * @template T of object
     *
     * @phpstan-param class-string<T>|null $metadataClass
     *
     * @return array<string|int, array|object>
     *
     * @phpstan-return array<string|int, T>|array<string|int, array|object>
     */
    public function getSelected(): array;

    /**
     * Returns metadata for a specific item, if present, optionally hydrated.
     *
     * @template T of object
     *
     * @phpstan-param class-string<T>|null $metadataClass
     *
     * @return T|array|null
     *
     * @phpstan-return T|array
     */
    public function getMetadata(mixed $item): array|object|null;

    /**
     * Returns the total number of items in the registered source.
     * Useful for UI displaying "X of Y selected".
     */
    public function getTotal(): int;

    public function getIdentifier(mixed $value): int|string;
}
