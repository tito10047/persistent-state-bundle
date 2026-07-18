<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Tests\Unit\Selection\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionDoctrineStorage;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionEntityInterface;

class SelectionDoctrineStorageTest extends TestCase
{
    private SelectionEntityInterface $entity;
    private SelectionDoctrineStorage $storage;

    protected function setUp(): void
    {
        $this->entity = new class implements SelectionEntityInterface {
            private array $identifiers = [];
            private array $metadata = [];
            private SelectionMode $mode = SelectionMode::INCLUDE;

            public function getContext(): string
            {
                return 'ctx';
            }

            public function setContext(string $context): self
            {
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
        };

        $entity = $this->entity;
        $entityClass = get_class($entity);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findOneBy')->willReturn($entity);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        $this->storage = new SelectionDoctrineStorage($em, $entityClass);
    }

    public function testSetAllowsDuplicateIdentifiers(): void
    {
        $this->storage->set('ctx', 1, null);
        $this->storage->set('ctx', 1, null);

        $this->assertSame([1, 1], $this->storage->getStored('ctx'));
    }

    public function testSetAppendsSingleIdentifier(): void
    {
        $this->storage->set('ctx', 42, null);

        $this->assertSame([42], $this->storage->getStored('ctx'));
    }

    public function testSetMultipleDeduplicatesWithinBatch(): void
    {
        $this->storage->setMultiple('ctx', [1, 2, 2, 3]);

        $this->assertSame([1, 2, 3], $this->storage->getStored('ctx'));
    }
}
