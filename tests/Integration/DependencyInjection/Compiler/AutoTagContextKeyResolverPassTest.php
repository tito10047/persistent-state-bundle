<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\DependencyInjection\Compiler;

use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\ServiceHelper;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class AutoTagContextKeyResolverPassTest extends AssetMapperKernelTestCase
{
    public function testTestArrayNormalizerIsRegisteredAndWorks(): void
    {
        $container = self::getContainer();

        /** @var ServiceHelper $locator */
        $locator = $container->get(ServiceHelper::class);
        $this->assertInstanceOf(ServiceHelper::class, $locator);

    }
}
