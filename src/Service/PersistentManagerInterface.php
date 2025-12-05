<?php

namespace Tito10047\PersistentPreferenceBundle\Service;



interface PersistentManagerInterface extends PreconfiguredSelectionInterface, PreconfiguredPreferenceInterface{

	public function getPreference(string|object $owner): PreferenceInterface;

	public function getPreferenceStorage(): \Tito10047\PersistentPreferenceBundle\Storage\PreferenceStorageInterface;
	public function getSelection(string $namespace, mixed $owner = null): SelectionInterface;

}