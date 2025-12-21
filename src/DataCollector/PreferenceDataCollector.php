<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Tito10047\PersistentStateBundle\Enum\SelectionMode;
use Tito10047\PersistentStateBundle\Preference\Storage\PreferenceStorageInterface;

final class PreferenceDataCollector extends DataCollector
{
    public function __construct()
    {
        $this->reset();
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {

        $managers = (array) ($this->data['managers'] ?? []);
        $total = 0;
        $flattenedContexts = [];
        $perManager = [];
        foreach ($managers as $managerName => $info) {
            $contexts = (array) ($info['contexts'] ?? []);
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

        $selections = (array) ($this->data['selections'] ?? []);
        $totalSelections = 0;
        $perSelectionManager = [];
        foreach ($selections as $managerName => $info) {
            $namespaces = (array) ($info['namespaces'] ?? []);
            $nsCount = 0;
            foreach ($namespaces as $nsId => $data) {
                $identifiersCount = \is_array($data['identifiers'] ?? null) ? \count($data['identifiers']) : 0;
                $totalSelections += $identifiersCount;
                $nsCount += $identifiersCount;
            }
            $perSelectionManager[$managerName] = [
                'namespaces' => array_keys($namespaces),
                'count' => $nsCount,
            ];
        }

        $this->data['enabled'] = true;
        $this->data['preferencesCount'] = $total;
        $this->data['selectionsCount'] = $totalSelections;
        $this->data['context'] = [
            'route' => $request->attributes->get('_route'),
            'contexts' => array_keys($flattenedContexts),
            'managers' => $perManager,
            'selection_managers' => $perSelectionManager,
            'selections' => $selections,
        ];
    }

    public function reset(): void
    {
        $this->data = [
            'enabled' => false,
            'preferencesCount' => 0,
            'selectionsCount' => 0,
            'context' => [],
            'managers' => [],
            'selections' => [],
        ];
    }

    public function getName(): string
    {
        return 'app.preference_collector';
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->data['enabled'] ?? false);
    }

    public function getPreferencesCount(): int
    {
        return (int) ($this->data['preferencesCount'] ?? 0);
    }

    public function getSelectionsCount(): int
    {
        return (int) ($this->data['selectionsCount'] ?? 0);
    }

    /**
     * @return array<string,mixed>
     */
    public function getContext(): array
    {
        return (array) ($this->data['context'] ?? []);
    }

    /**
     * Trace hook used by TraceablePreference[Manager] to push current snapshot.
     *
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

    /**
     * @param array<string|int> $identifiers
     */
    public function onSelectionChanged(string $managerName, string $namespace, array $identifiers, SelectionMode $mode = SelectionMode::INCLUDE, int $total = 0): void
    {
        if (!isset($this->data['selections']) || !\is_array($this->data['selections'])) {
            $this->data['selections'] = [];
        }
        if (!isset($this->data['selections'][$managerName]) || !\is_array($this->data['selections'][$managerName])) {
            $this->data['selections'][$managerName] = ['namespaces' => []];
        }
        $this->data['selections'][$managerName]['namespaces'][$namespace] = [
            'identifiers' => $identifiers,
            'mode' => $mode,
            'total' => $total,
        ];
    }
}
