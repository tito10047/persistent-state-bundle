<?php

namespace Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Factory;

use Tito10047\PersistentStateBundle\Tests\App\AssetMapper\Src\Entity\TestCategory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<TestCategory>
 */
final class TestCategoryFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return TestCategory::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => 'Category',
        ];
    }
}
