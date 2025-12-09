<?php

namespace Tito10047\PersistentStateBundle\Preference\Storage;

/**
 * Responsibility: Physical data persistence mechanism.
 *
 * This interface is "dumb". It does not know about objects, business logic, or
 * type casting. It simply stores and retrieves serialized data (scalars/arrays)
 * associated with a specific context string.
 */
interface PreferenceStorageInterface
{
	/**
	 * Retrieves a raw value from storage.
	 *
	 * @param string $context The unique context identifier (e.g., 'user_123').
	 * @param string $key     The setting key.
	 * @param mixed  $default Value to return if the key is missing.
	 * @return mixed
	 */
	public function get(string $context, string $key, mixed $default = null): mixed;

	/**
	 * Persists a single value.
	 * Existing value for the same context and key will be overwritten.
	 *
	 * @param string $context The unique context identifier.
	 * @param string $key     The setting key.
	 * @param mixed  $value   The value to store (already serialized/normalized).
	 */
	public function set(string $context, string $key, mixed $value): void;

	/**
	 * Persists multiple values at once.
	 * PERFORMANCE CRITICAL: This allows optimized batch writes (e.g., single SQL transaction).
	 *
	 * @param string               $context The unique context identifier.
	 * @param array<string, mixed> $values  Key-value pairs to store.
	 */
	public function setMultiple(string $context, array $values): void;

	/**
	 * Removes a specific key from storage.
	 *
	 * @param string $context
	 * @param string $key
	 */
	public function remove(string $context, string $key): void;

	/**
	 * Checks if a key exists in storage (even if the value is null).
	 *
	 * @param string $context
	 * @param string $key
	 * @return bool
	 */
	public function has(string $context, string $key): bool;

	/**
	 * Retrieves all stored key-value pairs for the given context.
	 *
	 * @param string $context
	 * @return array<string, mixed>
	 */
	public function all(string $context): array;
}