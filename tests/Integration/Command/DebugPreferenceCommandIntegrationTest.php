<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Integration\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Tests\Integration\Kernel\AssetMapperKernelTestCase;

class DebugPreferenceCommandIntegrationTest extends AssetMapperKernelTestCase
{
    public function testRunsThroughContainerAndPrintsDoctrineStorage(): void
    {
        static::bootKernel();

        // Seed some preferences into doctrine-backed manager
        /** @var PreferenceManagerInterface $pmDoctrine */
        $pmDoctrine = static::getContainer()->get('persistent_preference.manager.my_pref_manager');
        $pmDoctrine->getPreference('user_15')->import([
            'theme' => 'dark',
            'limit' => 50,
        ]);

        $application = new Application(static::$kernel);
        $command = $application->find('debug:preference');
        $tester = new CommandTester($command);

        $exit = $tester->execute([
            'context' => 'user_15',
            '--manager' => 'my_pref_manager',
        ]);

        $output = $tester->getDisplay();

        self::assertSame(0, $exit, $output);
        self::assertStringContainsString('Context: user_15', $output);
        self::assertStringContainsString('Storage: doctrine', $output);
        self::assertStringContainsString('theme', $output);
        self::assertStringContainsString('dark', $output);
        self::assertStringContainsString('limit', $output);
        self::assertStringContainsString('50', $output);
    }
}
