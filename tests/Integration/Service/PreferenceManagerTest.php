<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Service;

use PHPUnit\Framework\Attributes\TestWith;
use stdClass;
use Tito10047\PersistentPreferenceBundle\Exception\NormalizationFailedException;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceInterface;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\ServiceHelper;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;
use Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Support\TestList;
use Tito10047\PersistentPreferenceBundle\Enum\PreferenceMode;
use function Zenstruck\Foundry\object;

class PreferenceManagerTest extends AssetMapperKernelTestCase
{
    public function testGetPreferenceAndSelectFlow(): void
    {
    }
}
