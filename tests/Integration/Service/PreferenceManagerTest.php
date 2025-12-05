<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Service;

use PHPUnit\Framework\Attributes\TestWith;
use stdClass;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceInterface;
use Tito10047\PersistentPreferenceBundle\Service\PersistentManagerInterface;
use Tito10047\PersistentPreferenceBundle\Service\PersistentContextInterface;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\ServiceHelper;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Support\TestList;
use Tito10047\PersistentPreferenceBundle\Enum\PreferenceMode;
use function Zenstruck\Foundry\object;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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
        $pm = static::getContainer()->get(PersistentManagerInterface::class);

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

    public function testResolvesObjectContextViaPersistentContextInterface(): void
    {
        static::bootKernel();
        $this->ensureSession();
        $pm = static::getContainer()->get(PersistentManagerInterface::class);

        $obj = new class implements PersistentContextInterface {
            public function getPersistentContext(): string { return 'ctx_object_1'; }
        };

        $pref = $pm->getPreference($obj);
        $pref->import(['enabled' => 'true', 'count' => '10']);

        $this->assertTrue($pref->getBool('enabled'));
        $this->assertSame(10, $pref->getInt('count'));
        $this->assertSame(['enabled' => true, 'count' => 10], [
            'enabled' => $pref->getBool('enabled'),
            'count' => $pref->getInt('count'),
        ]);
    }

    public function testThrowsForUnsupportedObject(): void
    {
        static::bootKernel();
        $this->ensureSession();
        $pm = static::getContainer()->get(PersistentManagerInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $pm->getPreference(new stdClass());
    }
}
