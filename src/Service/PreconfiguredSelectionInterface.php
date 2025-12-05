<?php

namespace Tito10047\PersistentPreferenceBundle\Service;

interface PreconfiguredSelectionInterface
{
	public function getSelection(string $namespace, mixed $owner = null): SelectionInterface;
}