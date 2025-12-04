<?php

namespace Tito10047\PersistentPreferenceBundle\Twig;

use Tito10047\PersistentPreferenceBundle\Service\PreferenceManagerInterface;
use Twig\Extension\RuntimeExtensionInterface;

final class PreferenceRuntime implements RuntimeExtensionInterface
{
    public function __construct(private readonly PreferenceManagerInterface $preferenceManager)
    {
    }

    /**
     * Function variant: {{ preference(context, key, default) }}
     */
    public function getPreferenceValue(object|string $context, string $key, mixed $default = null): mixed
    {
        return $this->preferenceManager->getPreference($context)->get($key, $default);
    }

    /**
     * Filter variant: {{ context|pref(key, default) }}
     */
    public function filterPref(object|string $context, string $key, mixed $default = null): mixed
    {
        return $this->getPreferenceValue($context, $key, $default);
    }
}
