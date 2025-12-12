<?php

namespace Tito10047\PersistentStateBundle\Tests\Integration\DependencyInjection\Compiler;

use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\ServiceHelper;
use Tito10047\PersistentStateBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

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
