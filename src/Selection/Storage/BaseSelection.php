<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Storage;

use Doctrine\ORM\Mapping as ORM;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;

/**
 * Base class for selection storage.
 * Extend this entity in your application to define the table and ID strategy.
 */
#[ORM\MappedSuperclass]
abstract class BaseSelection implements SelectionEntityInterface
{
    /**
     * Context identifier (e.g., "user_123").
     */
    #[ORM\Column(length: 190, unique: true)]
    protected ?string $context = null;

    /**
     * The stored identifiers (JSON).
     */
    #[ORM\Column(type: 'json')]
    protected array $identifiers = [];

    /**
     * The stored metadata (JSON).
     */
    #[ORM\Column(type: 'json')]
    protected array $metadata = [];

    /**
     * Selection mode.
     */
    #[ORM\Column(type: 'string', length: 20, enumType: SelectionMode::class)]
    protected SelectionMode $mode = SelectionMode::INCLUDE;

    public function getContext(): string
    {
        return (string) $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public function setIdentifiers(array $identifiers): self
    {
        $this->identifiers = $identifiers;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getMode(): SelectionMode
    {
        return $this->mode;
    }

    public function setMode(SelectionMode $mode): self
    {
        $this->mode = $mode;

        return $this;
    }
}
