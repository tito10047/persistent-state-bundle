<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Preference\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tito10047\PersistentPreferenceBundle\Event\PreferenceEvent;
use Tito10047\PersistentPreferenceBundle\Event\PreferenceEvents;
use Tito10047\PersistentPreferenceBundle\Preference\Service\Preference;
use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope;
use Tito10047\PersistentPreferenceBundle\Transformer\ValueTransformerInterface;

class PreferenceTest extends TestCase
{
    private function makeTransformer(callable $supports, callable $transform, ?callable $supportsReverse = null, ?callable $reverseTransform = null): ValueTransformerInterface
    {
        $tr = $this->createMock(ValueTransformerInterface::class);
        $tr->method('supports')->willReturnCallback($supports);
        $tr->method('transform')->willReturnCallback($transform);
        $tr->method('supportsReverse')->willReturnCallback($supportsReverse ?? static fn() => false);
        $tr->method('reverseTransform')->willReturnCallback($reverseTransform ?? static fn($v) => $v);
        return $tr;
    }

    public function testSetDispatchesPreThenStoresThenDispatchesPost(): void
    {
        $context = 'ctx1';
        $key = 'theme';
        $input = 'dark';
        $transformed = new StorableEnvelope("scalar",'dark_trans');

        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('set')
            ->with($context, $key, $transformed->toArray());

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $order = 0;
        $dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function ($evt, $name) use (&$order, $context, $key, $input) {
                $order++;
                if ($order === 1) {
                    TestCase::assertInstanceOf(PreferenceEvent::class, $evt);
                    TestCase::assertSame(PreferenceEvents::PRE_SET, $name);
                    TestCase::assertSame($context, $evt->context);
                    TestCase::assertSame($key, $evt->key);
                    TestCase::assertSame($input, $evt->value);
                } elseif ($order === 2) {
                    TestCase::assertInstanceOf(PreferenceEvent::class, $evt);
                    TestCase::assertSame(PreferenceEvents::POST_SET, $name);
                    TestCase::assertSame($context, $evt->context);
                    TestCase::assertSame($key, $evt->key);
                    TestCase::assertSame($input, $evt->value);
                }
                return $evt;
            });

        $transformer = $this->makeTransformer(
            supports: static fn($v) => true,
            transform: static fn($v) => $transformed,
        );

        $service = new Preference([$transformer], $context, $storage, $dispatcher);
        $service->set($key, $input);
    }

    public function testSetStopsOnPreEventPropagationStop(): void
    {
        $context = 'ctx2';
        $key = 'k';
        $value = 123;

        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::never())->method('set');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PreferenceEvent::class), PreferenceEvents::PRE_SET)
            ->willReturnCallback(function (PreferenceEvent $event) {
                $event->stopPropagation();
                return $event;
            });

        $service = new Preference([], $context, $storage, $dispatcher);
        $service->set($key, $value); // should early-return, no post dispatch
    }

    public function testImportDispatchesPreForEachStoresOnceThenDispatchesPostForEach(): void
    {
        $context = 'ctx3';
        $values = ['a' => 1, 'b' => 2];
        $transformed = [
			'a' => (new StorableEnvelope("scalar",'1t'))->toArray(),
			'b' =>  (new StorableEnvelope("scalar",'2t'))->toArray()
		];

        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('setMultiple')
            ->with($context, $transformed);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        // Expect 4 dispatch calls: 2x PRE_SET, then 2x POST_SET
        $dispatcher->expects(self::exactly(4))
            ->method('dispatch')
            ->with(self::isInstanceOf(PreferenceEvent::class), self::logicalOr(PreferenceEvents::PRE_SET, PreferenceEvents::POST_SET))
            ->willReturnArgument(0);

        $transformer = $this->makeTransformer(
            supports: static fn($v) => true,
            transform: static function ($v) { return new StorableEnvelope('scalar',  $v . 't'); }
        );

        $service = new Preference([$transformer], $context, $storage, $dispatcher);
        $service->import($values);
    }

    public function testImportStopsOnAnyPreEvent(): void
    {
        $context = 'ctx4';
        $values = ['x' => 10, 'y' => 20];

        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::never())->method('setMultiple');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $call = 0;
        $dispatcher->expects(self::atLeast(1))
            ->method('dispatch')
            ->willReturnCallback(function (PreferenceEvent $event, string $name) use (&$call) {
                ++$call;
                if ($name === PreferenceEvents::PRE_SET) {
                    $event->stopPropagation(); // stop on first key
                }
                return $event;
            });

        $service = new Preference([], $context, $storage, $dispatcher);
        $service->import($values);
    }
}
