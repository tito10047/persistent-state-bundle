<?php

declare(strict_types=1);

namespace Tito10047\PersistentStateBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension exposing helpers to read preferences directly in templates.
 *
 * Usage examples:
 *  - {{ preference(user, 'foo') }}
 *  - {{ company|pref('bar') }}
 */
final class PreferenceExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('preference', [PreferenceRuntime::class, 'getPreferenceValue']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('pref', [PreferenceRuntime::class, 'filterPref']),
        ];
    }
}
