<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Selection\Loader;

use Doctrine\Common\Collections\ArrayCollection;
use Tito10047\PersistentPreferenceBundle\Selection\Loader\DoctrineCollectionLoader;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DoctrineCollectionLoaderTest extends AssetMapperKernelTestCase
{
    public function testGetCacheKeyStableAndDistinct(): void
    {
        $loader = new DoctrineCollectionLoader();

        $o1 = (object)['id' => 1, 'name' => 'A'];
        $o2 = (object)['id' => 2, 'name' => 'B'];

        $c1 = new ArrayCollection([$o1, $o2]);
        $c2 = new ArrayCollection([(object)['id' => 1, 'name' => 'A'], (object)['id' => 2, 'name' => 'B']]);

        // Rovnaký obsah -> rovnaký cache key
        $k1a = $loader->getCacheKey($c1);
        $k1b = $loader->getCacheKey($c1);
        $k2  = $loader->getCacheKey($c2);
        $this->assertSame($k1a, $k1b);
        $this->assertSame($k1a, $k2);

        // Po zmene obsahu -> iný cache key
        $c2->add((object)['id' => 3, 'name' => 'C']);
        $k3 = $loader->getCacheKey($c2);
        $this->assertNotSame($k2, $k3);
    }
}
