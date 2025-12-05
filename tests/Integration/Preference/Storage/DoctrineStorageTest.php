<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Preference\Storage;

use Tito10047\PersistentPreferenceBundle\Storage\DoctrinePreferenceStorage;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DoctrineStorageTest extends AssetMapperKernelTestCase
{
    public function testServiceIsRegisteredFromConfigAndPersistsData(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $serviceId = 'app.storage.doctrine';
        $this->assertTrue($container->has($serviceId), 'Doctrine storage service should be registered with configured id');

        /** @var DoctrinePreferenceStorage $storage */
        $storage = $container->get($serviceId);
        $this->assertInstanceOf(DoctrinePreferenceStorage::class, $storage);

        // Basic CRUD
        $ctx = 'user_99';
        $this->assertNull($storage->get($ctx, 'theme'));
        $this->assertFalse($storage->has($ctx, 'theme'));

        $storage->set($ctx, 'theme', 'dark');
        $this->assertTrue($storage->has($ctx, 'theme'));
        $this->assertSame('dark', $storage->get($ctx, 'theme'));

        $storage->setMultiple($ctx, ['limit' => 10, 'enabled' => true]);
        $all = $storage->all($ctx);
        $this->assertSame(['enabled' => true, 'limit' => 10,'theme' => 'dark'], $all);

        $storage->remove($ctx, 'theme');
        $this->assertFalse($storage->has($ctx, 'theme'));
        $this->assertSame(['enabled' => true,'limit' => 10], $storage->all($ctx));
    }

    public function testManagerUsesConfiguredDoctrineStorage(): void
    {
        static::bootKernel();
        $container = static::getContainer();

        $manager = $container->get('persistent_preference.manager.my_pref_manager');
        $pref = $manager->getPreference('user_5');
        $pref->set('x', 1);
        $this->assertSame(1, $pref->getInt('x'));
    }
}
