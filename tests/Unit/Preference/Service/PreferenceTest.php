<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Preference\Service;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tito10047\PersistentPreferenceBundle\Event\PreferenceEvent;
use Tito10047\PersistentPreferenceBundle\Event\PreferenceEvents;
use Tito10047\PersistentPreferenceBundle\Preference\Service\Preference;
use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceStorageInterface;
use Tito10047\PersistentPreferenceBundle\Storage\StorableEnvelope;
use Tito10047\PersistentPreferenceBundle\Transformer\ScalarValueTransformer;
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

    public function testGetContextReturnsProvidedContext(): void
    {
        $context = 'user_42';
        $storage = $this->createMock(PreferenceStorageInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $service = new Preference([], $context, $storage, $dispatcher);
        self::assertSame($context, $service->getContext());
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

    public function testGetReturnsDefaultWithoutReverseTransform(): void
    {
        $context = 'ctx5';
        $key = 'missing';
        $default = 'DEF';

        $storage = $this->createMock(PreferenceStorageInterface::class);
        // storage returns exactly the default value
        $storage->expects(self::once())
            ->method('get')
            ->with($context, $key, $default)
            ->willReturn($default);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new Preference([], $context, $storage, $dispatcher);

        self::assertSame($default, $service->get($key, $default));
    }

    public function testGetAppliesReverseTransformWhenSupported(): void
    {
        $context = 'ctx6';
        $key = 'obj';
        $stored = (new StorableEnvelope('scalar', 'raw'))->toArray();

        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('get')
            ->with($context, $key, null)
            ->willReturn($stored);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $transformer = $this->makeTransformer(
            supports: static fn($v) => false,
            transform: static fn($v) => new StorableEnvelope('scalar', $v),
            supportsReverse: static fn(StorableEnvelope $v) => $v->className=='scalar',
            reverseTransform: static fn(StorableEnvelope $v) => $v->data.'_rt',
        );

        $service = new Preference([$transformer], $context, $storage, $dispatcher);
        self::assertSame('raw_rt', $service->get($key));
    }

    public function testGetIntCastsValuesProperly(): void
    {
        $context = 'ctx7';
        $storage = $this->createMock(PreferenceStorageInterface::class);

		$transformer = new ScalarValueTransformer();
		$map = [
            // context, key, default => return
            [$context, 'i1', 0, $transformer->transform(7)->toArray()],
            [$context, 'i2', 0, $transformer->transform('15')->toArray()],
            [$context, 'i3', 5, $transformer->transform('no-number')->toArray()],
        ];
		$storage->method('get')->willReturnMap($map);

		$dispatcher             = $this->createMock(EventDispatcherInterface::class);
		$service                = new Preference([$transformer], $context, $storage, $dispatcher);

        self::assertSame(7, $service->getInt('i1'));
        self::assertSame(15, $service->getInt('i2'));
        self::assertSame(5, $service->getInt('i3', 5));
    }

    public function testHasDelegatesToStorage(): void
    {
        $context = 'ctx9';
        $key = 'exists';
        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('has')
            ->with($context, $key)
            ->willReturn(true);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new Preference([], $context, $storage, $dispatcher);
        self::assertTrue($service->has($key));
    }

    public function testRemoveDelegatesToStorageAndIsFluent(): void
    {
        $context = 'ctx10';
        $key = 'to_remove';
        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('remove')
            ->with($context, $key);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $service = new Preference([], $context, $storage, $dispatcher);

        self::assertSame($service, $service->remove($key));
    }

    public function testAllAppliesReverseTransformToEachValue(): void
    {
        $context = 'ctx11';
        $raw = [
            'a' => (new StorableEnvelope('scalar', '1'))->toArray(),
            'b' => (new StorableEnvelope('scalar', '2'))->toArray(),
        ];

        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('all')
            ->with($context)
            ->willReturn($raw);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $transformer = $this->makeTransformer(
            supports: static fn($v) => false,
            transform: static fn($v) => new StorableEnvelope('scalar', $v),
            supportsReverse: static fn(StorableEnvelope $v) => $v->className === 'scalar',
            reverseTransform: static fn(StorableEnvelope $v) => 'rt-'.$v->data,
        );

        $service = new Preference([$transformer], $context, $storage, $dispatcher);
        self::assertSame(['a' => 'rt-1', 'b' => 'rt-2'], $service->all());
    }

    public function testSetThenGetRoundtripReturnsSameLogicalValue(): void
    {
        $context = 'ctx12';
        $key = 'round';
        $input = ['x' => 1];

        $storedValue = null;
        $storage = $this->createMock(PreferenceStorageInterface::class);
        $storage->expects(self::once())
            ->method('set')
            ->with($context, $key, self::isType('array'))
            ->willReturnCallback(function ($ctx, $k, $val) use (&$storedValue) { $storedValue = $val; });
        $storage->expects(self::once())
            ->method('get')
            ->with($context, $key, null)
            ->willReturnCallback(function () use (&$storedValue) { return $storedValue; });

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        // Identity forward transform into envelope and exact reverse
        $transformer = $this->makeTransformer(
            supports: static fn($v) => is_array($v),
            transform: static fn($v) => new StorableEnvelope('json', json_encode($v)),
            supportsReverse: static fn(StorableEnvelope $v) => $v->className=='json',
            reverseTransform: static fn(StorableEnvelope $v) => json_decode($v->data, true),
        );

        $service = new Preference([$transformer], $context, $storage, $dispatcher);
        $service->set($key, $input);
        $out = $service->get($key);

        self::assertSame($input, $out);
    }
}
