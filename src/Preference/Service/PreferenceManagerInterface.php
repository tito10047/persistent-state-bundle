<?php

namespace Tito10047\PersistentPreferenceBundle\Preference\Service;

use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceStorageInterface;

interface PreferenceManagerInterface {
    /**
     * Returns a Preference API instance bound to a resolved context for the given owner.
     *
     * The $owner may be a string context directly (e.g. "user_123") or an object
     * that will be resolved to a context using registered ContextKeyResolver(s).
     */
    public function getPreference(string|object $owner): PreferenceInterface;

    /**
     * Exposes the low-level storage used by the manager.
     * Useful for advanced scenarios or debugging.
     */
    public function getPreferenceStorage(): PreferenceStorageInterface;

}