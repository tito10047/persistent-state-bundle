<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Selection\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Exception\RuntimeException;

final class SelectionDoctrineStorage implements SelectionStorageInterface
{
    /** @var array<string, SelectionEntityInterface|null> */
    private array $cache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $entityClass,
    ) {
    }

    public function set(string $context, int|string|array $identifier, ?array $metadata): void
    {
        $entity = $this->findOrCreate($context);
        $ids = $entity->getIdentifiers();
        $meta = $entity->getMetadata();

        $ids[] = $identifier;
        $entity->setIdentifiers(array_values($ids));

        if (null !== $metadata) {
            $key = $this->metaKey($identifier);
            $meta[$key] = $metadata;
            $entity->setMetadata($meta);
        }
    }

    public function setMultiple(string $context, array $identifiers): void
    {
        $entity = $this->findOrCreate($context);
        $ids = $entity->getIdentifiers();

        $changed = false;
        foreach ($identifiers as $id) {
            if (!in_array($id, $ids, false)) {
                $ids[] = $id;
                $changed = true;
            }
        }

        if ($changed) {
            $entity->setIdentifiers(array_values($ids));
        }
    }

    public function remove(string $context, array $identifier): void
    {
        $this->removeMultiple($context, $identifier);
    }

    public function removeMultiple(string $context, array $identifiers): void
    {
        $entity = $this->findOne($context);
        if (null === $entity) {
            return;
        }

        $ids = $entity->getIdentifiers();
        $meta = $entity->getMetadata();

        if ([] === $ids) {
            return;
        }

        $remaining = [];
        $changed = false;
        foreach ($ids as $existing) {
            if (!in_array($existing, $identifiers, false)) {
                $remaining[] = $existing;
            } else {
                unset($meta[$this->metaKey($existing)]);
                $changed = true;
            }
        }

        if ($changed) {
            $entity->setIdentifiers(array_values($remaining));
            $entity->setMetadata($meta);
        }
    }

    public function clear(string $context): void
    {
        $entity = $this->findOne($context);
        if ($entity) {
            $entity->setIdentifiers([]);
            $entity->setMetadata([]);
            $entity->setMode(SelectionMode::INCLUDE);
        }
    }

    public function getStored(string $context): array
    {
        $entity = $this->findOne($context);

        return $entity?->getIdentifiers() ?? [];
    }

    public function getMetadata(string $context, string|int|array $identifiers): array
    {
        $entity = $this->findOne($context);
        if (null === $entity) {
            return [];
        }

        $meta = $entity->getMetadata();
        $key = $this->metaKey($identifiers);

        return $meta[$key] ?? [];
    }

    public function hasIdentifier(string $context, string|int|array $identifiers): bool
    {
        $ids = $this->getStored($context);

        return in_array($identifiers, $ids, false);
    }

    public function setMode(string $context, SelectionMode $mode): void
    {
        $entity = $this->findOrCreate($context);
        $entity->setMode($mode);
    }

    public function getMode(string $context): SelectionMode
    {
        $entity = $this->findOne($context);

        return $entity?->getMode() ?? SelectionMode::INCLUDE;
    }

    private function findOrCreate(string $context): SelectionEntityInterface
    {
        $entity = $this->findOne($context);
        if (!$entity) {
            /** @var class-string<SelectionEntityInterface> $cls */
            $cls = $this->entityClass;
            /** @var SelectionEntityInterface $entity */
            $entity = new $cls();
            $entity->setContext($context);
            $this->em->persist($entity);
            $this->cache[$context] = $entity;
        }

        return $entity;
    }

    private function findOne(string $context): ?SelectionEntityInterface
    {
        if (array_key_exists($context, $this->cache)) {
            return $this->cache[$context];
        }

        $repo = $this->em->getRepository($this->entityClass);
        /** @var object|null $entity */
        $entity = $repo->findOneBy(['context' => $context]);

        if (null !== $entity && !$entity instanceof SelectionEntityInterface) {
            throw new RuntimeException(sprintf('Entity %s must implement %s', get_debug_type($entity), SelectionEntityInterface::class));
        }

        return $this->cache[$context] = $entity;
    }

    private function metaKey(int|array|string $identifier): string
    {
        if (is_array($identifier)) {
            return 'arr:'.json_encode($identifier, JSON_THROW_ON_ERROR);
        }

        return (string) $identifier;
    }
}
