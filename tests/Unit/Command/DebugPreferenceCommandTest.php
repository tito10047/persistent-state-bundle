<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\RequestStack;
use Tito10047\PersistentPreferenceBundle\Command\DebugPreferenceCommand;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceInterface;
use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Tito10047\PersistentPreferenceBundle\Storage\SessionStorage;
use Tito10047\PersistentPreferenceBundle\Storage\StorageInterface;

class DebugPreferenceCommandTest extends TestCase
{
    private function makeContainerMock(array $services, array $hasMap = []): ContainerInterface
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('has')
            ->willReturnCallback(static function (string $id) use ($hasMap, $services) {
                if (array_key_exists($id, $hasMap)) {
                    return (bool) $hasMap[$id];
                }
                return array_key_exists($id, $services);
            });

        $container->method('get')
            ->willReturnCallback(static function (string $id) use ($services) {
                if (!array_key_exists($id, $services)) {
                    throw new \RuntimeException('Service not found: ' . $id);
                }
                return $services[$id];
            });

        return $container;
    }

    public function testSuccessWithRowsAndSessionStorageLabel(): void
    {
        $context = 'user_15';
        $serviceId = 'persistent_preference.manager.default';

        $preference = $this->createMock(PreferenceInterface::class);
        $preference->method('all')->willReturn([
            'theme' => 'dark',
            'limit' => 50,
            'enabled' => true,
            'data' => ['a' => 1],
            'nothing' => null,
        ]);

        $storage = new SessionStorage(new RequestStack());

        $manager = $this->createMock(PreferenceManagerInterface::class);
        $manager->method('getPreference')->with($context)->willReturn($preference);
        $manager->method('getStorage')->willReturn($storage);

        $container = $this->makeContainerMock([
            $serviceId => $manager,
        ]);

        $command = new DebugPreferenceCommand($container);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'context' => $context,
            '--manager' => 'default',
        ]);

        $output = $tester->getDisplay();
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Context: user_15', $output);
        self::assertStringContainsString('Storage: session', $output);
        self::assertStringContainsString('theme', $output);
        self::assertStringContainsString('dark', $output);
        self::assertStringContainsString('limit', $output);
        self::assertStringContainsString('50', $output);
        self::assertStringContainsString('enabled', $output);
        self::assertStringContainsString('true', $output);
        self::assertStringContainsString('"a":1', $output); // JSON for array value
        self::assertStringContainsString('nothing', $output);
        self::assertStringContainsString('null', $output);
    }

    public function testEmptyPreferencesShowsMessage(): void
    {
        $context = 'ctx';
        $serviceId = 'persistent_preference.manager.default';

        $preference = $this->createMock(PreferenceInterface::class);
        $preference->method('all')->willReturn([]);

        $storage = $this->createMock(StorageInterface::class);

        $manager = $this->createMock(PreferenceManagerInterface::class);
        $manager->method('getPreference')->with($context)->willReturn($preference);
        $manager->method('getStorage')->willReturn($storage);

        $container = $this->makeContainerMock([
            $serviceId => $manager,
        ]);

        $command = new DebugPreferenceCommand($container);
        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'context' => $context,
        ]);

        $output = $tester->getDisplay();
        self::assertSame(0, $exitCode);
        self::assertStringContainsString('(no preferences)', $output);
    }

    public function testMissingManagerServiceFails(): void
    {
        $container = $this->makeContainerMock([], [
            'persistent_preference.manager.missing' => false,
        ]);

        $command = new DebugPreferenceCommand($container);
        $tester = new CommandTester($command);
        $code = $tester->execute([
            'context' => 'x',
            '--manager' => 'missing',
        ]);

        $output = $tester->getDisplay();
        self::assertSame(1, $code);
        self::assertStringContainsString('not found', strtolower($output));
    }

    public function testWrongServiceTypeFails(): void
    {
        $serviceId = 'persistent_preference.manager.weird';
        $container = $this->makeContainerMock([
            $serviceId => new \stdClass(),
        ]);

        $command = new DebugPreferenceCommand($container);
        $tester = new CommandTester($command);
        $code = $tester->execute([
            'context' => 'x',
            '--manager' => 'weird',
        ]);

        $output = $tester->getDisplay();
        self::assertSame(1, $code);
        $normalized = preg_replace('/\s+/', ' ', $output);
        self::assertStringContainsString('is not a PreferenceManagerInterface', $normalized);
    }

    public function testFallbackStorageNameUsesShortClassName(): void
    {
        $context = 'c';
        $serviceId = 'persistent_preference.manager.default';

        $preference = $this->createMock(PreferenceInterface::class);
        $preference->method('all')->willReturn(['a' => 1]);

        // Custom storage implementing interface to trigger fallback name
        $customStorage = new class() implements StorageInterface {
            public function get(string $context, string $key, mixed $default = null): mixed { return null; }
            public function set(string $context, string $key, mixed $value): void {}
            public function setMultiple(string $context, array $values): void {}
            public function remove(string $context, string $key): void {}
            public function has(string $context, string $key): bool { return false; }
            public function all(string $context): array { return []; }
        };

        $manager = $this->createMock(PreferenceManagerInterface::class);
        $manager->method('getPreference')->with($context)->willReturn($preference);
        $manager->method('getStorage')->willReturn($customStorage);

        $container = $this->makeContainerMock([
            $serviceId => $manager,
        ]);

        $command = new DebugPreferenceCommand($container);
        $tester = new CommandTester($command);
        $tester->execute(['context' => $context]);
        $output = $tester->getDisplay();

        // Anonymous class name includes interface in PHPUnit 10 with Reflection
        self::assertStringContainsString('Storage:', $output);
        self::assertStringContainsString('@anonymous', $output);
    }
}
