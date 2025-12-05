<?php

namespace Tito10047\PersistentPreferenceBundle\Service;

interface PreconfiguredPreferenceInterface {
	public function getPreference(string|object $owner): PreferenceInterface;

}