<?php

namespace Tito10047\PersistentPreferenceBundle\Service;


interface PreferenceManagerInterface {

	public function getPreference(string|object $context): PreferenceInterface;

	public function getStorage(): \Tito10047\PersistentPreferenceBundle\Storage\StorageInterface;

}