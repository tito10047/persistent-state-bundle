<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Selection\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use stdClass;
use Tito10047\PersistentPreferenceBundle\Enum\SelectionMode;
use Tito10047\PersistentPreferenceBundle\Selection\Service\Selection;
use Tito10047\PersistentPreferenceBundle\Selection\Storage\SelectionSessionStorage;
use Tito10047\PersistentPreferenceBundle\Tests\Trait\SessionInterfaceTrait;
use Tito10047\PersistentPreferenceBundle\Transformer\ArrayValueTransformer;
use Tito10047\PersistentPreferenceBundle\Transformer\ScalarValueTransformer;
use Tito10047\PersistentPreferenceBundle\Transformer\SerializableObjectTransformer;
use Tito10047\PersistentPreferenceBundle\Transformer\ObjectIdValueTransformer;
use Tito10047\PersistentPreferenceBundle\Transformer\ValueTransformerInterface;

class SelectionInterfaceTest  extends TestCase{

	use SessionInterfaceTrait;
	private ValueTransformerInterface $normalizer;
	private SelectionSessionStorage $storage;
	private ValueTransformerInterface $converter;

	protected function setUp(): void
	{
		$requestStack = $this->createMock(RequestStack::class);
		$requestStack->method('getSession')->willReturn($this->mockSessionInterface());

		$this->storage = new SelectionSessionStorage($requestStack);

		$this->normalizer = new ScalarValueTransformer();
		$this->converter = new ArrayValueTransformer();
	}

	public function testGetSelectedIdentifiersWithExcludeModeRemembersAll():void {
		$selection = new Selection('test', $this->storage, $this->normalizer, $this->converter);
		$selection->rememberAll([1, 2, 3]);
		$selection->setMode(SelectionMode::EXCLUDE);

		/** @var SelectionInterface $selection */
		$ids = $selection->getSelectedIdentifiers();

		$this->assertSame([1, 2, 3], $ids);
	}

	public function testSelectAndIsSelected(): void
	{
		$selection = new Selection('ctx_select',  $this->storage, $this->normalizer, $this->converter);

		// selection methods should be fluent
		$chain = $selection->select(5)->select(6);
		$this->assertSame($selection, $chain);

		$this->assertTrue($selection->isSelected(5));
		$this->assertTrue($selection->isSelected(6));
		$this->assertFalse($selection->isSelected(7));
	}

	public function testUnselect(): void
	{
		$selection = new Selection('ctx_unselect',  $this->storage, $this->normalizer, $this->converter);
		$selection->select(10)->select(11);

		$this->assertTrue($selection->isSelected(10));
		$this->assertTrue($selection->isSelected(11));

		$chain = $selection->unselect(10);
		$this->assertSame($selection, $chain);

		$this->assertFalse($selection->isSelected(10));
		$this->assertTrue($selection->isSelected(11));
	}

	public function testSelectMultiple(): void
	{
		$selection = new Selection('ctx_multi', $this->storage, $this->normalizer, $this->converter);
		// intentionally pass mixed types, storage supports loose comparisons
		$selection->selectMultiple([1, '2', 3]);

		/** @var SelectionInterface $selection */
		$this->assertSame([1, '2', 3], $selection->getSelectedIdentifiers());
	}

	public function testClearSelected(): void
	{
		$selection = new Selection('ctx_clear', $this->storage, $this->normalizer, $this->converter);
		$selection->select(1)->select(2);
		$this->assertSame([1, 2], $selection->getSelectedIdentifiers());

		$chain = $selection->unselectAll();
		$this->assertSame($selection, $chain);
		$this->assertSame([], $selection->getSelectedIdentifiers());
		$this->assertFalse($selection->isSelected(1));
	}

	public function testDestroyClearsAllContexts(): void
	{
		$selection = new Selection('ctx_destroy', $this->storage, $this->normalizer, $this->converter);
		// Setup some state in both primary and __ALL__ contexts (using helper methods only for setup)
		$selection->rememberAll([100, 200, 300]);
		$selection->select(200)->select(400);

		// Sanity before destroy
		$this->assertNotSame([], $selection->getSelectedIdentifiers());

		$chain = $selection->destroy();
		$this->assertSame($selection, $chain);

		// After destroy, include-mode default with no identifiers
		$this->assertSame([], $selection->getSelectedIdentifiers());
		$this->assertFalse($selection->isSelected(200));
	}

