<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity\Company;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Entity\User;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Twig\Environment;

class PreferenceExtensionTest extends AssetMapperKernelTestCase
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

    public function testTwigFunctionAndFilterReturnStoredValues(): void
    {
        static::bootKernel();
        $this->ensureSession();

        $pm = static::getContainer()->get(PreferenceManagerInterface::class);

        $user = (new User())->setId(5)->setName('Alice');
        $company = (new Company())->setUuid(10)->setName('ACME');

        $pm->getPreference($user)->set('foo', 'bar');
        $pm->getPreference($company)->set('foo2', 'baz');

        /** @var Environment $twig */
        $twig = static::getContainer()->get(Environment::class);

        $tpl = $twig->createTemplate(<<<'TWIG'
<div>
    User Foo: {{ preference(user, 'foo') }}
    Company pref: {{ company|pref('foo2') }}
</div>
TWIG);

        $html = $tpl->render([
            'user' => $user,
            'company' => $company,
        ]);

        self::assertStringContainsString('User Foo: bar', $html);
        self::assertStringContainsString('Company pref: baz', $html);
    }
}
