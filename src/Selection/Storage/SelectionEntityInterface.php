<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Storage;

use Tito10047\PersistentStateBundle\Enum\SelectionMode;

/**
 * Interface that must be implemented by the Doctrine Entity used for selection storage.
 */
interface SelectionEntityInterface
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
     * Gets the stored identifiers.
     */
    public function getIdentifiers(): array;

    /**
     * Sets the identifiers to be stored.
     */
    public function setIdentifiers(array $identifiers): self;

    /**
     * Gets the stored metadata.
     */
    public function getMetadata(): array;

    /**
     * Sets the metadata to be stored.
     */
    public function setMetadata(array $metadata): self;

    /**
     * Gets the selection mode.
     */
    public function getMode(): SelectionMode;

    /**
     * Sets the selection mode.
     */
    public function setMode(SelectionMode $mode): self;
}
