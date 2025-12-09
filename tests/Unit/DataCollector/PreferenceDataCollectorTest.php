<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Tests\Unit\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;

final class PreferenceDataCollectorTest extends TestCase
{
    public function testResetSetsDefaults(): void
    {
        $storage = $this->createMock(PreferenceStorageInterface::class);

        $collector = new PreferenceDataCollector($storage);
        $collector->reset();

        self::assertFalse($collector->isEnabled());
        self::assertSame(0, $collector->getPreferencesCount());
        self::assertSame([], $collector->getContext());
        self::assertSame('app.preference_collector', $collector->getName());
    }

    public function testCollectPopulatesData(): void
    {
        $storage = $this->createMock(PreferenceStorageInterface::class);

        $collector = new PreferenceDataCollector($storage);
        $request = new Request([], [], ['_route' => 'test_route']);
        $response = new Response('OK', 200);

        $collector->collect($request, $response);

        self::assertTrue($collector->isEnabled());
        self::assertSame(0, $collector->getPreferencesCount());
        $context = $collector->getContext();
        self::assertArrayHasKey('storage', $context);
        self::assertArrayHasKey('route', $context);
        self::assertSame('test_route', $context['route']);
    }
}
