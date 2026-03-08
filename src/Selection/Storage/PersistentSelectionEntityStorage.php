<?php

namespace Tito10047\PersistentStateBundle\Selection\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Entity\Rds\Admin;
use Entity\Rds\PersistentSelection;
use Entity\Rds\RdsUsers;
use Symfony\Bundle\SecurityBundle\Security;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;

class PersistentSelectionEntityStorage implements SelectionStorageInterface {

    private array $selections = [];

    public function __construct(
        private readonly EntityManagerInterface $emRds
    ) { }


    private const META_MODE_KEY = '__mode';

    private function getEntity(string $context): PersistentSelection {
        if (array_key_exists($context, $this->selections) ){
            return $this->selections[$context];
        }
        $entity = $this->emRds->getRepository(PersistentSelection::class)->findOneBy(["context" => $context]);
        if (!$entity) {
            $entity = new PersistentSelection();
            $entity->setContext($context);
            $this->emRds->persist($entity);
        }
        return $this->selections[$context] = $entity;
    }

    private function encodeId(array|int|string $identifier): string {
        if (is_array($identifier)) {
            return 'arr:' . json_encode($identifier, JSON_THROW_ON_ERROR);
        }
        return (string) $identifier;
    }

    private function save(PersistentSelection $entity): void {
        $this->emRds->flush();
    }

    public function set(string $context, array|int|string $identifier, ?array $metadata): void {
        $entity = $this->getEntity($context);
        $ids    = $entity->getIds() ?? [];
        $meta   = $entity->getMetadata() ?? [];

        $encoded = $this->encodeId($identifier);
        if (!in_array($encoded, $ids, true)) {
            $ids[] = $encoded;
        }
        if ($metadata !== null) {
            $meta[$encoded] = $metadata;
        }

        $entity->setIds(array_values($ids));
        $entity->setMetadata($meta);
        $this->save($entity);
    }

    public function setMultiple(string $context, array $identifiers): void {
        $entity = $this->getEntity($context);
        $ids    = $entity->getIds() ?? [];
        $meta   = $entity->getMetadata() ?? [];

        foreach ($identifiers as $id) {
            $encoded = $this->encodeId($id);
            if (!in_array($encoded, $ids, true)) {
                $ids[] = $encoded;
            }
        }

        $entity->setIds(array_values($ids));
        $entity->setMetadata($meta);
        $this->save($entity);
    }

    public function remove(string $context, array $identifier): void {
        $this->removeMultiple($context, $identifier);
    }

    public function removeMultiple(string $context, array $identifiers): void {
        $entity = $this->getEntity($context);
        $ids    = $entity->getIds() ?? [];
        $meta   = $entity->getMetadata() ?? [];

        if ($ids === []) {
            return;
        }

        $toRemove = array_map(fn($id) => $this->encodeId($id), $identifiers);
        $remaining = [];
        foreach ($ids as $existing) {
            if (!in_array($existing, $toRemove, true)) {
                $remaining[] = $existing;
            } else {
                unset($meta[$existing]);
            }
        }

        $entity->setIds(array_values($remaining));
        $entity->setMetadata($meta);
        $this->save($entity);
    }

    public function clear(string $context): void {
        $entity = $this->getEntity($context);
        $entity->setIds([]);
        $entity->setMetadata([
            self::META_MODE_KEY => SelectionMode::INCLUDE->value,
        ]);
        $this->save($entity);
    }

    public function getStored(string $context): array {
        $entity = $this->getEntity($context);
        return $entity->getIds() ?? [];
    }

    public function getMetadata(string $context, array|int|string $identifiers): array {
        $entity = $this->getEntity($context);
        $meta   = $entity->getMetadata() ?? [];
        $key    = $this->encodeId($identifiers);
        return $meta[$key] ?? [];
    }

    public function hasIdentifier(string $context, array|int|string $identifiers): bool {
        $entity = $this->getEntity($context);
        $ids    = $entity->getIds() ?? [];
        $encoded = $this->encodeId($identifiers);
        return in_array($encoded, $ids, true);
    }

    public function setMode(string $context, SelectionMode $mode): void {
        $entity = $this->getEntity($context);
        $meta   = $entity->getMetadata() ?? [];
        $meta[self::META_MODE_KEY] = $mode->value;
        $entity->setMetadata($meta);
        $this->save($entity);
    }

    public function getMode(string $context): SelectionMode {
        $entity = $this->getEntity($context);
        $meta   = $entity->getMetadata() ?? [];
        $raw    = $meta[self::META_MODE_KEY] ?? null;
        if ($raw instanceof SelectionMode) {
            return $raw;
        }
        if (is_string($raw)) {
            return SelectionMode::tryFrom($raw) ?? SelectionMode::INCLUDE;
        }
        return SelectionMode::INCLUDE;
    }
}
