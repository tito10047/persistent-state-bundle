<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;
use Tito10047\PersistentPreferenceBundle\Storage\SelectionSessionStorage;
use Tito10047\PersistentPreferenceBundle\Tests\Trait\SessionInterfaceTrait;

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

        $this->storage->add($ctx, [1, 2, 3], null);
        $this->storage->add($ctx, [2, 3, 4, '5'], null);

        $this->assertSame([1, 2, 3, 4, '5'], $this->storage->getStored($ctx));
    }

    public function testRemoveRemovesAndReindexes(): void
    {
        $ctx = 'ctx_remove';

        $this->storage->add($ctx, [1, 2, 3, 4], null);
        $this->storage->remove($ctx, [2, 4]);

        $this->assertSame([1, 3], $this->storage->getStored($ctx));
    }

    public function testClearResetsContext(): void
    {
        $ctx = 'ctx_clear';

        $this->storage->add($ctx, [7], null);
        $this->storage->setMode($ctx, SelectionMode::EXCLUDE);

        $this->storage->clear($ctx);

        $this->assertSame([], $this->storage->getStored($ctx));
        $this->assertSame(SelectionMode::INCLUDE, $this->storage->getMode($ctx));
    }

    public function testGetStoredIdentifiersReturnsCurrentIds(): void
    {
        $ctx = 'ctx_ids';
        $this->storage->add($ctx, [9, 10], null);

        $this->assertSame([9, 10], $this->storage->getStored($ctx));
    }

    public function testHasIdentifierUsesLooseComparison(): void
    {
        $ctx = 'ctx_has';
        $this->storage->add($ctx, [5], null);
        
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
        // New API: third parameter is an associative map id => metadata
        $this->storage->add($ctx, [1, 2], [
            1 => $meta,
            2 => $meta,
        ]);

        // Non-overwritten metadata persists per id
        $this->assertSame($meta, $this->storage->getMetadata($ctx, 1));
        $this->assertSame($meta, $this->storage->getMetadata($ctx, 2));

        // getStored returns id=>metadata map for stored ids
        $this->assertSame([
            1 => $meta,
            2 => $meta,
        ], $this->storage->getStoredWithMetadata($ctx));

        // Add another id without metadata, should not override others
        $this->storage->add($ctx, [3], null);
        $this->assertSame([], $this->storage->getMetadata($ctx, 3));

        $this->assertSame([
            1 => $meta,
            2 => $meta,
            3 => [],
        ], $this->storage->getStoredWithMetadata($ctx));
    }

    public function testRemoveAlsoRemovesMetadata(): void
    {
        $ctx = 'ctx_remove_meta';
        $meta = ['x' => 10];
        // New API: provide map for both ids
        $this->storage->add($ctx, [10, 11], [
            10 => $meta,
            11 => $meta,
        ]);
        $this->storage->remove($ctx, [10]);

        $this->assertSame([11], $this->storage->getStored($ctx));
        $this->assertSame([], $this->storage->getMetadata($ctx, 10));
        $this->assertSame([11 => $meta], $this->storage->getStoredWithMetadata($ctx));
    }
}
