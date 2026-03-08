<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Tests\Integration\Selection;

use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity\UserSelection;
use Tito10047\PersistentStateBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class SelectionDoctrineStorageIntegrationTest extends AssetMapperKernelTestCase
{
    public function testDoctrineStoragePersistsData(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get('persistent_state.selection.manager.db_selection');
        $selection = $manager->getSelection('test_context');

        // 1. Pridanie ID (select)
        $selection->select(123, ['foo' => 'bar']);
        $selection->select(456);

        $em = $container->get('doctrine.orm.entity_manager');
        $em->flush();

        // Overenie cez API selekcie
        $this->assertTrue($selection->isSelected(123));
        $this->assertTrue($selection->isSelected(456));
        $this->assertEquals(['foo' => 'bar'], $selection->getMetadata(123));

        // 2. Overenie v databáze
        $em = $container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository(UserSelection::class);

        /** @var UserSelection $entity */
        $entity = $repo->findOneBy(['context' => 'test_context']);

        $this->assertNotNull($entity);
        $this->assertContains(123, $entity->getIdentifiers());
        $this->assertContains(456, $entity->getIdentifiers());
        $metadata = $entity->getMetadata();
        $this->assertArrayHasKey('123', $metadata);
        $this->assertEquals('array', $metadata['123']['__class__']);
        $this->assertEquals(['foo' => 'bar'], $metadata['123']['data']);
        $this->assertEquals(SelectionMode::INCLUDE, $entity->getMode());

        // 3. Zmena na selectAll (EXCLUDE mode) a unselect
        $selection->selectAll();
        $selection->unselect(123);

        $em->flush();
        $em->clear(); // Clear identity map to force reload from DB

        $entity = $repo->findOneBy(['context' => 'test_context']);
        $this->assertEquals(SelectionMode::EXCLUDE, $entity->getMode());
        $this->assertContains(123, $entity->getIdentifiers()); // V EXCLUDE móde sú identifiers VÝNIMKY (nevybrané)
        $this->assertNotContains(456, $entity->getIdentifiers()); // 456 zostal vybraný, takže nie je vo výnimkách
        // Tu sme unselectli 123, takze 123 by mala mat metadata ak sme ich tam nechali.
        // Ale pockaj, Selection::unselect() vola storage->remove().
        // A storage->remove() u mna maze metadata.
        $this->assertArrayNotHasKey('123', $entity->getMetadata());
    }
}
