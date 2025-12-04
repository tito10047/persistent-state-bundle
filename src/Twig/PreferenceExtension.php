<?php

namespace Tito10047\PersistentPreferenceBundle\Twig;

use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
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
