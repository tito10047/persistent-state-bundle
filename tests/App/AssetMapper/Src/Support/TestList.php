<?php

namespace Tito10047\PersistentPreferenceBundle\Tests\App\AssetMapper\Src\Support;

/**
 * Jednoduchý wrapper okolo zoznamu položiek (polia s kľúčom 'id').
 */
class TestList
{
    /** @var array<int, array<string, mixed>> */
    private array $items;

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return $this->items;
    }
}
