<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tito10047\PersistentPreferenceBundle\Service\PersistentManagerInterface;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity\Company;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity\User;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class ObjectContextResolverTest extends AssetMapperKernelTestCase
{
    private function ensureSession(): void
    {
        $rs = static::getContainer()->get(RequestStack::class);
        $current = $rs->getCurrentRequest();
        if ($current && $current->hasSession()) {
            return;
        }

        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);
        $rs->push($request);
    }

    public function testResolvesConfiguredUserAndCompanyContexts(): void
    {
        static::bootKernel();
        $this->ensureSession();

        $pm = static::getContainer()->get(PersistentManagerInterface::class);

        $user = (new User())->setId(10);
        $company = (new Company())->setUuid(77);

        // Store via object contexts
        $pm->getPreference($user)->set('limit', 5);
        $pm->getPreference($company)->set('enabled', 'true');

        // Retrieve via explicit string contexts to verify resolver produced keys
        $this->assertSame(5, $pm->getPreference('user_10')->getInt('limit'));
        $this->assertTrue($pm->getPreference('company_77')->getBool('enabled'));

        $this->assertSame(5, $pm->getPreference($user)->getInt('limit'));
        $this->assertTrue($pm->getPreference($company)->getBool('enabled'));

        // Also ensure unsupported object still throws
        $this->expectException(\InvalidArgumentException::class);
        $pm->getPreference(new \stdClass());
    }
}
