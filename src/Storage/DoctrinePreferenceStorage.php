<?php

namespace Tito10047\PersistentStateBundle\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceEntityInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;

final class DoctrinePreferenceStorage implements PreferenceStorageInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly string $entityClass,
    ) {
    }

    public function get(string $context, string $key, mixed $default = null): mixed
    {
        $entity = $this->findOne($context, $key);

        return $entity?->getValue() ?? $default;
    }

    public function set(string $context, string $key, mixed $value): void
    {
        $entity = $this->findOne($context, $key);
        if (!$entity) {
            /** @var class-string<PreferenceEntityInterface> $cls */
            $cls = $this->entityClass;
            /** @var PreferenceEntityInterface $entity */
            $entity = new $cls();
            $entity->setContext($context)->setKey($key);
            $this->em->persist($entity);
        }

        $entity->setValue($value);
        $this->em->flush();
    }

    public function setMultiple(string $context, array $values): void
    {
        if ([] === $values) {
            return;
        }

        $this->em->wrapInTransaction(function () use ($context, $values): void {
            foreach ($values as $key => $value) {
                $this->set($context, (string) $key, $value);
            }
        });
    }

    public function remove(string $context, string $key): void
    {
        $entity = $this->findOne($context, $key);
        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
        }
    }

    public function has(string $context, string $key): bool
    {
        return (bool) $this->findOne($context, $key);
    }

    public function all(string $context): array
    {
        $repo = $this->em->getRepository($this->entityClass);
        /** @var PreferenceEntityInterface[] $rows */
        $rows = $repo->findBy(['context' => $context]);
        $out = [];
        foreach ($rows as $row) {
            $out[$row->getKey()] = $row->getValue();
        }

        return $out;
    }

    private function findOne(string $context, string $key): ?PreferenceEntityInterface
    {
        $repo = $this->em->getRepository($this->entityClass);
        /** @var object|null $entity */
        $entity = $repo->findOneBy(['context' => $context, 'key' => $key]);
        if (null === $entity) {
            return null;
        }
        if (!$entity instanceof PreferenceEntityInterface) {
            throw new \RuntimeException(sprintf('Entity %s must implement %s', get_debug_type($entity), PreferenceEntityInterface::class));
        }

        return $entity;
    }
}
