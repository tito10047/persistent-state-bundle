<?php

namespace Tito10047\PersistentStateBundle\Tests\Unit\Selection\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Selection\Storage\SelectionSessionStorage;
use Tito10047\PersistentStateBundle\Tests\Trait\SessionInterfaceTrait;

class SelectionSessionStorageTest extends TestCase
{
	use SessionInterfaceTrait;

    private SelectionSessionStorage $storage;


    protected function setUp(): void
    {

        // Mock RequestStack to return our fake session
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($this->mockSessionInterface());

        $this->storage = new SelectionSessionStorage($requestStack);
    }

    public function testAddMergesAndDeduplicates(): void
    {
        $ctx = 'ctx_add';

        $this->storage->setMultiple($ctx, [1, 2, 3]);
        $this->storage->setMultiple($ctx, [2, 3, 4, '5']);

        $this->assertSame([1, 2, 3, 4, '5'], $this->storage->getStored($ctx));
    }

    public function testRemoveRemovesAndReindexes(): void
    {
        $ctx = 'ctx_remove';

        $this->storage->setMultiple($ctx, [1, 2, 3, 4]);
        $this->storage->remove($ctx, [2, 4]);

        $this->assertSame([1, 3], $this->storage->getStored($ctx));
    }

    public function testClearResetsContext(): void
    {
        $ctx = 'ctx_clear';

        $this->storage->setMultiple($ctx, [7]);
        $this->storage->setMode($ctx, SelectionMode::EXCLUDE);

        $this->storage->clear($ctx);

        $this->assertSame([], $this->storage->getStored($ctx));
        $this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
    }

    public function testGetStoredIdentifiersReturnsCurrentIds(): void
    {
        $ctx = 'ctx_ids';
        $this->storage->setMultiple($ctx, [9, 10]);

        $this->assertSame([9, 10], $this->storage->getStored($ctx));
    }

    public function testHasIdentifierUsesLooseComparison(): void
    {
        $ctx = 'ctx_has';
        $this->storage->setMultiple($ctx, [5]);
        
        // uses in_array with loose comparison in the implementation
        $this->assertTrue($this->storage->hasIdentifier($ctx, '5'));
        $this->assertTrue($this->storage->hasIdentifier($ctx, 5));
        $this->assertFalse($this->storage->hasIdentifier($ctx, '6'));

        // metadata not set returns empty array
        $this->assertSame([], $this->storage->getMetadata($ctx, 5));
    }

    public function testDefaultModeIsInclude(): void
    {
        $ctx = 'ctx_default_mode';
        $this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
    }

    public function testSetAndGetModePersistsValue(): void
    {
        $ctx = 'ctx_mode';
        $this->storage->setMode($ctx, SelectionMode::EXCLUDE);
        $this->assertSame(SelectionMode::EXCLUDE, $this->storage->getMode($ctx));
    }

    public function testAddWithMetadataAndGetStored(): void
    {
        $ctx = 'ctx_meta';

        $meta = ['foo' => 'bar', 'n' => 1];
        // New API: set per id with optional metadata
        $this->storage->set($ctx, 1, $meta);
        $this->storage->set($ctx, 2, $meta);

        // Non-overwritten metadata persists per id
        $this->assertSame($meta, $this->storage->getMetadata($ctx, 1));
        $this->assertSame($meta, $this->storage->getMetadata($ctx, 2));

        // Reconstruct map id=>metadata from API
        $map = [];
        foreach ($this->storage->getStored($ctx) as $id) {
            $map[$id] = $this->storage->getMetadata($ctx, $id);
        }
        $this->assertSame([
            1 => $meta,
            2 => $meta,
        ], $map);

        // Add another id without metadata, should not override others
        $this->storage->set($ctx, 3, null);
        $this->assertSame([], $this->storage->getMetadata($ctx, 3));

        $map = [];
        foreach ($this->storage->getStored($ctx) as $id) {
            $map[$id] = $this->storage->getMetadata($ctx, $id);
        }
        $this->assertSame([
            1 => $meta,
            2 => $meta,
            3 => [],
        ], $map);
    }

    public function testRemoveAlsoRemovesMetadata(): void
    {
        $ctx = 'ctx_remove_meta';
        $meta = ['x' => 10];
        $this->storage->set($ctx, 10, $meta);
        $this->storage->set($ctx, 11, $meta);
        $this->storage->remove($ctx, [10]);

        $this->assertSame([11], $this->storage->getStored($ctx));
        $this->assertSame([], $this->storage->getMetadata($ctx, 10));
        $map = [];
        foreach ($this->storage->getStored($ctx) as $id) {
            $map[$id] = $this->storage->getMetadata($ctx, $id);
        }
        $this->assertSame([11 => $meta], $map);
    }
}
