<?php

namespace Tito10047\PersistentStateBundle\Preference\Service;

/**
 * The primary public API for interacting with user preferences in a specific context.
 * An instance of this interface is "stateful" - it already knows the context (e.g. "user_123").
 */
interface PreferenceInterface
{
    /**
     * Returns the resolved persistent context identifier for this preference instance.
     */
    public function getContext(): string;
	/**
	 * Sets a preference value.
	 * Use this for single key updates.
	 *
	 * Implementation note: The value will be processed by available Serializers
	 * before being sent to storage.
	 *
	 * @param string $key   Unique identifier for the setting.
	 * @param mixed  $value The value to store. Can be scalar, array, or object (if serializer exists).
	 * @return self
	 */
	public function set(string $key, mixed $value): self;

	/**
	 * Sets multiple preferences at once.
	 * Optimized method for bulk updates (e.g., saving a settings form).
	 * Existing keys will be overwritten, new keys added.
	 *
	 * @param array<string, mixed> $values Associative array of key => value.
	 * @return self
	 */
	public function import(array $values): self;

	/**
	 * Retrieves a raw preference value.
	 *
	 * @param string $key     The setting key.
	 * @param mixed  $default Value to return if key is not found.
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed;

	/**
	 * Retrieves a value cast to integer.
	 * Extremely useful for pagination limits, IDs, or counters.
	 *
	 * @param string $key
	 * @param int    $default Default value if key is missing or not a number.
	 * @return int
	 */
	public function getInt(string $key, int $default = 0): int;

	/**
	 * Retrieves a value cast to boolean.
	 * Essential for UI toggles, feature flags, or "remember me" states.
	 * It handles string "false", "0", "off" correctly.
	 *
	 * @param string $key
	 * @param bool   $default
	 * @return bool
	 */
	public function getBool(string $key, bool $default = false): bool;

	/**
	 * Checks if a specific setting exists in the storage.
	 * Note: A key can exist even if its value is null.
	 */
	public function has(string $key): bool;

	/**
	 * Removes a specific setting from storage.
	 * If the key does not exist, the operation is ignored (no error).
	 *
	 * @param string $key
	 * @return self
	 */
	public function remove(string $key): self;

	/**
	 * Returns all stored preferences for the current context.
	 * Useful for debugging or exporting settings.
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array;
}