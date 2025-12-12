<?php

namespace Tito10047\PersistentStateBundle\Tests\Integration\Preference\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceInterface;
use Tito10047\PersistentStateBundle\Preference\Service\PreferenceManagerInterface;
use Tito10047\PersistentStateBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class PreferenceManagerTest extends AssetMapperKernelTestCase
{
    private function ensureSession(): void
    {
        $rs = static::getContainer()->get(RequestStack::class);
        // If there's already a current request with a session, keep it
        $current = $rs->getCurrentRequest();
        if ($current && $current->hasSession()) {
            return;
        }

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $rs->push($request);
    }

    public function testReturnsPreferenceForStringContextAndPersists(): void
    {
        static::bootKernel();
        $this->ensureSession();
        $pm = static::getContainer()->get(PreferenceManagerInterface::class);

        $pref = $pm->getPreference('user_1');
        $this->assertInstanceOf(PreferenceInterface::class, $pref);

        $pref->set('limit', 25);
        $this->assertTrue($pref->has('limit'));
        $this->assertSame(25, $pref->getInt('limit'));
        $this->assertSame(25, $pref->get('limit'));
        $this->assertTrue($pref->getBool('limit'));

        // ensure isolation by different context
        $pref2 = $pm->getPreference('user_2');
        $this->assertNull($pref2->get('limit'));
        $this->assertFalse($pref2->has('limit'));
    }

    public function testThrowsForUnsupportedObject(): void
    {
        static::bootKernel();
        $this->ensureSession();
        $pm = static::getContainer()->get(PreferenceManagerInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $pm->getPreference(new \stdClass());
    }
}
