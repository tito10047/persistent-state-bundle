<?php

declare(strict_types=1);

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Tito10047\PersistentPreferenceBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

final class PreferenceDataCollectorIntegrationTest extends AssetMapperKernelTestCase
{
    public function testCollectorCountsPersistedPreferencesForContext(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        // Write some preferences using the real manager & session-backed storage
        /** @var PreferenceManagerInterface $manager */
        $manager = $container->get(PreferenceManagerInterface::class);

        $contextKey = 'integration_test_ctx';

        // Ensure RequestStack has a current Request with a Session
        /** @var RequestStack $requestStack */
        $requestStack = $container->get(RequestStack::class);
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $requestStack->push($request);

        // Persist preferences for the context
        $manager->getPreference($contextKey)->import([
            'limit' => 20,
            'theme' => 'dark',
            'sidebar_open' => true,
        ]);

        // Create collector (service may not be registered when profiler is absent)
        $storage = $container->get(\Tito10047\PersistentPreferenceBundle\Storage\StorageInterface::class);
        $collector = new PreferenceDataCollector($storage);
        self::assertInstanceOf(DataCollectorInterface::class, $collector);

        // Manually feed trace info (in test env profiler may be disabled)
        $snapshot = $manager->getPreference($contextKey)->all();
        $collector->onPreferenceChanged('default', $contextKey, $snapshot);

        // Collect with the same Request and context information
        $request->attributes->set('_route', 'integration_test_route');
        $response = new Response('OK', 200);
        $collector->collect($request, $response);

        self::assertTrue($collector->isEnabled());
        self::assertSame(3, $collector->getPreferencesCount());
        $ctx = $collector->getContext();
        $contexts = $ctx['contexts'] ?? [];
        self::assertContains($contextKey, $contexts);
        $managers = $ctx['managers'] ?? [];
        self::assertArrayHasKey('default', $managers);
        self::assertContains($contextKey, $managers['default']['contexts'] ?? []);
    }
}
