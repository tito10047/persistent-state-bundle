<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Preference\Storage;

/**
 * Interface that must be implemented by the Doctrine Entity used for storage.
 *
 * This allows the bundle's DoctrineStorage to interact with your specific entity
 * (e.g., App\Entity\UserPreference) without knowing its class name in advance.
 */
interface PreferenceEntityInterface
{
    /**
     * Gets the context identifier (e.g., "user_123").
     */
    public function getContext(): string;

    /**
     * Sets the context identifier.
     */
    public function setContext(string $context): self;

    /**
     * Gets the setting key (e.g., "theme").
     */
    public function getKey(): string;

    /**
     * Sets the setting key.
     */
    public function setKey(string $key): self;

    /**
     * Gets the raw value (usually from a JSON column).
     */
    public function getValue(): mixed;

    /**
     * Sets the value to be stored.
     * The value should be serializable to JSON (array, string, int, bool, null).
     */
    public function setValue(mixed $value): self;
}
