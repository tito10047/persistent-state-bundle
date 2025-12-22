<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Preference\Storage;

use Doctrine\ORM\Mapping as ORM;

/**
 * Base class for preference storage.
 * Extend this entity in your application to define the table and ID strategy.
 */
#[ORM\MappedSuperclass]
abstract class BasePreference implements PreferenceEntityInterface
{
    /**
     * Context identifier (e.g., "user_123").
     */
    #[ORM\Column(length: 190)]
    protected ?string $context = null;

    /**
     * Setting key (e.g., "theme").
     * Mapped as "name" to avoid SQL reserved keyword issues.
     */
    #[ORM\Column(name: 'name', length: 190)]
    protected ?string $key = null;

    /**
     * The stored value (JSON).
     */
    #[ORM\Column(type: 'json', nullable: true)]
    protected mixed $value = null;

    public function getContext(): string
    {
        return (string) $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getKey(): string
    {
        return (string) $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }
}
