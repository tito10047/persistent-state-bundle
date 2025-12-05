<?php

namespace Tito10047\PersistentPreferenceBundle\Preference\Service;

use Tito10047\PersistentPreferenceBundle\Preference\Storage\PreferenceStorageInterface;

interface PreferenceManagerInterface {
	public function getPreference(string|object $owner): PreferenceInterface;
	public function getPreferenceStorage(): PreferenceStorageInterface;

}