 public function testGetSelectedIdentifiersInIncludeMode(): void
 {
     $selection = new Selection('ctx_ids', $this->storage, $this->normalizer, $this->converter);
     $selection->select(1)->select(2)->select(2); // duplicate should be deduped by storage

     /** @var SelectionInterface $selection */
     $this->assertSame([1, 2], $selection->getSelectedIdentifiers());
 }

 public function testSelectWithArrayMetadataAndRetrieve(): void
 {
     $selection = new Selection('ctx_meta_array', $this->storage, $this->normalizer, $this->converter);
     $meta = ['foo' => 'bar', 'n' => 42];
     $selection->select(7, $meta);

     // getMetadata returns array when no class is requested
     $this->assertSame($meta, $selection->getMetadata(7));

     // getSelected returns id=>metadata map
     $this->assertSame([7 => $meta], $selection->getSelected());
 }

 public function testSelectWithObjectMetadataAndHydration(): void
 {
     $selection = new Selection('ctx_meta_object', $this->storage, $this->normalizer, new SerializableObjectTransformer());
     $obj = new stdClass();
     $obj->foo = 'baz';
     $obj->num = 13;

     $selection->select(8, $obj);


     // With class, we get hydrated stdClass
     $hydrated = $selection->getMetadata(8);
     $this->assertInstanceOf(stdClass::class, $hydrated);
     $this->assertSame('baz', $hydrated->foo);
     $this->assertSame(13, $hydrated->num);
 }

 public function testSelectMultipleWithPerIdMetadata(): void
 {
     $selection = new Selection('ctx_meta_multi',  $this->storage, $this->normalizer, $this->converter);
     $items = [1, 2, 3];
     $metadataMap = [
         1 => ['x' => 1],
         2 => ['x' => 2],
		 3 => ['x' => 0]
         // 3 will fallback to sharing same array if provided, here we provide a default
     ];
     $selection->selectMultiple($items, $metadataMap);

     $selected = $selection->getSelected();
     $this->assertSame(['x' => 1], $selected[1]);
     $this->assertSame(['x' => 2], $selected[2]);
     $this->assertSame(['x' => 0], $selected[3]);
 }

 public function testUpdateMetadataOverwrites(): void
 {
     $selection = new Selection('ctx_update', $this->storage, $this->normalizer, $this->converter);
     $selection->select(55, ['a' => 1]);

     $selection->update(55, ['a' => 2, 'b' => 3]);
     $this->assertSame(['a' => 2, 'b' => 3], $selection->getMetadata(55));
 }


 public function testHasSelectionWithCacheKeyAndTtl(): void
 {
     $selection = new Selection('ctx_meta', $this->storage, $this->normalizer, $this->converter);

     // Initially no selection cached
     $this->assertFalse($selection->hasSource('abc'));

     // Set with cache key without ttl
     $selection->registerSource("abc", [10, 20]);
     $this->assertTrue($selection->hasSource('abc'));
     $this->assertFalse($selection->hasSource('other'));

 }

 public function testHasSourceExpiresWithIntTtl(): void
 {
     $selection = new Selection('ctx_meta_ttl_int', $this->storage, $this->normalizer, $this->converter);

     $this->assertFalse($selection->hasSource('src1'));

     // ttl 1 second
     $selection->registerSource('src1', [1, 2, 3], 1);
     $this->assertTrue($selection->hasSource('src1'));
    
    // wait for expiry
    sleep(2);
     $this->assertFalse($selection->hasSource('src1'));
 }

