<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Tests\Integration\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManagerInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionManagerInterface;
use Tito10047\PersistentStateBundle\Selection\Service\TraceableSelection;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity\RecordInteger;
use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity\User;
use Tito10047\PersistentStateBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Tito10047\PersistentStateBundle\Tests\Trait\SessionInterfaceTrait;

final class SelectionTraceIntegrationTest extends AssetMapperKernelTestCase
{
    use SessionInterfaceTrait;
    public function testSelectionTracingOnRead(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->initSession();

        /** @var SelectionManagerInterface $manager */
        $manager = $container->get(SelectionManagerInterface::class);

        $collector = new PreferenceDataCollector();

        $source = [
            new User(1),
            new User(2),
            new User(3),
        ];

        // We need to manually wrap it or use the one from container if it's already decorated
        // In test env, it should be decorated if TraceableManagersPass worked.
        $selection = $manager->registerSelection('test_ns', $source);

        // If it's not traceable (e.g. compiler pass didn't run in this test context properly), wrap it
        if (!$selection instanceof TraceableSelection) {
            $selection = new TraceableSelection('default', 'test_ns', $selection, $collector);
        } else {
            // If it is traceable, we need to make sure it uses our collector instance for assertions
            // But since it's a service, it uses the collector from container.
            $collector = $container->get(PreferenceDataCollector::class);
        }

        // Initially nothing selected
        $selection->isSelected(1);

        $request = new Request();
        $response = new Response();
        $collector->collect($request, $response);

        $this->assertEquals(0, $collector->getSelectionsCount());

        // Select one
        $selection->select(1);
        $collector->collect($request, $response);
        $this->assertEquals(1, $collector->getSelectionsCount());

        // Select ALL
        $selection->selectAll();
        $collector->collect($request, $response);
        // Total should be 3 (since we registered [1,2,3])
        $ctx = $collector->getContext();
        $this->assertEquals(3, $collector->getSelectionsCount());

        $ctx = $collector->getContext();
        $this->assertEquals(SelectionMode::EXCLUDE, $ctx['selections']['default']['namespaces']['test_ns']['mode']);

        // Unselect one in EXCLUDE mode
        $selection->unselect(2);
        $collector->collect($request, $response);
        // Total should be 2
        $this->assertEquals(2, $collector->getSelectionsCount());
    }
}
