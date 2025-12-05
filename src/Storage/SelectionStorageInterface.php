<?php
declare(strict_types=1);


namespace Tito10047\PersistentPreferenceBundle\Storage;



use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;

/**
 * Defines the contract for persisting selection state.
 *
 * This storage is deliberately "dumb". It does not care if IDs are valid
 * or if they exist in the database. It only persists scalar values (int/string).
 * Complex logic regarding objects/UUIDs must be handled by the Manager layer.
 */
interface SelectionStorageInterface
{
	/**
	 * Pridá alebo aktualizuje identifikátory a ich pridružené dáta.
	 *
	 * @param array<int|string> $ids
	 * @param array<string|int, array> $idMetadataMap Mapa: ID => Konvertované pole metadát
	 */
	public function add(string $context, array $ids, ?array $idMetadataMap): void;

	/**
	 * Removes identifiers from the storage for a specific context.
	 *
	 * @param string $context The unique context key
	 * @param array<string|int> $ids List of identifiers to remove
	 */
	public function remove(string $context, array $ids): void;

	/**
	 * Clears all data for the given context and resets the mode to INCLUDE.
	 *
	 * @param string $context The unique context key
	 */
	public function clear(string $context): void;

	/**
	 * Returns the raw identifiers currently stored.
	 *
	 * NOTE: The meaning of these IDs depends on the current SelectionMode.
	 * - If Mode is INCLUDE: These are the selected items.
	 * - If Mode is EXCLUDE: These are the unselected items (exceptions).
	 *
	 * @param string $context The unique context key
	 * @return array<string|int>
	 */
	public function getStored(string $context): array;

	public function getStoredWithMetadata(string $context):array;

	public function getMetadata(string $context, string|int $id):array;

	/**
	 * Checks if a specific identifier is present in the storage.
	 * This checks the raw storage, ignoring the current Mode logic.
	 *
	 * @param string $context The unique context key
	 * @param string|int $id The identifier to check
	 */
	public function hasIdentifier(string $context, string|int $id): bool;

	/**
	 * Sets the selection mode (Include vs Exclude).
	 *
	 * @param string $context The unique context key
	 * @param SelectionMode $mode The target mode
	 */
	public function setMode(string $context, SelectionMode $mode): void;

	/**
	 * Retrieves the current selection mode.
	 *
	 * @param string $context The unique context key
	 */
	public function getMode(string $context): SelectionMode;
}