 public function testHasSourceExpiresWithDateIntervalTtl(): void
 {
     $selection = new Selection('ctx_meta_ttl_interval',  $this->storage, $this->normalizer, $this->converter);

     $this->assertFalse($selection->hasSource('src2'));

     $interval = new \DateInterval('PT1S');
     $selection->registerSource('src2', [4, 5], $interval);
     $this->assertTrue($selection->hasSource('src2'));

        sleep(2);
        $this->assertFalse($selection->hasSource('src2'));
    }
    public function testToggleInIncludeModeWithMetadata(): void
    {
        $selection = new Selection('ctx_toggle_include', $this->storage, $this->normalizer, $this->converter);

        // Initially not selected
        $this->assertFalse($selection->isSelected(101));

        // Toggle to select with metadata
        $newState = $selection->toggle(101, ['qty' => 5]);
        $this->assertTrue($newState);
        $this->assertTrue($selection->isSelected(101));
        $this->assertSame(['qty' => 5], $selection->getMetadata(101));

        // Toggle again to unselect
        $newState = $selection->toggle(101);
        $this->assertFalse($newState);
        $this->assertFalse($selection->isSelected(101));
        $this->assertNull($selection->getMetadata(101));
    }

    public function testToggleInExcludeMode(): void
    {
        $selection = new Selection('ctx_toggle_exclude', $this->storage, $this->normalizer, $this->converter);

        // Define universe and switch to EXCLUDE => everything remembered is selected by default
        $selection->rememberAll([1, 2, 3]);
        $selection->setMode(SelectionMode::EXCLUDE);

        // Initially selected (not excluded yet)
        $this->assertTrue($selection->isSelected(1));

        // Toggle should unselect (i.e., add to exclusion list)
        $state = $selection->toggle(1);
        $this->assertFalse($state);
        $this->assertFalse($selection->isSelected(1));

        // Toggle again should select (remove from exclusion list)
        $state = $selection->toggle(1);
        $this->assertTrue($state);
        $this->assertTrue($selection->isSelected(1));
    }

    public function testCustomMetadataTransformerIsApplied(): void
    {
        $selection = new Selection('ctx_custom_meta', $this->storage, $this->normalizer, $this->converter);

        // Create a custom metadata transformer mock
        $custom = $this->createMock(ValueTransformerInterface::class);
        $custom->method('supports')->willReturnCallback(static fn($v) => is_array($v));
        $custom->method('transform')->willReturnCallback(static fn($v) => new \Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope('custom', $v));
        $custom->method('supportsReverse')->willReturnCallback(static fn($env) => $env->className === 'custom');
        $custom->method('reverseTransform')->willReturnCallback(static function ($env) {
            $o = new \stdClass();
            foreach ((array)$env->data as $k => $v) { $o->{$k} = $v; }
            return $o;
        });

        // Re-create selection with the custom metadata transformer
        $selection = new Selection('ctx_custom_meta', $this->storage, $this->normalizer, $custom);

        $meta = ['foo' => 'bar', 'n' => 9];
        $selection->select(123, $meta);

        // getSelected should hydrate metadata through reverseTransform
        $selected = $selection->getSelected();
        $this->assertArrayHasKey(123, $selected);
        $this->assertInstanceOf(\stdClass::class, $selected[123]);
        $this->assertSame('bar', $selected[123]->foo);
        $this->assertSame(9, $selected[123]->n);

        // getMetadata() should return hydrated object as well
        $m = $selection->getMetadata(123);
        $this->assertInstanceOf(\stdClass::class, $m);
        $this->assertSame('bar', $m->foo);
        $this->assertSame(9, $m->n);
    }

    public function testObjectIdValueTransformerAsMetadata(): void
    {
        // Dummy object with getId()
        $fooClass = new class(77) {
            public function __construct(private int $id) {}
            public function getId(): int { return $this->id; }
        };

        $transformer = new ObjectIdValueTransformer($fooClass::class);
        // Sanity on transformer itself
        $this->assertTrue($transformer->supports($fooClass));

        $selection = new Selection('ctx_obj_id_meta', $this->storage, $transformer, $this->normalizer);

        // Select scalar id with object metadata -> should store envelope and read back id (77)
        $selection->select($fooClass);

        // getSelected returns map id => metadata (reverse transformed)
        $selected = $selection->getSelectedIdentifiers();
        $this->assertSame([77], $selected);
    }
}