<?php

namespace Tito10047\PersistentStateBundle\Tests\Unit\Preference\Storage;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceSessionStorage;

class PreferenceSessionStorageTest extends TestCase
{
    private function createStorageWithSession(?SessionInterface $session): PreferenceSessionStorage
    {
        $stack = $this->createMock(RequestStack::class);

        if ($session === null) {
            $stack->method('getCurrentRequest')->willReturn(null);
        } else {
            $request = new Request();
            $request->setSession($session);
            $stack->method('getCurrentRequest')->willReturn($request);
        }

        return new PreferenceSessionStorage($stack);
    }

    /** @return SessionInterface&MockObject */
    private function createSessionMock(): SessionInterface
    {
        /** @var SessionInterface&MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        return $session;
    }

    public function testGetReturnsDefaultWhenNoSession(): void
    {
        $storage = $this->createStorageWithSession(null);

        $this->assertSame('def', $storage->get('ctx', 'missing', 'def'));
        $this->assertNull($storage->get('ctx', 'missing'));
        $this->assertSame([], $storage->all('ctx'));
        $this->assertFalse($storage->has('ctx', 'x'));
        // remove should be a no-op
        $storage->remove('ctx', 'x');
        // set/setMultiple should be no-ops
        $storage->set('ctx', 'a', 1);
        $storage->setMultiple('ctx', ['a' => 1]);
    }

    public function testSetAndGetValue(): void
    {
        $session = $this->createSessionMock();

        // initial bucket empty
        $session->expects($this->atLeastOnce())
            ->method('get')
            ->with('_persistent_ctx1', $this->isType('array'))
            ->willReturnOnConsecutiveCalls([], ['a' => 123]);

        $session->expects($this->once())
            ->method('set')
            ->with('_persistent_ctx1', ['a' => 123]);

        $storage = $this->createStorageWithSession($session);
        $storage->set('ctx1', 'a', 123);
        $this->assertSame(123, $storage->get('ctx1', 'a'));
    }

    public function testSetMultipleAndAll(): void
    {
        $session = $this->createSessionMock();

        // Start with existing bucket
        $session->expects($this->exactly(2))
            ->method('get')
            ->with('_persistent_ctx2', $this->isType('array'))
            ->willReturnOnConsecutiveCalls(['x' => 1], ['x' => 1, 'a' => 2, 'b' => 3]);

        $session->expects($this->once())
            ->method('set')
            ->with('_persistent_ctx2', ['x' => 1, 'a' => 2, 'b' => 3]);

        $storage = $this->createStorageWithSession($session);
        $storage->setMultiple('ctx2', ['a' => 2, 'b' => 3]);
        $this->assertSame(['x' => 1, 'a' => 2, 'b' => 3], $storage->all('ctx2'));
    }

    public function testHasAndRemove(): void
    {
        $session = $this->createSessionMock();

        $session->expects($this->exactly(3))
            ->method('get')
            ->with('_persistent_ctx3', $this->isType('array'))
            ->willReturnOnConsecutiveCalls(['k' => null], ['k' => null], []);

        $session->expects($this->once())
            ->method('set')
            ->with('_persistent_ctx3', []);

        $storage = $this->createStorageWithSession($session);
        $this->assertTrue($storage->has('ctx3', 'k')); // exists even if null
        $storage->remove('ctx3', 'k');
        $this->assertFalse($storage->has('ctx3', 'k'));
    }

    public function testDifferentContextsAreIsolated(): void
    {
        $session = $this->createSessionMock();

        $session->expects($this->exactly(4))
            ->method('get')
            ->with($this->logicalOr(
                $this->equalTo('_persistent_A'),
                $this->equalTo('_persistent_B')
            ), $this->isType('array'))
            ->willReturnMap([
                ['_persistent_A', [], ['foo' => 1]],
                ['_persistent_A', [], ['foo' => 1]],
                ['_persistent_B', [], []],
                ['_persistent_B', [], []],
            ]);

        // one set call to put value into A
        $session->expects($this->once())
            ->method('set')
            ->with('_persistent_A', ['foo' => 1]);

        $storage = $this->createStorageWithSession($session);
        $storage->set('A', 'foo', 1);
        $this->assertSame(1, $storage->get('A', 'foo'));
        $this->assertNull($storage->get('B', 'foo'));
        $this->assertFalse($storage->has('B', 'foo'));
    }
}
