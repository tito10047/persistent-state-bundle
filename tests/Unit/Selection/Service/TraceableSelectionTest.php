<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Tests\Unit\Selection\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tito10047\PersistentStateBundle\DataCollector\PreferenceDataCollector;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Selection\Service\SelectionInterface;
use Tito10047\PersistentStateBundle\Selection\Service\TraceableSelection;

final class TraceableSelectionTest extends TestCase
{
    private PreferenceDataCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new PreferenceDataCollector();
    }

    public function testNotifyDoesNotThrowWhenInnerGetSelectedIdentifiersThrows(): void
    {
        $inner = $this->createMock(SelectionInterface::class);
        $inner->method('getSelectedIdentifiers')
            ->willThrowException(new \LogicException("You can't call getSelectedIdentifiers without previous call registerSelection"));
        $inner->method('getTotal')->willReturn(0);
        $inner->method('select')->willReturnSelf();

        $traceable = new TraceableSelection('manager', 'ns', $inner, $this->collector);

        // Must not propagate the LogicException from getSelectedIdentifiers
        $traceable->select(1);

        // If we reach this point, the exception was swallowed as expected
        $this->collector->collect(new Request(), new Response());
        $this->assertSame(0, $this->collector->getSelectionsCount());
    }

    public function testNotifyReportsEmptyIdentifiersWhenInnerThrows(): void
    {
        $inner = $this->createMock(SelectionInterface::class);
        $inner->method('getSelectedIdentifiers')
            ->willThrowException(new \LogicException("You can't call getSelectedIdentifiers without previous call registerSelection"));
        $inner->method('getTotal')->willReturn(3);
        $inner->method('select')->willReturnSelf();

        $traceable = new TraceableSelection('manager', 'ns', $inner, $this->collector);
        $traceable->select(1);

        $this->collector->collect(new Request(), new Response());
        $ctx = $this->collector->getContext();

        $this->assertSame([], $ctx['selections']['manager']['namespaces']['ns']['identifiers']);
        $this->assertSame(0, $this->collector->getSelectionsCount());
    }

    public function testNotifyCollectsIdentifiersFromInnerWhenSuccessful(): void
    {
        $inner = $this->createMock(SelectionInterface::class);
        $inner->method('getSelectedIdentifiers')->willReturn([1, 2]);
        $inner->method('getTotal')->willReturn(3);
        $inner->method('select')->willReturnSelf();

        $traceable = new TraceableSelection('manager', 'ns', $inner, $this->collector);
        $traceable->select(1);

        $this->collector->collect(new Request(), new Response());
        $ctx = $this->collector->getContext();

        $this->assertSame([1, 2], $ctx['selections']['manager']['namespaces']['ns']['identifiers']);
        $this->assertSame(2, $this->collector->getSelectionsCount());
    }

    public function testNotifyReportsCorrectModeFromInner(): void
    {
        $inner = $this->createMock(SelectionInterface::class);
        $inner->method('getSelectedIdentifiers')->willReturn([]);
        $inner->method('getTotal')->willReturn(0);
        $inner->method('unselectAll')->willReturnSelf();

        $traceable = new TraceableSelection('manager', 'ns', $inner, $this->collector);
        $traceable->unselectAll();

        $this->collector->collect(new Request(), new Response());
        $ctx = $this->collector->getContext();

        // inner does not implement HasModeInterface, so mode defaults to INCLUDE
        $this->assertSame(SelectionMode::INCLUDE, $ctx['selections']['manager']['namespaces']['ns']['mode']);
    }
}
