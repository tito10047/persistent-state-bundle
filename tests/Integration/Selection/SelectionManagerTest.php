<?php

namespace Tito10047\PersistentStateBundle\Tests\Integration\Selection;

use PHPUnit\Framework\Attributes\TestWith;
use stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\ServiceHelper;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Support\TestList;
use Tito10047\PersistentStateBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Tito10047\PersistentStateBundle\Tests\Trait\SessionInterfaceTrait;

class SelectionManagerTest extends AssetMapperKernelTestCase
{
	use SessionInterfaceTrait;

    public function testGetSelectionAndSelectFlow(): void
    {
		$this->initSession();
		$container = self::getContainer();

		/** @var SelectionInterface $manager */
        $manager = $container->get('persistent.selection.manager.scalar');
        $this->assertInstanceOf(SelectionManagerInterface::class, $manager);

        // Use the test normalizer that supports type "array" and requires identifierPath
        $selection = $manager->getSelection('test_key');
        $this->assertInstanceOf(SelectionInterface::class, $selection);

        // Initially nothing selected
        $this->assertFalse($selection->isSelected( 1));

        // Select single item and verify
        $selection->select( 1);
        $this->assertTrue($selection->isSelected(1));

        // Select multiple
        $selection->selectMultiple([
            2,
            3,
        ]);

        $this->assertTrue($selection->isSelected( 2));
        $this->assertTrue($selection->isSelected( 3));

        $ids = $selection->getSelectedIdentifiers();
        sort($ids);
        $this->assertSame([1, 2, 3], $ids);

        // Unselect one and verify
        $selection->unselect(2);
        $this->assertFalse($selection->isSelected( 2));

        $ids = $selection->getSelectedIdentifiers();
        sort($ids);
        $this->assertSame([1, 3], $ids);
    }

    public function testRegisterSourceThrowsWhenNoLoader(): void
    {
        $container = self::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get('persistent.selection.manager.default');
        $this->assertInstanceOf(SelectionManagerInterface::class, $manager);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No suitable loader found');

        // stdClass is not supported by any IdentityLoader in tests/app
        $manager->registerSelection('no_loader_key', new \stdClass());
    }


	#[TestWith(['default',[['id' => 1, 'name' => 'A']]])]
	#[TestWith(['scalar',[['id' => 1, 'name' => 'A']]])]
	#[TestWith(['array',[new stdClass()]])]
	public function testThrowExceptionOnBadNormalizer($service,$data):void {

		$container = self::getContainer();

		/** @var SelectionManagerInterface $manager */
		$manager = $container->get('persistent.selection.manager.'.$service);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('is not supported');
		$manager->registerSelection("array_key_2", $data);
	}

}
