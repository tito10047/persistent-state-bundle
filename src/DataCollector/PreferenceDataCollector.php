<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;

final class PreferenceDataCollector extends DataCollector
{
    public function __construct(private readonly PreferenceStorageInterface $storage)
    {
        $this->reset();
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        // Provide generic info; detailed stats are fed through trace hooks
        $storage = $this->storage;

        $managers = (array)($this->data['managers'] ?? []);
        $total = 0;
        $flattenedContexts = [];
        $perManager = [];
        foreach ($managers as $managerName => $info) {
            $contexts = (array)($info['contexts'] ?? []);
            $ctxCount = 0;
            foreach ($contexts as $ctxId => $values) {
                $count = is_array($values) ? \count($values) : 0;
                $total += $count;
                $ctxCount += $count;
                $flattenedContexts[$ctxId] = true;
            }
            $perManager[$managerName] = [
                'contexts' => array_keys($contexts),
                'count' => $ctxCount,
            ];
        }

        $this->data['enabled'] = true;
        $this->data['preferencesCount'] = $total;
        $this->data['context'] = [
            'storage' => \get_class($storage),
            'route' => $request->attributes->get('_route'),
            'contexts' => array_keys($flattenedContexts),
            'managers' => $perManager,
        ];
    }

    public function reset(): void
    {
        $this->data = [
            'enabled' => false,
            'preferencesCount' => 0,
            'context' => [],
            'managers' => [],
        ];
    }

    public function getName(): string
    {
        return 'app.preference_collector';
    }

    public function isEnabled(): bool
    {
        return (bool)($this->data['enabled'] ?? false);
    }

    public function getPreferencesCount(): int
    {
        return (int)($this->data['preferencesCount'] ?? 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function getContext(): array
    {
        return (array)($this->data['context'] ?? []);
    }

    /**
     * Trace hook used by TraceablePreference[Manager] to push current snapshot.
     *
     * @param string               $managerName
     * @param string               $contextId
     * @param array<string, mixed> $allValues
     */
    public function onPreferenceChanged(string $managerName, string $contextId, array $allValues): void
    {
        if (!isset($this->data['managers']) || !\is_array($this->data['managers'])) {
            $this->data['managers'] = [];
        }
        if (!isset($this->data['managers'][$managerName]) || !\is_array($this->data['managers'][$managerName])) {
            $this->data['managers'][$managerName] = ['contexts' => []];
        }
        $this->data['managers'][$managerName]['contexts'][$contextId] = $allValues;
    }
}
